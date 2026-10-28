<?php
declare(strict_types=1);
require_once __DIR__.'/organizations.php';
function oplen_deletion_schema(PDO $pdo): void {
    $id=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';
    $tail=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4':'';
    $pdo->exec("CREATE TABLE IF NOT EXISTS oplen_company_deletions (id $id,customer_id BIGINT NOT NULL UNIQUE,admin_id VARCHAR(64) NOT NULL,company_name VARCHAR(100) NOT NULL,slug VARCHAR(63),reason VARCHAR(500) NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'queued',requested_at BIGINT NOT NULL,completed_at BIGINT)$tail");
}
function oplen_deletion_version(array $customer): string {
    return hash('sha256',json_encode([$customer['id'],$customer['status'],$customer['slug'],$customer['company_name'],$customer['email'],$customer['access_kind'],$customer['trial_ends_at']]));
}
function oplen_deletion_request(PDO $pdo,array $admin,array $input): void {
    $id=filter_var($input['customer_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$reason=trim((string)($input['reason']??''));
    if(!$id||strlen($reason)<3||strlen($reason)>500)throw new DomainException('Choose a company and provide a reason between 3 and 500 characters.');
    oplen_deletion_schema($pdo);$pdo->beginTransaction();
    try{
        $lock=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
        $q=$pdo->prepare('SELECT * FROM oplen_customers WHERE id=?'.$lock);$q->execute([$id]);$customer=$q->fetch();
        if(!$customer||!in_array($customer['status'],['active','pending'],true))throw new DomainException('This company cannot be deleted here. Provisioning or failed setup must be reviewed first.');
        if(!hash_equals(oplen_deletion_version($customer),(string)($input['version']??'')))throw new DomainException('This company changed. Reload before deleting.');
        $confirmation=$customer['slug']?:$customer['company_name'];
        if(!hash_equals($confirmation,trim((string)($input['confirmation']??''))))throw new DomainException('Type the company identifier exactly to confirm deletion.');
        $pending=$customer['status']==='pending';
        if($pending&&$customer['slug']!==null)throw new DomainException('This setup must be reviewed before deletion.');
        if(!$pending){
            $q=$pdo->prepare('SELECT status FROM oplen_tenants WHERE slug=?'.$lock);$q->execute([$customer['slug']]);
            if($q->fetchColumn()!=='active')throw new DomainException('The company resources must be reviewed before deletion.');
            $pdo->prepare("UPDATE oplen_tenants SET status='deletion_requested' WHERE slug=? AND status='active'")->execute([$customer['slug']]);
            $pdo->prepare("UPDATE oplen_customers SET status='deletion_requested',activation_version=activation_version+1 WHERE id=?")->execute([$id]);
            $pdo->prepare('DELETE FROM oplen_handoffs WHERE tenant_slug=?')->execute([$customer['slug']]);
        }else{
            $pdo->prepare("UPDATE oplen_customers SET status='deleted',deleted_at=?,email=?,admin_name='Cuenta eliminada',company_name='Empresa eliminada',activation_version=activation_version+1 WHERE id=?")->execute([time(),'deleted-'.$id.'@invalid.example',$id]);
        }
        $pdo->prepare('DELETE FROM oplen_activations WHERE customer_id=?')->execute([$id]);
        $pdo->prepare('INSERT INTO oplen_company_deletions(customer_id,admin_id,company_name,slug,reason,status,requested_at,completed_at) VALUES(?,?,?,?,?,?,?,?)')->execute([$id,$admin['id'],$customer['company_name'],$customer['slug'],$reason,$pending?'completed':'queued',time(),$pending?time():null]);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
