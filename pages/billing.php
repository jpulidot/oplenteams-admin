<?php
require_once __DIR__.'/../includes/billing.php';
$billingError=null;$billingAllowed=false;$billingData=null;$billingModel=null;
try{$billingAllowed=oplen_organizations_allowed($admin,'billing.read');}
catch(Throwable $e){$billingError='Your billing permissions could not be verified.';}
if(!$billingAllowed){http_response_code($billingError?503:403);echo '<section class="panel"><h1>Billing</h1><p>'.e($billingError??'Your role does not have access to billing.').'</p></section>';return;}
try{$billingData=oplen_admin_billing_load(oplen_registry_pdo());$billingModel=oplen_admin_billing_model($billingData,time());}
catch(Throwable $e){http_response_code(503);$billingError='Billing data is unavailable. Check the central registry connection and permissions.';error_log('Oplen admin billing: registry read failed.');}
$checkedAt=new DateTimeImmutable('now',new DateTimeZone('America/Mexico_City'));
?>
<header class="page-head"><div><span class="eyebrow">BUSINESS</span><h1>Billing</h1><p>Company access and Stripe Sandbox records · Checked <?=e($checkedAt->format('d M Y · H:i:s'))?> CDMX</p></div><a class="button" href="<?=e(page_url('billing'))?>">Refresh records</a></header>
<?php if($billingError):?><div class="health-error" role="alert"><?=e($billingError)?></div><?php return;endif?>
<section class="panel billing-mode"><span class="eyebrow">SANDBOX</span><h2>Test billing only</h2><p>Sandbox payments do not represent real revenue or change company access. Live MRR and actual collections are not connected.</p></section>
<section class="metrics">
<article><span>Live MRR · USD</span><strong>—</strong><small>Live billing not connected</small></article>
<article><span>Test monthly value · USD</span><strong><?=e(oplen_admin_billing_money($billingModel['testMonthly']))?></strong><small>Active Sandbox subscriptions with latest invoice marked paid; completed company setups only</small></article>
<article><span>Complimentary</span><strong><?=$billingModel['courtesy']?></strong><small>Full access without a recorded charge</small></article>
<article><span>Manual Paid access</span><strong><?=$billingModel['manualPaid']?></strong><small>Access classification, not proof of payment</small></article>
</section>
<?php if(!$billingData['sandboxAvailable']):?><div class="billing-note">Sandbox billing has not been initialized in this registry. Company access remains visible; payment and seat records are unavailable.</div><?php endif?>
<?php $search=substr(trim((string)($_GET['q']??'')),0,100);$rows=$billingModel['rows'];if($search!=='')$rows=array_values(array_filter($rows,fn($r)=>stripos($r['customer']['company_name'],$search)!==false||stripos((string)$r['customer']['slug'],$search)!==false));?>
<form method="get" class="billing-search"><input type="hidden" name="page" value="billing"><label for="billing-search">Find company</label><input id="billing-search" name="q" maxlength="100" value="<?=e($search)?>" placeholder="Company name or identifier"><button class="ghostbtn" type="submit">Search</button></form>
<section class="panel table-panel"><div class="table-head"><div><h2>Companies and subscriptions</h2><p><?=$billingModel['subscriptions']?> Sandbox subscriptions in completed setups · <?=count($rows)?> companies shown</p></div></div>
<?php if(!$rows):?><div class="empty"><strong><?=$search!==''?'No companies match your search.':'No companies registered yet.'?></strong></div>
<?php else:?><div class="billing-table"><table><thead><tr><th>Company / access</th><th>Sandbox subscription</th><th>Latest invoice · test</th><th>Seats · test</th><th>Monthly plan · test</th><th>Trial ends</th><th>Last billing update</th></tr></thead><tbody>
<?php foreach($rows as $row):$c=$row['customer'];$s=$row['sandbox'];$validSeats=$row['monthlyCents']!==null;?>
<tr><td><strong><?=e($c['company_name'])?></strong><br><span class="muted"><?=e($row['access'])?> · <?=e(ucfirst($c['access_kind']))?></span></td>
<td><?php if($row['hasSubscription']):?><span class="stage-pill"><?=e($s['subscription_status'])?></span><br><small class="muted"><?=e($s['subscription_id'])?></small><?php else:?><span class="muted"><?=$s?'No subscription recorded':($billingData['sandboxAvailable']?'No test checkout record':'Unavailable')?></span><?php endif?></td>
<td><?=e($row['hasSubscription']?ucfirst($s['payment_status']):'—')?></td>
<td><?php if($validSeats):?><?=(int)$s['seat_count']?> selected<br><small class="muted">5 included · <?=max(0,(int)$s['seat_count']-5)?> extra</small><?php else:?><span class="muted">—</span><?php endif?></td>
<td><?=e(oplen_admin_billing_money($row['monthlyCents']))?><br><small class="muted">USD / month · Plan value</small></td>
<td><?=e($c['access_kind']==='trial'?oplen_registry_date($c['trial_ends_at']):'—')?></td>
<td><?=e($s?oplen_registry_date($s['updated_at']):'—')?></td></tr>
<?php endforeach?></tbody></table></div><?php endif?></section>
<p class="muted billing-note">Plan value uses the current Sandbox schedule: US$39 for five seats plus US$5 per additional seat. Selected billing seats are not the active-user count. Renewal dates and invoice amounts are not available in these stored records. Last billing update and trial dates use Mexico City time.</p>
<section class="panel table-panel billing-events"><div class="table-head"><div><span class="eyebrow">SANDBOX HISTORY</span><h2>Processed Stripe events</h2><p>Latest 30 events across all companies. Event processing is not a receipt or proof of real payment.</p></div></div>
<?php if(!$billingData['eventsAvailable']):?><div class="empty"><strong>Event history has not been initialized.</strong></div>
<?php elseif(!$billingData['events']):?><div class="empty"><strong>No Sandbox events processed yet.</strong></div>
<?php else:?><div class="billing-table"><table><thead><tr><th>Event</th><th>Type</th><th>Processed</th></tr></thead><tbody><?php foreach($billingData['events'] as $event):?><tr><td><?=e($event['event_id'])?></td><td><?=e($event['event_type'])?></td><td><?=e(oplen_registry_date($event['processed_at']))?></td></tr><?php endforeach?></tbody></table></div><?php endif?></section>
