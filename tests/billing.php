<?php
require __DIR__.'/../includes/billing.php';
function billing_check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE oplen_customers(id INT,company_name TEXT,slug TEXT,status TEXT,access_kind TEXT,trial_ends_at BIGINT,created_at BIGINT)');
$pdo->exec("INSERT INTO oplen_customers VALUES(1,'Trial','one','active','trial',2000,1),(2,'Courtesy','two','active','courtesy',NULL,2),(3,'Manual','three','active','paid',NULL,3),(4,'Deleted','four','deleted','paid',NULL,4),(5,'Deleting','five','deleting','paid',NULL,5)");
$data=oplen_admin_billing_load($pdo);
$model=oplen_admin_billing_model($data,1000);
billing_check(!$data['sandboxAvailable']&&!$data['eventsAvailable'],'Missing tables must mean uninitialized, not empty confirmed billing');
billing_check($model['testMonthly']===null,'Unavailable Sandbox cannot show zero revenue');
billing_check(count($model['rows'])===4,'Deleted companies excluded');
billing_check($model['manualPaid']===1&&$model['courtesy']===1,'Only completed active setups classified');
$pdo->exec('CREATE TABLE oplen_billing_sandbox(customer_id INT,subscription_id TEXT,subscription_status TEXT,payment_status TEXT,seat_count INT,updated_at INT)');
$pdo->exec('CREATE TABLE oplen_stripe_sandbox_events(event_id TEXT,event_type TEXT,processed_at INT)');
$pdo->exec("INSERT INTO oplen_billing_sandbox VALUES(1,'sub_one','active','paid',7,100),(2,'sub_two','past_due','unpaid',5,101),(4,'sub_four','active','paid',10,102),(5,'sub_five','active','paid',10,103),(99,'sub_orphan','active','paid',5,104)");
$pdo->exec("INSERT INTO oplen_stripe_sandbox_events VALUES('evt_one','invoice.paid',100),('evt_two','invoice.payment_failed',200)");
$data=oplen_admin_billing_load($pdo);$model=oplen_admin_billing_model($data,1000);
billing_check($model['testMonthly']===4900,'Only eligible active paid Sandbox plans count in simulated value');
billing_check($model['subscriptions']===2,'Deleting, deleted and orphan subscriptions excluded from active setup count');
billing_check($data['events'][0]['event_id']==='evt_two','Event history uses processing order');
billing_check(oplen_admin_billing_monthly(5)===3900&&oplen_admin_billing_monthly(6)===4400,'Included seats and extra seats use current Sandbox plan');
billing_check(oplen_admin_billing_monthly(4)===null&&oplen_admin_billing_monthly(1001)===null,'Invalid seat records cannot manufacture plan values');
$pdo->exec('UPDATE oplen_billing_sandbox SET seat_count=1 WHERE customer_id=1');
billing_check(oplen_admin_billing_model(oplen_admin_billing_load($pdo),1000)['testMonthly']===null,'Invalid contributing plan makes aggregate unavailable');
$pdo->exec('DROP TABLE oplen_customers');
try{oplen_admin_billing_load($pdo);throw new RuntimeException('Registry failure hidden');}catch(PDOException $e){}
echo "Billing availability, classification, history and Sandbox value checks passed.\n";
