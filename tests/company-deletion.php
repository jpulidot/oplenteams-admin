<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/includes/company-deletion.php';
function check_delete(bool $ok,string $label): void{if(!$ok)throw new RuntimeException($label);}
function reject_delete(callable $fn,string $label): void{try{$fn();}catch(DomainException $e){return;}throw new RuntimeException($label);}
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE oplen_customers (id INTEGER PRIMARY KEY,email TEXT,admin_name TEXT,company_name TEXT,slug TEXT,status TEXT,access_kind TEXT,trial_ends_at INTEGER,deleted_at INTEGER,activation_version INTEGER DEFAULT 0)');
$pdo->exec('CREATE TABLE oplen_tenants (slug TEXT PRIMARY KEY,status TEXT)');
$pdo->exec('CREATE TABLE oplen_handoffs (tenant_slug TEXT)');$pdo->exec('CREATE TABLE oplen_activations (customer_id INTEGER)');
$pdo->exec("INSERT INTO oplen_customers(id,email,company_name,slug,status,access_kind) VALUES(1,'one@example.test','One','one','active','courtesy'),(2,'two@example.test','Two',NULL,'pending','trial'),(3,'three@example.test','Three','three','provisioning','trial')");
$pdo->exec("INSERT INTO oplen_tenants VALUES('one','active')");$pdo->exec("INSERT INTO oplen_handoffs VALUES('one')");$pdo->exec('INSERT INTO oplen_activations VALUES(1),(2)');
$one=$pdo->query('SELECT * FROM oplen_customers WHERE id=1')->fetch();$input=['customer_id'=>1,'version'=>oplen_deletion_version($one),'confirmation'=>'one','reason'=>'Remove test company'];
$bad=$input;$bad['confirmation']='other';reject_delete(fn()=>oplen_deletion_request($pdo,['id'=>99],$bad),'wrong confirmation');
$bad=$input;$bad['version']='stale';reject_delete(fn()=>oplen_deletion_request($pdo,['id'=>99],$bad),'stale version');
check_delete($pdo->query('SELECT status FROM oplen_customers WHERE id=1')->fetchColumn()==='active','rejections preserve company');
oplen_deletion_request($pdo,['id'=>99],$input);
check_delete($pdo->query('SELECT status FROM oplen_customers WHERE id=1')->fetchColumn()==='deletion_requested','queue active company');
check_delete($pdo->query("SELECT status FROM oplen_tenants WHERE slug='one'")->fetchColumn()==='deletion_requested','block tenant immediately');
check_delete((int)$pdo->query('SELECT COUNT(*) FROM oplen_handoffs')->fetchColumn()===0,'revoke handoffs');
check_delete((int)$pdo->query('SELECT admin_id FROM oplen_company_deletions WHERE customer_id=1')->fetchColumn()===99,'audit actor');
reject_delete(fn()=>oplen_deletion_request($pdo,['id'=>99],$input),'duplicate deletion');
$two=$pdo->query('SELECT * FROM oplen_customers WHERE id=2')->fetch();
oplen_deletion_request($pdo,['id'=>99],['customer_id'=>2,'version'=>oplen_deletion_version($two),'confirmation'=>'Two','reason'=>'Remove abandoned signup']);
check_delete($pdo->query('SELECT status FROM oplen_customers WHERE id=2')->fetchColumn()==='deleted','pending signup deleted without provisioning');
check_delete($pdo->query('SELECT email FROM oplen_customers WHERE id=2')->fetchColumn()==='deleted-2@invalid.example','email freed');
check_delete($pdo->query('SELECT status FROM oplen_company_deletions WHERE customer_id=2')->fetchColumn()==='completed','pending audit completed');
reject_delete(fn()=>oplen_deletion_request($pdo,['id'=>99],['customer_id'=>3,'reason'=>'test','confirmation'=>'three','version'=>'any']),'provisioning denied');
echo "Company deletion requests: confirmation, concurrency, access blocking, audit and pending signup tests passed.\n";
