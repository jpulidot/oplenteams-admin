<?php
require __DIR__.'/../includes/organizations.php';
function check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$now=time();
$c=['status'=>'active','access_kind'=>'trial','trial_ends_at'=>$now+60];
check(oplen_registry_access($c,$now)==='Trial','Trial');
$c['trial_ends_at']=$now;
check(oplen_registry_access($c,$now)==='Read only','Read-only boundary');
check(oplen_registry_access($c,$now+30*86400)==='Blocked','Blocked boundary');
check(oplen_registry_access($c,$now+60*86400)==='Pending deletion','Deletion boundary');
$c['access_kind']='courtesy';check(oplen_registry_access($c,$now+90*86400)==='Full access','Courtesy does not expire with trial');
$c['status']='deleting';check(oplen_registry_access($c,$now)==='Deletion in progress','Deleting status never grants full access');
foreach(['2026-02-30T12:00','invalid','2000-01-01T00:00','2099-01-01T00:00'] as $bad){
    try{oplen_subscription_end($bad);throw new RuntimeException('Invalid date accepted');}catch(DomainException $ex){}
}
$date=(new DateTimeImmutable('+2 days',new DateTimeZone('America/Mexico_City')))->format('Y-m-d\\TH:i');
check(oplen_subscription_end($date)>$now,'Valid date');
$a=['id'=>1,'status'=>'active','access_kind'=>'trial','trial_ends_at'=>123];
$b=$a;$b['access_kind']='courtesy';
check(oplen_subscription_version($a)!==oplen_subscription_version($b),'Concurrent subscription change detected');
echo "Subscription checks passed.\n";
