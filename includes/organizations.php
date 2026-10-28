<?php
declare(strict_types=1);
require_once __DIR__.'/database.php';

function oplen_organizations_allowed(array $admin, string $permission='organizations.read'): bool {
    $q=oplen_pdo()->prepare("SELECT r.code,p.code permission_code FROM admin_accounts a JOIN admin_roles r ON r.id=a.role_id LEFT JOIN role_permissions rp ON rp.role_id=r.id LEFT JOIN permissions p ON p.id=rp.permission_id WHERE a.id=?");
    $q->execute([$admin['id']]);
    foreach($q->fetchAll() as $row){
        if($row['code']==='super_admin'||in_array($row['permission_code'],['admin.full',$permission],true))return true;
    }
    return false;
}
function oplen_registry_pdo(): PDO {
    $config=oplen_admin_registry_config();
    foreach(['db_host','db_name','db_user','db_pass'] as $key){
        if(!isset($config[$key])||!is_string($config[$key]))throw new RuntimeException('Incomplete registry configuration.');
    }
    return new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3
    ]);
}
function oplen_registry_access(array $customer,int $now): string {
    if($customer['status']==='deleted')return 'Deleted';
    if(in_array($customer['status'],['deleting','deletion_requested'],true))return 'Deletion in progress';
    if($customer['status']!=='active')return 'Setup pending';
    if(in_array($customer['access_kind'],['paid','courtesy'],true))return 'Full access';
    $end=(int)$customer['trial_ends_at'];
    if(!$end)return 'Blocked';
    if($now<$end)return 'Trial';
    if($now<$end+30*86400)return 'Read only';
    if($now<$end+60*86400)return 'Blocked';
    return 'Pending deletion';
}
function oplen_registry_date($value): string {
    if(!$value)return '—';
    return (new DateTimeImmutable('@'.(int)$value))->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d M Y · H:i');
}

function oplen_subscription_version(array $customer): string {
    return hash('sha256',json_encode([$customer['id'],$customer['status'],$customer['access_kind'],$customer['trial_ends_at']]));
}
function oplen_subscription_end(string $value): int {
    $zone=new DateTimeZone('America/Mexico_City');
    $date=DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i',$value,$zone);
    $errors=DateTimeImmutable::getLastErrors();
    if(!$date||($errors&&($errors['warning_count']||$errors['error_count']))||$date->format('Y-m-d\\TH:i')!==$value)
        throw new DomainException('Choose a valid trial end date.');
    $stamp=$date->getTimestamp();
    if($stamp<=time()||$stamp>time()+10*366*86400)throw new DomainException('The trial end must be in the future and within ten years.');
    return $stamp;
}
function oplen_subscription_save(PDO $pdo,array $admin,array $input): void {
    $id=filter_var($input['customer_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $kind=(string)($input['access_kind']??'');
    $reason=trim((string)($input['reason']??''));
    if(!$id||!in_array($kind,['trial','courtesy','paid'],true))throw new DomainException('Choose a valid company and access type.');
    if(strlen($reason)<3||strlen($reason)>500)throw new DomainException('Provide a reason between 3 and 500 characters.');
    $end=$kind==='trial'?oplen_subscription_end((string)($input['trial_end']??'')):null;
    $pdo->exec("CREATE TABLE IF NOT EXISTS oplen_subscription_audit (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT NOT NULL, admin_id VARCHAR(64) NOT NULL,
        old_access_kind VARCHAR(20) NOT NULL, new_access_kind VARCHAR(20) NOT NULL,
        old_trial_ends_at BIGINT, new_trial_ends_at BIGINT,
        reason VARCHAR(500) NOT NULL, created_at BIGINT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT id,status,access_kind,trial_ends_at FROM oplen_customers WHERE id=? FOR UPDATE');
        $q->execute([$id]);$customer=$q->fetch();
        if(!$customer||$customer['status']!=='active')throw new DomainException('Only companies with completed setup can be edited. Deleting companies cannot be restored here.');
        if(!hash_equals(oplen_subscription_version($customer),(string)($input['version']??'')))throw new DomainException('This subscription changed. Reload the page before editing.');
        $q=$pdo->prepare('UPDATE oplen_customers SET access_kind=?,trial_ends_at=? WHERE id=?');
        $q->execute([$kind,$end,$id]);
        $q=$pdo->prepare('INSERT INTO oplen_subscription_audit(customer_id,admin_id,old_access_kind,new_access_kind,old_trial_ends_at,new_trial_ends_at,reason,created_at) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([$id,$admin['id'],$customer['access_kind'],$kind,$customer['trial_ends_at'],$end,$reason,time()]);
        $pdo->commit();
    }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();throw $ex;}
}

