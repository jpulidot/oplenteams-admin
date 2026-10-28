<?php
require_once __DIR__.'/organizations.php';
require_once __DIR__.'/platform-settings.php';
$settingsError=null;$settingsData=null;$settingsHistory=[];$settingsIntegrations=[];$settingsAllowed=false;
try{$settingsAllowed=oplen_organizations_allowed($admin,'admin.full');}catch(Throwable $e){$settingsError='Administrative permissions could not be verified.';}
if(!$settingsAllowed){http_response_code($settingsError?503:403);return;}
$_SESSION['settings_csrf']??=bin2hex(random_bytes(32));
try{
    $settingsPdo=oplen_registry_pdo();oplen_settings_schema($settingsPdo);
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        if(!hash_equals($_SESSION['settings_csrf'],(string)($_POST['csrf']??'')))throw new DomainException('Reload the page before saving.');
        $input=['support_email'=>$_POST['support_email']??'','admin_timezone'=>$_POST['admin_timezone']??'','trial_days'=>$_POST['trial_days']??'','registration_open'=>isset($_POST['registration_open'])];
        oplen_settings_save($settingsPdo,$input,(string)$admin['id'],(string)($_POST['version']??''));
        $_SESSION['settings_notice']='Platform settings saved.';header('Location: '.page_url('settings'));exit;
    }
}catch(DomainException $e){$settingsError=$e->getMessage();}
catch(Throwable $e){$settingsError='Settings could not be loaded or saved. Check the central registry connection and permissions.';error_log('Oplen settings: registry operation failed.');}
if(isset($settingsPdo)){
    try{$settingsData=oplen_settings_read($settingsPdo);$settingsHistory=$settingsPdo->query('SELECT admin_id,before_json,after_json,created_at FROM oplen_platform_settings_audit ORDER BY id DESC LIMIT 10')->fetchAll();}
    catch(Throwable $e){$settingsData=null;$settingsError='Platform settings are unavailable.';}
}
try{
    $configPath=oplen_admin_registry_path();$platform=oplen_admin_registry_config();
    $private=dirname($configPath);
    $mailPath=(string)($platform['hostinger_mail_token_file']??$private.'/hostinger-mail-token.txt');
    $stripePath=(string)($platform['stripe_secret_key_file']??$private.'/stripe-secret-key.txt');
    $webhookPath=(string)($platform['stripe_webhook_secret_file']??$private.'/stripe-webhook-secret.txt');
    $readable=static fn(string $p):bool=>is_file($p)&&is_readable($p)&&filesize($p)>0;
    $stripe=$readable($stripePath)?trim((string)file_get_contents($stripePath)):'';
    $settingsIntegrations=[
        ['Hostinger mail',$readable($mailPath)?'Credential file present':'Not configured','Mailbox: '.(string)($platform['mail_from']??'hello@oplen.io').' · Delivery not tested'],
        ['Stripe',preg_match('/^sk_test_[A-Za-z0-9]+$/D',$stripe)?'Sandbox configured':($stripe===''?'Not configured':'Unsupported credential'),'Sandbox only · Live payments not enabled'],
        ['Stripe webhook',$readable($webhookPath)?'Credential file present':'Not configured','Webhook delivery not tested'],
        ['OpenAI',trim((string)($platform['openai_api_key']??''))!==''?'Key present':'Not configured','Default for newly created companies · API not tested'],
    ];unset($stripe,$platform);
}catch(Throwable $e){$settingsIntegrations=[['Integrations','Unavailable','Private configuration could not be read']];}
