<?php
declare(strict_types=1);
require_once __DIR__.'/organizations.php';

function oplen_users_companies(PDO $registry): array {
    return $registry->query("SELECT c.id,c.company_name,c.slug,c.status,c.access_kind,c.trial_ends_at,t.config_path FROM oplen_customers c JOIN oplen_tenants t ON t.slug=c.slug WHERE c.status='active' AND t.status='active' ORDER BY c.company_name,c.id")->fetchAll(PDO::FETCH_ASSOC);
}
function oplen_users_config(string $path, string $root): array {
    $root=realpath($root);$resolved=realpath($path);
    if(!$root||!$resolved||!str_starts_with($resolved,$root.DIRECTORY_SEPARATOR)||!is_readable($resolved))throw new RuntimeException('Tenant configuration unavailable');
    $config=require $resolved;
    if(!is_array($config))throw new RuntimeException('Tenant configuration unavailable');
    foreach(['db_host','db_name','db_user','db_pass'] as $key)if(!isset($config[$key])||!is_string($config[$key]))throw new RuntimeException('Tenant configuration unavailable');
    return $config;
}
function oplen_users_connection(array $company): PDO {
    $platform=oplen_admin_registry_config();
    $config=oplen_users_config((string)$company['config_path'],(string)($platform['tenants_dir']??''));
    return new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>2]);
}
function oplen_users_read(array $company, ?callable $connect=null): array {
    try{
        $pdo=($connect??'oplen_users_connection')($company);
        $rows=$pdo->query('SELECT id,name,email,role,access_level,department,job_title,is_active FROM cp_users ORDER BY name,id')->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$row){$row['identity']=$company['slug'].':'.$row['id'];$row['is_active']=(int)$row['is_active']===1;}unset($row);
        return ['available'=>true,'rows'=>$rows];
    }catch(Throwable $e){error_log('Oplen admin users: tenant account read unavailable.');return ['available'=>false,'rows'=>[]];}
}
function oplen_users_filter(array $rows,string $search,string $status): array {
    return array_values(array_filter($rows,static function($row)use($search,$status){
        if($status==='active'&&!$row['is_active'])return false;
        if($status==='inactive'&&$row['is_active'])return false;
        return $search===''||stripos($row['name'],$search)!==false||stripos($row['email'],$search)!==false;
    }));
}

function oplen_users_directory(array $companies, ?callable $connect=null, float $budgetSeconds=8.0): array {
    $rows=[];$failed=[];$loaded=0;$started=microtime(true);
    foreach($companies as $company){
        if(microtime(true)-$started>=$budgetSeconds){$failed[]=$company['company_name'].' · '.$company['slug'];continue;}
        $data=oplen_users_read($company,$connect);
        if(!$data['available']){$failed[]=$company['company_name'].' · '.$company['slug'];continue;}
        $loaded++;
        foreach($data['rows'] as $row){
            $row['company_name']=$company['company_name'];$row['company_slug']=$company['slug'];
            $row['company_access']=oplen_registry_access($company,time());$rows[]=$row;
        }
    }
    usort($rows,static fn($a,$b)=>strcasecmp($a['name'],$b['name'])?:strcmp($a['identity'],$b['identity']));
    return ['available'=>$loaded>0,'rows'=>$rows,'failed'=>$failed,'loaded'=>$loaded,'total'=>count($companies)];
}
