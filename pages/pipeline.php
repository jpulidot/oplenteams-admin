<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/database.php';
$leads=[];$err=null;
try{$pdo=oplen_pdo();$leads=$pdo->query("SELECT id,company_name,contact_name,email,team_size,stage,next_action,next_action_at,created_at FROM leads ORDER BY updated_at DESC")->fetchAll();}catch(Throwable $e){$err=$e->getMessage();}
$stages=['new'=>'New','contacted'=>'Contacted','demo'=>'Demo','trial'=>'Trial','won'=>'Won','lost'=>'Lost'];
?>
<header class="page-head"><div><span class="eyebrow">COMMERCIAL</span><h1>Pipeline</h1><p>Drag a lead between stages. Every move is saved to the database and logged in the lead activity history.</p></div><a class="button" href="<?=page_url('leads')?>">All leads</a></header>
<?php if($err):?>
<section class="panel"><div class="health-error"><?=e($err)?></div></section>
<?php else:?>
<div class="pipeline-board">
<?php foreach($stages as $key=>$label):$items=array_values(array_filter($leads,fn($l)=>$l['stage']===$key));?>
<section class="pipeline-col" data-stage="<?=e($key)?>">
  <div class="pipeline-head"><span><?=$label?></span><b><?=count($items)?></b></div>
  <div class="pipeline-stack">
    <?php foreach($items as $l):?>
      <a class="lead-card" draggable="true" data-id="<?=e($l['id'])?>" data-stage="<?=e($l['stage'])?>" href="<?=page_url('leads')?>&id=<?=urlencode($l['id'])?>">
        <strong><?=e($l['company_name'])?></strong>
        <span><?=e($l['contact_name']?:$l['email'])?></span>
        <?php if($l['next_action']):?><small>Next · <?=e($l['next_action'])?></small><?php endif?>
      </a>
    <?php endforeach?>
    <?php if(!$items):?><div class="pipeline-empty">Drop leads here</div><?php endif?>
  </div>
</section>
<?php endforeach?>
</div>
<?php endif?>
