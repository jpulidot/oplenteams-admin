<?php
require_once __DIR__.'/organizations.php';
require_once __DIR__.'/company-deletion.php';
if(empty($_SESSION['subscription_csrf']))$_SESSION['subscription_csrf']=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']!=='POST')return;
try{
    $deleting=($_POST['action']??'')==='delete-company';
    if(!oplen_organizations_allowed($admin)||!oplen_organizations_allowed($admin,$deleting?'organizations.delete':'billing.write')){
        http_response_code(403);throw new DomainException('You do not have permission to perform this action.');
    }
    if(!hash_equals($_SESSION['subscription_csrf'],(string)($_POST['csrf']??''))){
        http_response_code(403);throw new DomainException('The form expired. Reload and try again.');
    }
    if($deleting){oplen_deletion_request(oplen_registry_pdo(),$admin,$_POST);$_SESSION['subscription_notice']='Company deletion confirmed. Access is blocked immediately; resource cleanup runs automatically on the hourly schedule.';header('Location: '.page_url('organizations'));exit;}
    oplen_subscription_save(oplen_registry_pdo(),$admin,$_POST);
    $_SESSION['subscription_notice']='Subscription updated. No payment was charged.';
    header('Location: '.page_url('organizations'));exit;
}catch(DomainException $ex){$subscriptionError=$ex->getMessage();}
catch(Throwable $ex){http_response_code(503);$subscriptionError='The operation could not be completed. Reload the company list before retrying.';error_log('Oplen subscription: save failed.');}

