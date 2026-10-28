<?php
require_once __DIR__.'/../includes/overview.php';
$checkedAt=new DateTimeImmutable('now',new DateTimeZone(oplen_admin_timezone()));
$stats=null;$users=null;$admins=null;$pipeline=null;$messages=[];
try{
    $db=oplen_pdo();
    $admins=(int)$db->query("SELECT COUNT(*) FROM admin_accounts a JOIN team_members t ON t.id=a.team_member_id WHERE a.status='active' AND t.status='active'")->fetchColumn();
}catch(Throwable $ex){$messages[]='Internal account counts are unavailable.';error_log('Oplen overview: admin count unavailable.');}
try{
    $db=$db??oplen_pdo();
    $pipeline=$db->query('SELECT stage,COUNT(*) total FROM leads GROUP BY stage')->fetchAll(PDO::FETCH_KEY_PAIR);
}catch(Throwable $ex){$messages[]='Commercial pipeline is unavailable.';error_log('Oplen overview: pipeline unavailable.');}
try{
    if(oplen_organizations_allowed($admin)){
        $registry=oplen_registry_pdo();
        $customers=$registry->query("SELECT status,access_kind,trial_ends_at,slug FROM oplen_customers WHERE status<>'deleted'")->fetchAll();
        $stats=oplen_overview_customers($customers,time());
        try{$users=oplen_overview_users($registry,$customers);}catch(Throwable $ex){$messages[]='Customer user total is unavailable because one or more company databases could not be checked.';error_log('Oplen overview: tenant count unavailable.');}
    }else{$messages[]='Your role does not have access to organization metrics.';}
}catch(Throwable $ex){$messages[]='Customer registry is unavailable. Check the private platform configuration.';error_log('Oplen overview: registry unavailable.');}
$checks=oplen_overview_health();
$checks=['Website'=>$checks['Website'],'Admin'=>['status'=>'Responding','detail'=>'Current authenticated request','ok'=>true],'App'=>$checks['App'],'Demo'=>$checks['Demo']];
$stages=['new'=>'New','contacted'=>'Contacted','demo'=>'Demo','trial'=>'Trial','won'=>'Won','lost'=>'Lost'];
$open=$pipeline===null?null:array_sum(array_map(fn($s)=>(int)($pipeline[$s]??0),['new','contacted','demo','trial']));
?>
<header class="page-head"><div><span class="eyebrow">CONTROL CENTER</span><h1>Overview</h1><p>Live organization and commercial data · Checked <?=e($checkedAt->format('d M Y · H:i:s'))?> <?=e($checkedAt->getTimezone()->getName())?></p></div><a class="button" href="<?=e(page_url('overview'))?>">Refresh</a></header>
<?php foreach($messages as $message):?><div class="health-error"><?=e($message)?></div><?php endforeach?>
<section class="metrics">
<article><span>MRR · USD</span><strong>—</strong><small>Live billing not connected · Sandbox and manual Paid records excluded</small></article>
<article><span>Organizations</span><strong><?=$stats===null?'—':$stats['registered']?></strong><small><?=$stats===null?'Unavailable':$stats['active'].' setup completed · '.$stats['trials'].' in trial'?></small></article>
<article><span>Customer users</span><strong><?=$users===null?'—':$users?></strong><small>Active accounts in companies with completed setup</small></article>
<article><span>Oplen admins</span><strong><?=$admins===null?'—':$admins?></strong><small>Active accounts and active internal members</small></article>
</section>
<?php if($stats!==null):?><p class="muted overview-note"><?=$stats['courtesy']?> complimentary · <?=$stats['paid']?> manually marked Paid · Deleted companies excluded</p><?php endif?>
<section class="grid-2">
<article class="panel"><span class="eyebrow">COMMERCIAL</span><h2>Pipeline</h2>
<?php if($pipeline===null):?><div class="empty"><strong>Pipeline unavailable.</strong><p>The lead database could not be read.</p></div>
<?php elseif(array_sum($pipeline)===0):?><div class="empty"><strong>No leads registered yet.</strong><p>Confirmed from the commercial database.</p></div>
<?php else:?><p class="muted"><?=$open?> open opportunities · <?=array_sum($pipeline)?> total leads</p><div class="overview-stages"><?php foreach($stages as $key=>$label):?><div><span><?=e($label)?></span><strong><?=(int)($pipeline[$key]??0)?></strong></div><?php endforeach?></div><?php endif?>
<p><a class="ghostbtn" href="<?=e(page_url('pipeline'))?>">Open pipeline →</a> <a class="ghostbtn" href="<?=e(page_url('leads'))?>">All leads</a></p>
</article>
<article class="panel"><span class="eyebrow">PLATFORM</span><h2>Availability</h2>
<?php foreach($checks as $name=>$check):?><div class="health-row"><span><i class="<?=$check['ok']?'':'muted-dot'?>"></i><?=e($name)?></span><span class="overview-check"><b><?=e($check['status'])?></b><small><?=e($check['detail'])?></small></span></div><?php endforeach?>
<p class="muted overview-note">HTTP reachability only. Does not verify login, saving or every application function.</p><a class="ghostbtn" href="<?=e(page_url('system'))?>">Database and runtime checks →</a>
</article></section>
