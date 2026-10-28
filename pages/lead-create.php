<?php
$value=static fn(string $key,string $default=''):string=>is_string($leadCreateValues[$key]??null)?$leadCreateValues[$key]:$default;
?>
<section class="panel lead-summary lead-create"><div class="titleline"><h2>Add lead</h2><a class="ghostbtn" href="<?=e(page_url('leads'))?>">Cancel</a></div>
<?php if($leadCreateError):?><div class="health-error" role="alert"><?=e($leadCreateError)?></div><?php endif?>
<?php if($leadCreateAllowed):?>
<form class="lead-form" method="post" action="<?=e(page_url('leads'))?>&amp;add=1"><input type="hidden" name="csrf" value="<?=e($_SESSION['lead_create_csrf'])?>">
<div class="form-grid">
<?php foreach(['company_name'=>['Company *',190],'contact_name'=>['Contact',190],'email'=>['Email *',190],'team_size'=>['Team size',50],'contact_role'=>['Contact role',160]] as $key=>[$label,$max]):?>
<label><?=$label?><input type="<?=$key==='email'?'email':'text'?>" name="<?=$key?>" maxlength="<?=$max?>" value="<?=e($value($key))?>" <?=in_array($key,['company_name','email'],true)?'required':''?>></label>
<?php endforeach?>
<label>Stage<select name="stage"><?php foreach(['new','contacted','demo','trial','won','lost'] as $stage):?><option value="<?=$stage?>" <?=$value('stage','new')===$stage?'selected':''?>><?=ucfirst($stage)?></option><?php endforeach?></select></label>
<label>Source<select name="source"><?php foreach(['manual'=>'Manual','referral'=>'Referral','outbound'=>'Outbound','event'=>'Event','other'=>'Other'] as $source=>$label):?><option value="<?=$source?>" <?=$value('source','manual')===$source?'selected':''?>><?=$label?></option><?php endforeach?></select></label>
<label>Next action<input name="next_action" maxlength="255" value="<?=e($value('next_action'))?>" placeholder="Schedule demo, follow up…"></label></div>
<label>What they want to improve<textarea name="improvement_goal" maxlength="10000" rows="3"><?=e($value('improvement_goal'))?></textarea></label>
<label>Notes<textarea name="notes" maxlength="10000" rows="3"><?=e($value('notes'))?></textarea></label>
<div class="form-actions"><button class="button" type="submit">Create lead</button><span class="muted">Assigned to you · Appears in Leads and Pipeline</span></div>
</form><?php elseif(!$leadCreateError):?><p>Your role cannot add leads.</p><?php endif?></section>
