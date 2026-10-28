<?php
declare(strict_types=1);
require_once __DIR__.'/organizations.php';
function oplen_lead_input(array $input):array {
    $limits=['company_name'=>190,'contact_name'=>190,'email'=>190,'team_size'=>50,'contact_role'=>160,'improvement_goal'=>10000,'next_action'=>255,'notes'=>10000];$data=[];
    foreach($limits as $field=>$limit){
        if(isset($input[$field])&&!is_string($input[$field]))throw new DomainException('Invalid field: '.$field);
        $value=trim($input[$field]??'');
        if(preg_match_all('/[\s\S]/u',$value)>$limit||!preg_match('//u',$value))throw new DomainException('Field too long or invalid: '.$field);
        $data[$field]=$value;
    }
    $data['email']=strtolower($data['email']);
    if($data['company_name']===''||!filter_var($data['email'],FILTER_VALIDATE_EMAIL))throw new DomainException('Company and a valid email are required.');
    $data['stage']=$input['stage']??'new';$data['source']=$input['source']??'manual';
    if(!in_array($data['stage'],['new','contacted','demo','trial','won','lost'],true))throw new DomainException('Choose a valid stage.');
    if(!in_array($data['source'],['manual','referral','outbound','event','other'],true))throw new DomainException('Choose a valid source.');
    return $data;
}
function oplen_lead_create(PDO $pdo,array $input,string $adminId,bool $allowed,string $token,string $expected):string {
    if(!$allowed)throw new DomainException('Your role cannot add leads.');
    if($expected===''||!hash_equals($expected,$token))throw new DomainException('Reload the form before saving.');
    $data=oplen_lead_input($input);
    $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&15)|64);$bytes[8]=chr((ord($bytes[8])&63)|128);
    $id=vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('INSERT INTO leads(id,company_name,contact_name,email,team_size,contact_role,improvement_goal,next_action,notes,stage,source,owner_admin_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$id,$data['company_name'],$data['contact_name']?:null,$data['email'],$data['team_size']?:null,$data['contact_role']?:null,$data['improvement_goal']?:null,$data['next_action']?:null,$data['notes']?:null,$data['stage'],$data['source'],$adminId]);
        $a=$pdo->prepare("INSERT INTO lead_activities(lead_id,admin_account_id,activity_type,body) VALUES(?,?,'lead_created',?)");$a->execute([$id,$adminId,'Lead added in Admin · Source: '.$data['source']]);
        $audit=$pdo->prepare("INSERT INTO audit_logs(admin_account_id,action,entity_type,entity_id,metadata_json) VALUES(?,'lead_created','lead',?,?)");$audit->execute([$adminId,$id,json_encode(['source'=>$data['source'],'stage'=>$data['stage']],JSON_THROW_ON_ERROR)]);
        $pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
