<?php
require __DIR__.'/../includes/lead-create.php';
function lead_check(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);}
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE leads(id TEXT PRIMARY KEY,company_name TEXT,contact_name TEXT,email TEXT,team_size TEXT,contact_role TEXT,improvement_goal TEXT,next_action TEXT,notes TEXT,stage TEXT,source TEXT,owner_admin_id TEXT)');
$pdo->exec('CREATE TABLE lead_activities(lead_id TEXT,admin_account_id TEXT,activity_type TEXT,body TEXT)');
$pdo->exec('CREATE TABLE audit_logs(admin_account_id TEXT,action TEXT,entity_type TEXT,entity_id TEXT,metadata_json TEXT)');
$input=['company_name'=>' Test Co ','email'=>'ANA@EXAMPLE.COM','stage'=>'demo','source'=>'referral','notes'=>'Call notes'];
foreach([[false,'token','token'],[true,'wrong','token'],[true,'','']] as [$allowed,$token,$expected]){
 try{oplen_lead_create($pdo,$input,'admin',$allowed,$token,$expected);throw new RuntimeException('Guard bypassed');}catch(DomainException $e){}
}
lead_check((int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn()===0,'Permission and CSRF failures cannot write');
foreach([['email'=>'bad'],['stage'=>'invalid'],['source'=>'website'],['company_name'=>str_repeat('x',191)],['email'=>[]]] as $invalid){
 try{oplen_lead_create($pdo,array_replace($input,$invalid),'admin',true,'token','token');throw new RuntimeException('Invalid input accepted');}catch(DomainException $e){}
}
$id=oplen_lead_create($pdo,$input,'admin',true,'token','token');
$row=$pdo->query('SELECT * FROM leads')->fetch();
lead_check($row['id']===$id&&$row['company_name']==='Test Co'&&$row['email']==='ana@example.com','Validated fields persisted');
lead_check($row['stage']==='demo'&&$row['source']==='referral'&&$row['owner_admin_id']==='admin','Pipeline stage source and owner persisted');
lead_check((int)$pdo->query('SELECT COUNT(*) FROM lead_activities')->fetchColumn()===1&&(int)$pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===1,'Creation activity and audit saved');
$pdo->exec('DROP TABLE audit_logs');
try{oplen_lead_create($pdo,$input,'admin',true,'token','token');throw new RuntimeException('Audit failure ignored');}catch(PDOException $e){}
lead_check((int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn()===1&&(int)$pdo->query('SELECT COUNT(*) FROM lead_activities')->fetchColumn()===1,'Audit failure rolls back lead and history');
echo "Lead creation validation, authorization, CSRF and atomic history passed.\n";
