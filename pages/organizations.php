<?php
require_once __DIR__.'/../includes/organizations.php';
require_once __DIR__.'/../includes/company-deletion.php';
$customers=[];$registryError=null;$allowed=false;$canEdit=false;$canDelete=false;
try{$allowed=oplen_organizations_allowed($admin);$canEdit=$allowed&&oplen_organizations_allowed($admin,'billing.write');$canDelete=$allowed&&oplen_organizations_allowed($admin,'organizations.delete');}catch(Throwable $ex){$registryError='Could not verify your administrative permissions.';error_log('Oplen organizations: permission lookup failed.');}
if(!$allowed){
    http_response_code($registryError?503:403);
    echo '<section class="panel"><h1>Organizations</h1><p>'.e($registryError??'You do not have permission to view organizations.').'</p></section>';
    return;
}
try{
    $pdo=oplen_registry_pdo();
    $customers=$pdo->query("SELECT id,company_name,slug,admin_name,email,status,access_kind,created_at,activated_at,trial_ends_at,mail_status FROM oplen_customers WHERE status<>'deleted' ORDER BY created_at DESC,id DESC")->fetchAll();
}catch(Throwable $ex){
    http_response_code(503);$registryError='The central customer registry could not be loaded. Check the private platform configuration and database access.';
    error_log('Oplen organizations: central registry lookup failed.');
}
$now=time();
$deleteId=filter_var($_GET['delete']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($deleteId&&!$registryError){
    $target=null;foreach($customers as $c)if((int)$c['id']===$deleteId){$target=$c;break;}
    if(!$canDelete||!$target||!in_array($target['status'],['active','pending'],true)){http_response_code(403);echo '<section class="panel"><h1>Deletion unavailable</h1><a class="ghostbtn" href="'.e(page_url('organizations')).'">Back to organizations</a></section>';return;}
    $identifier=$target['slug']?:$target['company_name'];
?>
<header class="page-head"><div><span class="eyebrow">CUSTOMERS / DELETE</span><h1>Delete company</h1><p><?=e($target['company_name'])?> · <?=e($target['email'])?></p></div><a class="ghostbtn" href="<?=e(page_url('organizations'))?>">← Organizations</a></header>
<?php if(!empty($subscriptionError)):?><div class="health-error"><?=e($subscriptionError)?></div><?php endif?>
<section class="panel subscription-card deletion-card"><h2>This permanently deletes the company</h2><p>Access is blocked immediately. The automatic cleanup removes its database, configuration and uploaded files during the next hourly cleanup. Its Stripe Sandbox subscription is canceled first. This action cannot be undone.</p>
<form method="post" class="subscription-form">
<input type="hidden" name="action" value="delete-company"><input type="hidden" name="csrf" value="<?=e($_SESSION['subscription_csrf'])?>"><input type="hidden" name="customer_id" value="<?=e((string)$target['id'])?>"><input type="hidden" name="version" value="<?=e(oplen_deletion_version($target))?>">
<label>Type <?=e($identifier)?> to confirm<input name="confirmation" required autocomplete="off"></label>
<label>Reason<textarea name="reason" required minlength="3" maxlength="500" rows="3"><?=e((string)($_POST['reason']??''))?></textarea><small>Recorded with your administrator account.</small></label>
<div class="subscription-actions"><a class="ghostbtn" href="<?=e(page_url('organizations'))?>">Keep company</a><button class="button danger-button" type="submit">Permanently delete company</button></div>
</form></section>
<?php return;
}
$selected=null;
$selectedId=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($selectedId)foreach($customers as $customer)if((int)$customer['id']===$selectedId){$selected=$customer;break;}
if($selectedId&&!$registryError){
    if(!$selected||!$canEdit||$selected['status']!=='active'){
        http_response_code(403);echo '<section class="panel"><h1>Subscription unavailable</h1><a class="ghostbtn" href="'.e(page_url('organizations')).'">Back to organizations</a></section>';return;
    }
    $endValue=$selected['trial_ends_at']?(new DateTimeImmutable('@'.(int)$selected['trial_ends_at']))->setTimezone(new DateTimeZone('America/Mexico_City'))->format('Y-m-d\\TH:i'):'';
?>
<header class="page-head"><div><span class="eyebrow">CUSTOMERS / SUBSCRIPTION</span><h1>Edit subscription</h1><p><?=e($selected['company_name'])?> · <?=e($selected['email'])?></p></div><a class="ghostbtn" href="<?=e(page_url('organizations'))?>">← Organizations</a></header>
<?php if(!empty($subscriptionError)):?><div class="health-error"><?=e($subscriptionError)?></div><?php endif?>
<div class="subscription-layout">
<section class="panel subscription-card"><span class="eyebrow">ACCESS SETTINGS</span><h2>Subscription</h2>
<form method="post" class="subscription-form">
<input type="hidden" name="csrf" value="<?=e($_SESSION['subscription_csrf'])?>">
<input type="hidden" name="customer_id" value="<?=e((string)$selected['id'])?>">
<input type="hidden" name="version" value="<?=e(oplen_subscription_version($selected))?>">
<label>Access type<select name="access_kind"><?php foreach(['trial'=>'Trial','courtesy'=>'Complimentary','paid'=>'Paid — manual record'] as $key=>$label):?><option value="<?=e($key)?>" <?=$selected['access_kind']===$key?'selected':''?>><?=e($label)?></option><?php endforeach?></select><small>Choose how this company accesses Oplen.</small></label>
<label>Trial end date<input type="datetime-local" name="trial_end" value="<?=e($endValue)?>"><small>Mexico City time. Applies only to Trial.</small></label>
<label>Reason for change<textarea name="reason" required minlength="3" maxlength="500" rows="3" placeholder="For example: extend the trial after onboarding."><?=e((string)($_POST['reason']??''))?></textarea><small>Saved in the history with your administrator account.</small></label>
<div class="subscription-actions"><a class="ghostbtn" href="<?=e(page_url('organizations'))?>">Cancel</a><button type="submit" class="button">Save changes</button></div>
</form></section>
<aside class="panel subscription-summary"><span class="eyebrow">CURRENT SUBSCRIPTION</span><h2><?=e($selected['company_name'])?></h2>
<dl><div><dt>Access</dt><dd><?=e(oplen_registry_access($selected,$now))?></dd></div><div><dt>Plan</dt><dd><?=e(ucfirst($selected['access_kind']))?></dd></div><div><dt>Trial ends</dt><dd><?=e(oplen_registry_date($selected['trial_ends_at']))?></dd></div></dl>
<p>Complimentary and Paid provide full access. A manual Paid record does not charge money.</p></aside></div>
<?php return;
}
?>
<header class="page-head"><div><span class="eyebrow">CUSTOMERS</span><h1>Organizations</h1><p>Companies registered in Oplen, including those completing activation and setup.</p></div></header>
<?php if(!empty($subscriptionError)):?><div class="health-error"><?=e($subscriptionError)?></div><?php endif?>
<?php if(isset($_SESSION['subscription_notice'])):?><div class="notice-success"><?=e($_SESSION['subscription_notice'])?></div><?php unset($_SESSION['subscription_notice']);endif?>
<?php if($registryError):?><div class="health-error"><?=e($registryError)?></div>
<?php else:?>
<section class="metrics">
<article><span>Registered</span><strong><?=count($customers)?></strong><small>Customer registry</small></article>
<article><span>Active</span><strong><?=count(array_filter($customers,fn($c)=>$c['status']==='active'))?></strong><small>Setup completed</small></article>
<article><span>Setup pending</span><strong><?=count(array_filter($customers,fn($c)=>in_array($c['status'],['pending','provisioning','failed'],true)))?></strong><small>Activation or provisioning</small></article>
<article><span>Trial</span><strong><?=count(array_filter($customers,fn($c)=>oplen_registry_access($c,$now)==='Trial'))?></strong><small>Full trial access</small></article>
</section>
<section class="panel table-panel"><div class="table-head"><div><h2>Organizations</h2><p>Live data from the central registry.</p></div></div>
<?php if(!$customers):?><div class="empty large"><strong>No companies registered yet.</strong><p>New registrations will appear here automatically.</p></div>
<?php else:?><div style="overflow:auto"><table><thead><tr><th>Company</th><th>Administrator</th><th>Setup status</th><th>Access</th><th>Plan</th><th>Registered</th><th>Trial ends</th><th>Actions</th></tr></thead><tbody>
<?php foreach($customers as $c):?><tr>
<td><strong><?=e($c['company_name'])?></strong><br><span class="muted"><?=e($c['slug']?:'URL pending')?></span></td>
<td><?=e($c['admin_name'])?><br><span class="muted"><?=e($c['email'])?></span></td>
<td><?=e(ucfirst($c['status']))?></td><td><?=e(oplen_registry_access($c,$now))?></td>
<td><?=e(ucfirst($c['access_kind']))?></td><td><?=e(oplen_registry_date($c['created_at']))?></td><td><?=e(oplen_registry_date($c['trial_ends_at']))?></td>
<td><div class="organization-actions"><?php if($canDelete&&in_array($c['status'],['active','pending'],true)):?><a class="ghostbtn danger-link" href="<?=e(page_url('organizations').'&delete='.(int)$c['id'])?>">Delete company</a><?php endif?><?php if($canEdit&&$c['status']==='active'):?><a class="ghostbtn" href="<?=e(page_url('organizations').'&id='.(int)$c['id'])?>">Edit subscription</a><?php else:?><span class="muted">—</span><?php endif?></div></td>
</tr><?php endforeach?></tbody></table></div><?php endif?></section>
<?php endif?>

