<?php
require_once __DIR__.'/lead-create.php';
$leadCreateAllowed=false;$leadCreateError=null;
$leadCreateOpen=isset($_GET['add'])||($_SERVER['REQUEST_METHOD']??'GET')==='POST';
$leadCreateValues=[];
try{$leadCreateAllowed=oplen_organizations_allowed($admin,'commercial.write');}
catch(Throwable $e){$leadCreateError='Lead permissions could not be verified.';}
$_SESSION['lead_create_csrf']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $leadCreateValues=$_POST;
    try{
        if(!$leadCreateAllowed){http_response_code(403);throw new DomainException('Your role cannot add leads.');}
        $token=is_string($_POST['csrf']??null)?$_POST['csrf']:'';
        if(!hash_equals($_SESSION['lead_create_csrf'],$token)){http_response_code(403);throw new DomainException('Reload the form before saving.');}
        $id=oplen_lead_create(oplen_pdo(),$_POST,(string)$admin['id'],$leadCreateAllowed,$token,$_SESSION['lead_create_csrf']);
        $_SESSION['lead_create_csrf']=bin2hex(random_bytes(32));
        header('Location: '.page_url('leads').'&id='.rawurlencode($id).'&created=1',true,303);exit;
    }catch(DomainException $e){$leadCreateError=$e->getMessage();}
    catch(Throwable $e){$leadCreateError='The lead could not be saved. Your input is preserved; check the Admin database and try again.';error_log('Oplen lead creation failed.');}
}
