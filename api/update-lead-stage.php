<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/database.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);exit;}
$input=json_decode(file_get_contents('php://input') ?: '{}',true);
$id=trim((string)($input['id']??''));
$stage=trim((string)($input['stage']??''));
$allowed=['new','contacted','demo','trial','won','lost'];
if($id===''||!in_array($stage,$allowed,true)){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'invalid_input']);exit;}
try{
  $pdo=oplen_pdo();
  $q=$pdo->prepare('SELECT stage FROM leads WHERE id=? LIMIT 1');
  $q->execute([$id]);
  $old=$q->fetchColumn();
  if($old===false){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'lead_not_found']);exit;}
  if($old===$stage){echo json_encode(['ok'=>true,'unchanged'=>true]);exit;}
  $pdo->beginTransaction();
  $u=$pdo->prepare('UPDATE leads SET stage=?, updated_at=NOW() WHERE id=?');
  $u->execute([$stage,$id]);
  $a=$pdo->prepare("INSERT INTO lead_activities(lead_id,activity_type,body) VALUES(?,'stage_changed',?)");
  $a->execute([$id,'Stage changed from '.ucfirst((string)$old).' to '.ucfirst($stage)]);
  $pdo->commit();
  echo json_encode(['ok'=>true,'stage'=>$stage]);
}catch(Throwable $e){
  if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
  error_log('Oplen stage update: '.$e->getMessage());
  http_response_code(500);echo json_encode(['ok'=>false,'error'=>'server_error']);
}
