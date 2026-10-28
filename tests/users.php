<?php
require __DIR__.'/../includes/users.php';
function users_check(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE oplen_customers(id INT,company_name TEXT,slug TEXT,status TEXT,access_kind TEXT,trial_ends_at INT)');
$db->exec('CREATE TABLE oplen_tenants(slug TEXT,status TEXT,config_path TEXT)');
$db->exec("INSERT INTO oplen_customers VALUES(1,'One','one','active','courtesy',NULL),(2,'Two','two','active','trial',1),(3,'Deleted','deleted','deleted','paid',NULL),(4,'Deleting','deleting','deleting','paid',NULL),(5,'Pending','pending','active','trial',1)");
$db->exec("INSERT INTO oplen_tenants VALUES('one','active','one.php'),('two','active','two.php'),('deleted','active','deleted.php'),('deleting','active','deleting.php'),('pending','provisioning','pending.php')");
$companies=oplen_users_companies($db);users_check(count($companies)===2,'Only completed active tenants listed');
$tenant=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$tenant->exec('CREATE TABLE cp_users(id INT,name TEXT,email TEXT,role TEXT,access_level TEXT,department TEXT,job_title TEXT,is_active INT,password_hash TEXT,permissions_json TEXT)');
$tenant->exec("INSERT INTO cp_users VALUES(1,'Ana','ana@example.com','admin','c_level','Ops','COO',1,'SECRET','SECRET'),(2,'Luis','luis@example.com','collaborator','collaborator','Ops','Analyst',0,'SECRET','SECRET')");
$connector=fn($c)=>$tenant;
$a=oplen_users_read($companies[0],$connector);$b=oplen_users_read($companies[1],$connector);
users_check($a['available']&&count($a['rows'])===2,'Enabled and deactivated accounts loaded');
users_check($a['rows'][0]['identity']!==$b['rows'][0]['identity'],'Same tenant-local ID and email remain separate identities');
users_check(!str_contains(json_encode($a),'SECRET'),'No password or permission data exposed');
users_check(count(oplen_users_filter($a['rows'],'','inactive'))===1,'Inactive filter');
users_check(count(oplen_users_filter($a['rows'],'ANA@','active'))===1,'Case-insensitive email search');
users_check(count(oplen_users_filter($a['rows'],'Luis','active'))===0,'Search and status filters combine');
users_check(!$a['rows'][1]['is_active'],'Deactivated preserved');
$failed=oplen_users_read($companies[0],fn($c)=>throw new RuntimeException('Disconnected'));
users_check(!$failed['available'],'Failure is unavailable, not confirmed zero accounts');
users_check((int)$tenant->query('SELECT COUNT(*) FROM cp_users')->fetchColumn()===2,'Reads preserve records');
$root=sys_get_temp_dir().'/oplen-users-'.bin2hex(random_bytes(8));mkdir($root);
$outside=$root.'-outside.php';file_put_contents($outside,"<?php throw new RuntimeException('EXECUTED');");
try{oplen_users_config($outside,$root);throw new RuntimeException('Outside config accepted');}catch(RuntimeException $e){users_check($e->getMessage()==='Tenant configuration unavailable','Outside path rejected before execution');}finally{unlink($outside);rmdir($root);}
echo "Customer directory isolation, filters, preserved records and unavailable sources passed.\n";

$all=oplen_users_directory($companies,$connector);
users_check($all['available']&&count($all['rows'])===4&&$all['loaded']===2&&!$all['failed'],'All companies aggregates every loaded tenant');
users_check(count(array_unique(array_column($all['rows'],'identity')))===4,'Combined directory preserves tenant-local identities');
users_check(count(oplen_users_filter($all['rows'],'ana@','active'))===2,'Search applies across companies');
$partial=oplen_users_directory($companies,fn($c)=>$c['slug']==='one'?$tenant:throw new RuntimeException('Offline'));
users_check($partial['available']&&count($partial['rows'])===2&&$partial['loaded']===1&&count($partial['failed'])===1,'Partial results report failed company coverage');
$timeout=oplen_users_directory($companies,$connector,0.0);
users_check(!$timeout['available']&&count($timeout['failed'])===2,'Request budget never reports unloaded companies as empty');
echo "All-company aggregation and partial coverage passed.\n";
