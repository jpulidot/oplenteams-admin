<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/overview.php';
$checkedAt=new DateTimeImmutable('now',new DateTimeZone(oplen_admin_timezone()));
$checks=[]; $db=null; $dbError=null; $latency=null; $dbVersion='—'; $schemaTables=0; $adminUsers=0; $errors24=0;
$start=microtime(true);
try{
  $db=oplen_pdo(); $db->query('SELECT 1')->fetchColumn(); $latency=(int)round((microtime(true)-$start)*1000);
  $dbVersion=(string)$db->query('SELECT VERSION()')->fetchColumn();
  $schemaTables=(int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('admin_roles','permissions','role_permissions','team_members','admin_accounts','leads','lead_activities','organizations','customer_accounts','subscriptions','audit_logs')")->fetchColumn();
  $adminUsers=(int)$db->query("SELECT COUNT(*) FROM admin_accounts WHERE status='active'")->fetchColumn();
  $errors24=(int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= NOW()-INTERVAL 24 HOUR AND (action LIKE '%error%' OR action LIKE '%fail%')")->fetchColumn();
  $checks[]=['Database','Operational',$latency.' ms','ok'];
}catch(Throwable $ex){$dbError='Administrative database connection unavailable.';$checks[]=['Database','Offline','Connection failed','bad'];}
$checks[]=['Admin application','Operational','PHP '.PHP_VERSION,'ok'];
$checks[]=['DB schema',$schemaTables>=11?'Installed':'Incomplete',$schemaTables.'/11 core tables',$schemaTables>=11?'ok':'warn'];
$environment=(string)(oplen_env()['APP_ENV']??'Not configured');
$checks[]=['Environment',$environment,'Configured deployment','warn'];
$checks[]=['Admin accounts',$db?$adminUsers.' active':'Unavailable','Database',$db?'ok':'bad'];
$checks[]=['Errors · 24h',$db?(string)$errors24:'Unavailable','Audit log',$db&&$errors24===0?'ok':($db?'warn':'bad')];
$healthy=count(array_filter($checks,fn($c)=>$c[3]==='ok')); $total=count($checks); $all=$healthy===$total;
?>
<header class="pagehead"><div><div class="ey">Infrastructure</div><h1>System health</h1><p class="muted">Live checks for the Oplen Admin environment.</p></div></header>
<section class="health-hero"><div><span class="health-dot <?= $all?'health-ok':'health-warn'?>"></span><div><strong><?= $all?'All systems operational':'System attention required'?></strong><p class="muted"><?= $healthy ?>/<?= $total ?> checks healthy · Last checked <?= e($checkedAt->format('d M Y · g:i:s A')) ?></p></div></div><a class="ghostbtn" href="<?=e(page_url('system'))?>">Refresh checks</a></section>
<section class="health-grid">
<?php foreach($checks as $c):?><article class="health-card"><div class="health-card-top"><span><?=e($c[0])?></span><span class="health-badge <?=e($c[3])?>"><?=e($c[1])?></span></div><strong><?=e($c[2])?></strong></article><?php endforeach?>
</section>
<section class="block"><div class="titleline"><div><div class="ey">Runtime</div><h2>Environment details</h2></div></div><div class="health-table"><div><span>Admin version</span><strong><?=e(OPLEN_ADMIN_VERSION)?></strong></div><div><span>PHP</span><strong><?=e(PHP_VERSION)?></strong></div><div><span>Database engine</span><strong><?=e($dbVersion)?></strong></div><div><span>Schema coverage</span><strong><?=e((string)$schemaTables)?> / 11 tables</strong></div><div><span>Environment</span><strong><?=e($environment)?></strong></div><div><span>Host</span><strong>Hostinger</strong></div><div><span>Last deploy marker</span><strong><?=e(OPLEN_LAST_UPDATE)?></strong></div></div><?php if($dbError):?><div class="health-error">Database detail: <?=e($dbError)?></div><?php endif?></section>
