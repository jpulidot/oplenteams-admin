<?php
declare(strict_types=1);
function oplen_admin_private_file(string $path):string{
 $real=realpath($path);$public=realpath(dirname(__DIR__));
 if(!$real||!is_file($real)||!is_readable($real)||$real===$public||str_starts_with($real,$public.DIRECTORY_SEPARATOR))throw new RuntimeException('Private Teams configuration unavailable.');return $real;
}
function oplen_admin_registry_path():string{
 $env=oplen_env();$path=(string)(getenv('OPLEN_PLATFORM_CONFIG')?:($env['OPLEN_PLATFORM_CONFIG']??''));if($path==='')throw new RuntimeException('Set the explicit Teams platform configuration path.');return oplen_admin_private_file($path);
}
function oplen_admin_registry_config():array{
 $config=require oplen_admin_registry_path();
 if(!is_array($config)||($config['primary_host']??'')!=='app.oplenteams.io'||($config['routing']??'')!=='path')throw new RuntimeException('The registry must belong to the Teams app.');
 foreach(['db_host','db_name','db_user','db_pass','tenants_dir'] as $key)if(!isset($config[$key])||!is_string($config[$key]))throw new RuntimeException('Incomplete Teams registry configuration.');
 return $config;
}
