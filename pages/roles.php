<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/database.php';
$pdo=null;$err=null;$notice=null;$roles=[];$permissions=[];$matrix=[];
try{
  $pdo=oplen_pdo();
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $roleId=(int)($_POST['role_id']??0);
    if($roleId<=0)throw new RuntimeException('Invalid role.');
    $q=$pdo->prepare("SELECT code FROM admin_roles WHERE id=? LIMIT 1");$q->execute([$roleId]);$roleCode=$q->fetchColumn();
    if(!$roleCode)throw new RuntimeException('Role not found.');
    if($roleCode==='super_admin')throw new RuntimeException('Super Admin always keeps full access.');
    $selected=array_map('intval',$_POST['permissions']??[]);
    $pdo->beginTransaction();
    $q=$pdo->prepare("DELETE FROM role_permissions WHERE role_id=?");$q->execute([$roleId]);
    if($selected){$ins=$pdo->prepare("INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)");foreach($selected as $permissionId){if($permissionId>0)$ins->execute([$roleId,$permissionId]);}}
    $pdo->commit();$notice='Permissions updated.';
  }
  $roles=$pdo->query("SELECT r.id,r.code,r.name,r.description,r.is_system,COUNT(DISTINCT a.id) member_count FROM admin_roles r LEFT JOIN admin_accounts a ON a.role_id=r.id AND a.status<>'disabled' GROUP BY r.id ORDER BY r.id")->fetchAll();
  $permissions=$pdo->query("SELECT id,code,name FROM permissions ORDER BY CASE WHEN code='admin.full' THEN 0 ELSE 1 END, code")->fetchAll();
  foreach($pdo->query("SELECT role_id,permission_id FROM role_permissions")->fetchAll() as $rp){$matrix[(int)$rp['role_id']][(int)$rp['permission_id']]=true;}
}catch(Throwable $e){if($pdo&&$pdo->inTransaction())$pdo->rollBack();$err=$e->getMessage();}
$groups=['Administration'=>['admin.full'],'Commercial'=>['commercial.read','commercial.write'],'Customers'=>['organizations.read','organizations.write','users.read','users.write'],'Oplen'=>['team.read','team.write'],'Business'=>['billing.read','billing.write'],'System'=>['system.read']];
?><header class="page-head"><div><span class="eyebrow">OPLEN</span><h1>Roles & access</h1><p>Control exactly what each internal Admin role can view and manage.</p></div></header>
<?php if($err):?><div class="health-error"><?=e($err)?></div><?php endif?><?php if($notice):?><div class="notice-success"><?=e($notice)?></div><?php endif?>
<?php if(!$roles||!$permissions):?><section class="panel"><div class="empty large"><strong>No access data available.</strong><p>System roles and permissions must exist in the database before this module can be configured.</p></div></section><?php else:?>
<div class="roles-grid">
<?php foreach($roles as $role):$super=$role['code']==='super_admin';?>
<form method="post" class="role-card"><input type="hidden" name="role_id" value="<?=e((string)$role['id'])?>">
  <div class="role-card-head"><div><span class="eyebrow"><?=e(strtoupper($role['code']))?></span><h2><?=e($role['name'])?></h2><p><?=e($role['description']?:'No description')?></p></div><span class="role-count"><?=e((string)$role['member_count'])?> admins</span></div>
  <?php foreach($groups as $group=>$codes):?><div class="permission-group"><strong><?=e($group)?></strong><?php foreach($permissions as $permission):if(!in_array($permission['code'],$codes,true))continue;$checked=$super||isset($matrix[(int)$role['id']][(int)$permission['id']]);?><label class="permission-row"><input type="checkbox" name="permissions[]" value="<?=e((string)$permission['id'])?>" <?=$checked?'checked':''?> <?=$super?'disabled':''?>><span><b><?=e($permission['name'])?></b><small><?=e($permission['code'])?></small></span></label><?php endforeach?></div><?php endforeach?>
  <div class="role-card-foot"><?php if($super):?><span class="locked-note">Full access is locked for Super Admin.</span><?php else:?><button class="button" type="submit">Save permissions</button><?php endif?></div>
</form>
<?php endforeach?>
</div>
<?php endif?>