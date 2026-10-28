<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/database.php';
$pdo=null;$dbError=null;$lead=null;$activities=[];$leads=[];
try{
  $pdo=oplen_pdo();
  if(!empty($_GET['id'])){
    $q=$pdo->prepare("SELECT l.*,a.email owner_email,r.name owner_role FROM leads l LEFT JOIN admin_accounts a ON a.id=l.owner_admin_id LEFT JOIN admin_roles r ON r.id=a.role_id WHERE l.id=? LIMIT 1");
    $q->execute([(string)$_GET['id']]);$lead=$q->fetch();
    if($lead){$a=$pdo->prepare("SELECT la.*,aa.email admin_email FROM lead_activities la LEFT JOIN admin_accounts aa ON aa.id=la.admin_account_id WHERE la.lead_id=? ORDER BY la.created_at DESC,la.id DESC");$a->execute([$lead['id']]);$activities=$a->fetchAll();}
  } else {
    $leads=$pdo->query("SELECT id,company_name,contact_name,email,team_size,source,stage,next_action,created_at FROM leads ORDER BY created_at DESC LIMIT 250")->fetchAll();
  }
}catch(Throwable $e){$dbError=$e->getMessage();}
if(!empty($_GET['id'])):
?>
<header class="page-head"><div><span class="eyebrow">COMMERCIAL / LEAD</span><h1><?=e($lead['company_name']??'Lead')?></h1><p><?= $lead ? e(($lead['contact_name']?:$lead['email']).' · '.ucfirst($lead['stage'])) : 'Lead not found.' ?></p></div><a class="button" href="<?=page_url('leads')?>">← All leads</a></header>
<?php if(isset($_GET['created'])):?><div class="flash ok">Lead created.</div><?php endif?>
<?php if(isset($_GET['saved'])):?><div class="flash ok">Changes saved.</div><?php endif?><?php if(isset($_GET['error'])):?><div class="flash bad"><?=e((string)$_GET['error'])?></div><?php endif?>
<?php if($dbError):?><section class="panel"><div class="health-error"><?=e($dbError)?></div></section><?php elseif(!$lead):?><section class="panel"><div class="empty large"><strong>Lead not found.</strong></div></section><?php else:?>
<div class="lead-layout">
  <section class="lead-main">
    <section class="panel lead-summary"><div class="titleline"><div><span class="eyebrow">DETAIL</span><h2>Lead information</h2></div><span class="stage-pill stage-<?=e($lead['stage'])?>"><?=e(ucfirst($lead['stage']))?></span></div>
      <form class="lead-form" method="post" action="api/lead-manage.php"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=e($lead['id'])?>">
        <div class="form-grid"><label>Company<input name="company_name" required value="<?=e($lead['company_name'])?>"></label><label>Contact<input name="contact_name" value="<?=e($lead['contact_name']??'')?>"></label><label>Email<input type="email" name="email" required value="<?=e($lead['email'])?>"></label><label>Team size<input name="team_size" value="<?=e($lead['team_size']??'')?>"></label><label>Role<input name="contact_role" value="<?=e($lead['contact_role']??'')?>"></label><label>Stage<select name="stage"><?php foreach(['new','contacted','demo','trial','won','lost'] as $s):?><option value="<?=$s?>" <?=$lead['stage']===$s?'selected':''?>><?=ucfirst($s)?></option><?php endforeach?></select></label></div>
        <label>What they want to improve<textarea name="improvement_goal" rows="4"><?=e($lead['improvement_goal']??'')?></textarea></label>
        <div class="form-grid"><label>Next action<input name="next_action" value="<?=e($lead['next_action']??'')?>" placeholder="Follow up, send proposal..."></label><label>Next action date<input type="datetime-local" name="next_action_at" value="<?=!empty($lead['next_action_at'])?e(date('Y-m-d\TH:i',strtotime($lead['next_action_at']))):''?>"></label></div>
        <label>Lost reason<input name="lost_reason" value="<?=e($lead['lost_reason']??'')?>" placeholder="Only if this lead is lost"></label>
        <div class="form-actions"><button class="button" type="submit">Save changes</button><span class="muted">Source: <?=e($lead['source'])?> · Created <?=e(date('d M Y · H:i',strtotime($lead['created_at'])))?></span></div>
      </form>
    </section>
    <?php if(!empty($lead['notes'])):?><section class="panel"><h2>Notes</h2><p><?=nl2br(e($lead['notes']))?></p></section><?php endif?>
    <section class="panel"><div class="titleline"><div><span class="eyebrow">ACTIVITY</span><h2>Comments & activity</h2></div></div>
      <form class="comment-form" method="post" action="api/lead-manage.php"><input type="hidden" name="action" value="comment"><input type="hidden" name="id" value="<?=e($lead['id'])?>"><textarea name="body" rows="3" placeholder="Add a comment, call summary, context, objection..." required></textarea><button class="button" type="submit">Add comment</button></form>
      <div class="timeline"><?php if(!$activities):?><div class="empty"><p>No activity yet.</p></div><?php else:?><?php foreach($activities as $a):?><article class="timeline-item"><div class="timeline-dot"></div><div><div class="timeline-meta"><strong><?=e($a['activity_type']==='comment'?'Comment':ucwords(str_replace('_',' ',$a['activity_type'])))?></strong><span><?=e($a['admin_email']?:'System')?> · <?=e(date('d M Y · H:i',strtotime($a['created_at'])))?></span></div><p><?=nl2br(e($a['body']??''))?></p></div></article><?php endforeach?><?php endif?></div>
    </section>
  </section>
  <aside class="lead-side">
    <section class="panel"><span class="eyebrow">QUICK INFO</span><div class="kv"><span>Email</span><strong><?=e($lead['email'])?></strong></div><div class="kv"><span>Owner</span><strong><?=e($lead['owner_email']?:'Unassigned')?></strong></div><div class="kv"><span>Updated</span><strong><?=e(date('d M Y · H:i',strtotime($lead['updated_at'])))?></strong></div><?php if(!empty($lead['next_action'])):?><div class="kv"><span>Next action</span><strong><?=e($lead['next_action'])?></strong></div><?php endif?></section>
    <section class="panel danger-zone"><span class="eyebrow danger">DANGER ZONE</span><h2>Delete lead</h2><p>This permanently removes the lead and its activity timeline. The deletion itself is kept in the audit log.</p><form method="post" action="api/lead-manage.php" onsubmit="return confirm('Delete this lead permanently? This cannot be undone.');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($lead['id'])?>"><button class="danger-button" type="submit">Delete lead permanently</button></form></section>
  </aside>
</div>
<?php endif; else: ?>
<header class="page-head"><div><span class="eyebrow">COMMERCIAL</span><h1>Leads</h1><p>Website requests and prospects added by your team.</p></div><div class="lead-head-actions"><a class="ghostbtn" href="<?= page_url('pipeline') ?>">View pipeline</a><?php if($leadCreateAllowed):?><a class="button" href="<?=page_url('leads')?>&amp;add=1">Add lead</a><?php endif?></div></header>
<?php if($leadCreateOpen){require __DIR__.'/lead-create.php';}?>
<?php if(isset($_GET['deleted'])):?><div class="flash ok">Lead deleted.</div><?php endif?>
<section class="metrics"><article><span>Total leads</span><strong><?= count($leads) ?></strong><small>Latest 250 records</small></article><article><span>New</span><strong><?= count(array_filter($leads,fn($l)=>$l['stage']==='new')) ?></strong><small>Need first contact</small></article><article><span>Demos</span><strong><?= count(array_filter($leads,fn($l)=>$l['stage']==='demo')) ?></strong><small>Demo stage</small></article><article><span>Won</span><strong><?= count(array_filter($leads,fn($l)=>$l['stage']==='won')) ?></strong><small>Converted</small></article></section>
<section class="panel table-panel"><div class="table-head"><div><h2>All leads</h2><p>Click any row to open the full lead view.</p></div><span><?= count($leads) ?> loaded</span></div><?php if($dbError):?><div class="empty"><strong>Database unavailable</strong><p><?= e($dbError) ?></p></div><?php elseif(!$leads):?><div class="empty large"><strong>No leads yet.</strong><p>When someone submits Request Access on oplenteams.io, the request will appear here.</p></div><?php else:?><div style="overflow:auto"><table class="clickable-table"><thead><tr><th>Company</th><th>Contact</th><th>Email</th><th>Team</th><th>Stage</th><th>Source</th><th>Created</th></tr></thead><tbody><?php foreach($leads as $l):?><tr onclick="location.href='<?=page_url('leads')?>&id=<?=rawurlencode($l['id'])?>'"><td><strong><?= e($l['company_name']) ?></strong></td><td><?= e($l['contact_name']?:'—') ?></td><td><?= e($l['email']) ?></td><td><?= e($l['team_size']?:'—') ?></td><td><span class="stage-pill stage-<?= e($l['stage']) ?>"><?= e(ucfirst($l['stage'])) ?></span></td><td><?= e($l['source']) ?></td><td><?= e(date('d M Y · H:i',strtotime($l['created_at']))) ?></td></tr><?php endforeach?></tbody></table></div><?php endif?></section>
<?php endif; ?>
