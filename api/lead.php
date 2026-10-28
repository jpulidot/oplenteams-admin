<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://oplenteams.io');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);exit;}
$input=json_decode(file_get_contents('php://input') ?: '{}',true);if(!is_array($input))$input=$_POST;
$company=trim((string)($input['company']??$input['company_name']??''));$name=trim((string)($input['name']??$input['contact_name']??''));$email=strtolower(trim((string)($input['email']??'')));$team=trim((string)($input['team_size']??$input['team']??$input['size']??''));$role=trim((string)($input['role']??''));$improve=trim((string)($input['improve']??''));$website=trim((string)($input['website']??$input['_honey']??''));
if($company===''||!filter_var($email,FILTER_VALIDATE_EMAIL)){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'invalid_input']);exit;}if($website!==''){echo json_encode(['ok'=>true]);exit;}
function lead_uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&15)|64);$d[8]=chr((ord($d[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
try{$pdo=oplen_pdo();$check=$pdo->prepare("SELECT id FROM leads WHERE email=? AND company_name=? AND created_at >= (NOW() - INTERVAL 1 DAY) LIMIT 1");$check->execute([$email,$company]);if($check->fetchColumn()){echo json_encode(['ok'=>true,'duplicate'=>true]);exit;}$id=lead_uuid();$notes=trim(($role!==''?'Role: '.$role: '').($improve!==''?(($role!==''?"\n\n":'').'What they want to improve: '.$improve):''));$q=$pdo->prepare("INSERT INTO leads(id,company_name,contact_name,email,team_size,source,stage,notes) VALUES(?,?,?,?,?,'website','new',?)");$q->execute([$id,$company,$name?:null,$email,$team?:null,$notes?:null]);$a=$pdo->prepare("INSERT INTO lead_activities(lead_id,activity_type,body) VALUES(?,'lead_created','Access request submitted from oplenteams.io')");$a->execute([$id]);http_response_code(201);echo json_encode(['ok'=>true,'id'=>$id]);}catch(Throwable $e){error_log('Oplen lead capture: '.$e->getMessage());http_response_code(500);echo json_encode(['ok'=>false,'error'=>'server_error']);}