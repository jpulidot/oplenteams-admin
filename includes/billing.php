<?php
declare(strict_types=1);
require_once __DIR__.'/organizations.php';

function oplen_admin_billing_missing(PDO $pdo,PDOException $e,string $table): bool {
    return (int)($e->errorInfo[1]??0)===1146||($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'&&str_contains($e->getMessage(),'no such table: '.$table));
}
function oplen_admin_billing_load(PDO $pdo): array {
    $customers=$pdo->query("SELECT id,company_name,slug,status,access_kind,trial_ends_at FROM oplen_customers WHERE status<>'deleted' ORDER BY created_at DESC,id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $sandbox=[];$events=[];$sandboxAvailable=true;$eventsAvailable=true;
    try{$sandbox=$pdo->query('SELECT customer_id,subscription_id,subscription_status,payment_status,seat_count,updated_at FROM oplen_billing_sandbox')->fetchAll(PDO::FETCH_ASSOC);}
    catch(PDOException $e){if(!oplen_admin_billing_missing($pdo,$e,'oplen_billing_sandbox'))throw $e;$sandboxAvailable=false;}
    try{$events=$pdo->query('SELECT event_id,event_type,processed_at FROM oplen_stripe_sandbox_events ORDER BY processed_at DESC,event_id DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);}
    catch(PDOException $e){if(!oplen_admin_billing_missing($pdo,$e,'oplen_stripe_sandbox_events'))throw $e;$eventsAvailable=false;}
    return compact('customers','sandbox','events','sandboxAvailable','eventsAvailable');
}
function oplen_admin_billing_monthly($seats): ?int {
    $seats=filter_var($seats,FILTER_VALIDATE_INT);
    if($seats===false||$seats<5||$seats>1000)return null;
    return 3900+max(0,$seats-5)*500;
}
function oplen_admin_billing_model(array $data,int $now): array {
    $byCustomer=[];
    foreach($data['sandbox'] as $s)$byCustomer[(int)$s['customer_id']]=$s;
    $rows=[];$courtesy=0;$manualPaid=0;$subscriptions=0;$testMonthly=0;$invalidValue=false;
    foreach($data['customers'] as $c){
        if($c['status']==='deleted')continue;
        $s=$byCustomer[(int)$c['id']]??null;
        $sub=$s?(string)($s['subscription_id']??''):'';
        $hasSub=(bool)preg_match('/^sub_[A-Za-z0-9]+$/D',$sub);
        $monthly=$s?oplen_admin_billing_monthly($s['seat_count']):null;
        if($c['status']==='active'){
            if($c['access_kind']==='courtesy')$courtesy++;
            if($c['access_kind']==='paid')$manualPaid++;
            if($hasSub)$subscriptions++;
            if($hasSub&&$s['subscription_status']==='active'&&$s['payment_status']==='paid'){
                if($monthly===null)$invalidValue=true;else $testMonthly+=$monthly;
            }
        }
        $rows[]=['customer'=>$c,'sandbox'=>$s,'hasSubscription'=>$hasSub,'monthlyCents'=>$monthly,'access'=>oplen_registry_access($c,$now)];
    }
    if(!$data['sandboxAvailable']||$invalidValue)$testMonthly=null;
    return compact('rows','courtesy','manualPaid','subscriptions','testMonthly');
}
function oplen_admin_billing_money(?int $cents): string {return $cents===null?'—':'$'.number_format($cents/100,2);}
