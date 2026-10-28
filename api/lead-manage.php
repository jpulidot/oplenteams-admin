<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/database.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
$action=(string)($_POST['action']??'');
$id=trim((string)($_POST['id']??''));
if($id===''){http_response_code(422);exit('Missing lead id');}
$pdo=oplen_pdo();
$back='../index.php?page=leads&id='.rawurlencode($id);
try{
  if($action==='comment'){
    $body=trim((string)($_POST['body']??''));
    if($body===''){throw new RuntimeException('Comment cannot be empty.');}
    $q=$pdo->prepare("INSERT INTO lead_activities(lead_id,activity_type,body) VALUES(?,'comment',?)");
    $q->execute([$id,$body]);
  } elseif($action==='update'){
    $old=$pdo->prepare('SELECT stage FROM leads WHERE id=? LIMIT 1');$old->execute([$id]);$oldStage=$old->fetchColumn();
    if($oldStage===false){throw new RuntimeException('Lead not found.');}
    $stage=(string)($_POST['stage']??'new');
    $allowed=['new','contacted','demo','trial','won','lost'];
    if(!in_array($stage,$allowed,true)){$stage='new';}
    $company=trim((string)($_POST['company_name']??''));
    $contact=trim((string)($_POST['contact_name']??''));
    $email=strtolower(trim((string)($_POST['email']??'')));
    $team=trim((string)($_POST['team_size']??''));
    $role=trim((string)($_POST['contact_role']??''));
    $goal=trim((string)($_POST['improvement_goal']??''));
    $next=trim((string)($_POST['next_action']??''));
    $nextAt=trim((string)($_POST['next_action_at']??''));
    $lost=trim((string)($_POST['lost_reason']??''));
    if($company===''||!filter_var($email,FILTER_VALIDATE_EMAIL)){throw new RuntimeException('Company and valid email are required.');}
    $q=$pdo->prepare('UPDATE leads SET company_name=?,contact_name=?,email=?,team_size=?,contact_role=?,improvement_goal=?,stage=?,next_action=?,next_action_at=?,lost_reason=? WHERE id=?');
    $q->execute([$company,$contact?:null,$email,$team?:null,$role?:null,$goal?:null,$stage,$next?:null,$nextAt!==''?str_replace('T',' ',$nextAt).':00':null,$lost?:null,$id]);
    if($oldStage!==$stage){$a=$pdo->prepare("INSERT INTO lead_activities(lead_id,activity_type,body) VALUES(?,'stage_changed',?)");$a->execute([$id,'Stage changed from '.$oldStage.' to '.$stage]);}
  } elseif($action==='delete'){
    $lead=$pdo->prepare('SELECT company_name,email FROM leads WHERE id=? LIMIT 1');$lead->execute([$id]);$row=$lead->fetch();
    if(!$row){throw new RuntimeException('Lead not found.');}
    $audit=$pdo->prepare("INSERT INTO audit_logs(action,entity_type,entity_id,metadata_json) VALUES('lead_deleted','lead',?,?)");
    $audit->execute([$id,json_encode(['company_name'=>$row['company_name'],'email'=>$row['email']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    $q=$pdo->prepare('DELETE FROM leads WHERE id=?');$q->execute([$id]);
    header('Location: ../index.php?page=leads&deleted=1');exit;
  } else {throw new RuntimeException('Unknown action.');}
  header('Location: '.$back.'&saved=1');exit;
}catch(Throwable $e){
  header('Location: '.$back.'&error='.rawurlencode($e->getMessage()));exit;
}
