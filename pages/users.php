<?php
require_once __DIR__.'/../includes/users.php';
$usersError=null;$usersAllowed=false;$companies=[];$company=null;$data=null;
try{$usersAllowed=oplen_organizations_allowed($admin,'users.read');}catch(Throwable $e){$usersError='Your user-directory permissions could not be verified.';}
if(!$usersAllowed){echo '<section class="panel"><h1>Users</h1><p>'.e($usersError??'Your role does not have access to customer users.').'</p></section>';return;}
$search=is_string($_GET['q']??null)?substr(trim($_GET['q']),0,100):'';
$slug=is_string($_GET['company']??null)?$_GET['company']:'';
$status=is_string($_GET['status']??null)&&in_array($_GET['status'],['active','inactive'],true)?$_GET['status']:'all';
try{
    $companies=oplen_users_companies(oplen_registry_pdo());
    if($slug!=='')foreach($companies as $candidate)if($candidate['slug']===$slug){$company=$candidate;break;}
    if($slug!==''&&!$company)$usersError='That company is unavailable or its setup is not complete. Choose another company.';
    if(!$usersError&&$companies){
        $data=oplen_users_directory($company?[$company]:$companies);
        if($data['failed'])$usersError=($data['available']?'Partial results. Counts include loaded companies only.':'Accounts could not be loaded; counts are unavailable.').' Could not load within this request: '.implode(', ',$data['failed']).'. Try selecting a company or check its database connection and schema.';
    }
}catch(Throwable $e){$usersError='The company registry is unavailable. Please try again later.';error_log('Oplen admin users: registry read unavailable.');}
$rows=$data&&$data['available']?oplen_users_filter($data['rows'],$search,$status):[];
$checkedAt=new DateTimeImmutable('now',new DateTimeZone('America/Mexico_City'));
?>
<header class="page-head"><div><span class="eyebrow">CUSTOMERS</span><h1>Users</h1><p>Customer accounts by company · Checked <?=e($checkedAt->format('d M Y · H:i:s'))?> CDMX</p></div></header>
<form method="get" class="users-filters panel"><input type="hidden" name="page" value="users">
<label>Company<select name="company"><option value="" <?=$slug===''?'selected':''?>>All companies</option><?php foreach($companies as $c):?><option value="<?=e($c['slug'])?>" <?=$company&&$company['slug']===$c['slug']?'selected':''?>><?=e($c['company_name'])?> · <?=e($c['slug'])?></option><?php endforeach?></select></label>
<label>Name or email<input name="q" maxlength="100" value="<?=e($search)?>" placeholder="Search accounts"></label>
<label>Account status<select name="status"><?php foreach(['all'=>'All accounts','active'=>'Enabled','inactive'=>'Deactivated'] as $value=>$label):?><option value="<?=$value?>" <?=$status===$value?'selected':''?>><?=$label?></option><?php endforeach?></select></label><button class="ghostbtn" type="submit">Apply filters</button></form>
<?php if($usersError):?><div class="health-error" role="alert"><?=e($usersError)?></div><?php endif?>
<?php if($company):?><p class="muted">Company access: <strong><?=e(oplen_registry_access($company,time()))?></strong>. Enabled accounts can sign in only when company access permits it. This directory is read-only; deactivated accounts retain their history.</p><?php endif?>
<?php if(!$company):?><p class="muted">Enabled accounts can sign in only when their company access permits it. This directory is read-only; deactivated accounts retain their history.</p><?php endif?>
<?php if($data&&$data['available']):$enabled=count(array_filter($data['rows'],fn($row)=>$row['is_active']));?>
<section class="metrics"><article><span><?=$data['failed']?'Loaded accounts · partial':'Accounts'?></span><strong><?=count($data['rows'])?></strong><small><?=e($company['company_name']??'All companies')?></small></article><article><span>Enabled</span><strong><?=$enabled?></strong><small>Account status, independent of company access</small></article><article><span>Deactivated</span><strong><?=count($data['rows'])-$enabled?></strong><small>History preserved</small></article><article><span>Matching filters</span><strong><?=count($rows)?></strong><small><?=$data['loaded']?> / <?=$data['total']?> companies loaded</small></article></section>
<section class="panel table-panel"><div class="table-head"><div><h2><?=e($company['company_name']??'All companies')?></h2><p>Customer accounts · Internal Oplen staff are managed in Team</p></div></div>
<?php if(!$rows):?><div class="empty"><strong><?=count($data['rows'])?'No accounts match your filters.':'No user accounts in the loaded companies.'?></strong></div><?php else:?><div class="users-table"><table><thead><tr><th>Person</th><th>Company / access</th><th>Role / access level</th><th>Department / job title</th><th>Account status</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><strong><?=e($row['name'])?></strong><br><span class="muted"><?=e($row['email'])?></span></td><td><strong><?=e($row['company_name'])?></strong><br><span class="muted"><?=e($row['company_slug'])?> · <?=e($row['company_access'])?></span></td><td><?=e(ucfirst($row['role']))?><br><span class="muted"><?=e(ucwords(str_replace('_',' ',$row['access_level'])))?></span></td><td><?=e($row['department']?:'—')?><br><span class="muted"><?=e($row['job_title']?:'—')?></span></td><td><span class="stage-pill"><?=$row['is_active']?'Enabled':'Deactivated'?></span></td></tr><?php endforeach?></tbody></table></div><?php endif?></section>
<?php elseif(!$usersError):?><section class="panel"><h2>No completed company setups</h2><p>Accounts become available after company provisioning is complete.</p></section><?php endif?>
