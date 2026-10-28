<?php
declare(strict_types=1);
require_once __DIR__.'/organizations.php';

function oplen_overview_customers(array $customers, int $now): array {
    $registered=0;$active=0;$trials=0;$courtesy=0;$paid=0;
    foreach($customers as $c){
        if($c['status']==='deleted')continue;
        $registered++;
        if($c['status']!=='active')continue;
        $active++;
        if(oplen_registry_access($c,$now)==='Trial')$trials++;
        if($c['access_kind']==='courtesy')$courtesy++;
        if($c['access_kind']==='paid')$paid++;
    }
    return compact('registered','active','trials','courtesy','paid');
}
function oplen_overview_users(PDO $registry, array $customers): int {
    $platform=oplen_admin_registry_config();
    $root=realpath((string)($platform['tenants_dir']??''));
    if(!$root)throw new RuntimeException('Tenant configuration unavailable');
    $q=$registry->prepare("SELECT config_path FROM oplen_tenants WHERE slug=? AND status='active'");
    $total=0;
    foreach($customers as $c){
        if($c['status']!=='active')continue;
        $q->execute([$c['slug']]);$configPath=$q->fetchColumn();
        $resolved=$configPath?realpath((string)$configPath):false;
        if(!$resolved||!str_starts_with($resolved,$root.DIRECTORY_SEPARATOR)||!is_readable($resolved))throw new RuntimeException('Tenant unavailable');
        $config=require $resolved;
        foreach(['db_host','db_name','db_user','db_pass'] as $key){if(!isset($config[$key])||!is_string($config[$key]))throw new RuntimeException('Tenant unavailable');}
        $pdo=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>2]);
        $total+=(int)$pdo->query('SELECT COUNT(*) FROM cp_users WHERE is_active=1')->fetchColumn();
    }
    return $total;
}
function oplen_overview_health(): array {
    $urls=['Website'=>'https://oplenteams.io/','App'=>'https://app.oplenteams.io/','Demo'=>'https://demo.oplenteams.io/'];
    $checks=[];
    foreach($urls as $name=>$url)$checks[$name]=['status'=>'Not verified','detail'=>'HTTP check unavailable','ok'=>false];
    if(!function_exists('curl_multi_init'))return $checks;
    $multi=curl_multi_init();$handles=[];
    foreach($urls as $name=>$url){
        $h=curl_init($url);
        curl_setopt_array($h,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT_MS=>1000,CURLOPT_TIMEOUT_MS=>2500,CURLOPT_USERAGENT=>'OplenAdminAvailability/1.0']);
        curl_multi_add_handle($multi,$h);$handles[$name]=$h;
    }
    do{$status=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,0.1);}while($running&&$status===CURLM_OK);
    foreach($handles as $name=>$h){
        $code=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);
        $ok=curl_errno($h)===0&&$code>=200&&$code<400;
        $checks[$name]=['status'=>$ok?'Responding':($code?'HTTP error':'Not verified'),'detail'=>$code?'HTTP '.$code:'Request could not be completed','ok'=>$ok];
        curl_multi_remove_handle($multi,$h);curl_close($h);
    }
    curl_multi_close($multi);
    return $checks;
}

function oplen_admin_timezone(): string {
    require_once __DIR__.'/platform-settings.php';
    try{return oplen_settings_read(oplen_registry_pdo())['admin_timezone'];}
    catch(Throwable $e){return 'America/Mexico_City';}
}
