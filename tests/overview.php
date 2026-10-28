<?php
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/overview.php';
function overview_check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$now=1000;
$customers=[
 ['status'=>'active','access_kind'=>'trial','trial_ends_at'=>1001],
 ['status'=>'active','access_kind'=>'trial','trial_ends_at'=>1000],
 ['status'=>'active','access_kind'=>'courtesy','trial_ends_at'=>0],
 ['status'=>'active','access_kind'=>'paid','trial_ends_at'=>0],
 ['status'=>'pending','access_kind'=>'trial','trial_ends_at'=>1001],
 ['status'=>'deletion_requested','access_kind'=>'paid','trial_ends_at'=>0],
 ['status'=>'deleted','access_kind'=>'paid','trial_ends_at'=>0],
];
$s=oplen_overview_customers($customers,$now);
overview_check($s['registered']===6&&$s['active']===4,'Registration and completed setup must differ');
overview_check($s['trials']===1,'Expired trial must not count as current trial');
overview_check($s['courtesy']===1&&$s['paid']===1,'Deleted/deleting companies must not count as paid');
overview_check(oplen_overview_customers([],$now)['registered']===0,'Verified empty registry can show zero');
// A disconnected installation must render unavailable, not fabricated zero metrics.
$admin=['id'=>'fixture'];
ob_start();require __DIR__.'/../pages/overview.php';$html=ob_get_clean();
overview_check(str_contains($html,'Customer registry is unavailable'),'Registry failure is explicit');
overview_check(str_contains($html,'Pipeline unavailable.'),'Failed lead query cannot claim an empty pipeline');
overview_check(!str_contains($html,'<strong>0</strong>'),'Unavailable sources must not render zero');
overview_check(!str_contains($html,'Preparing'),'Old deployment placeholders removed');
overview_check(str_contains($html,'Sandbox and manual Paid records excluded'),'Manual access cannot manufacture MRR');
echo "Overview checks passed.\n";
