<?php
require dirname(__DIR__).'/includes/database.php';
$root=sys_get_temp_dir().'/teams-config-'.bin2hex(random_bytes(4));mkdir($root,0700,true);$env=$root.'/admin.env';$config=$root.'/platform.php';putenv('OPLEN_ADMIN_ENV='.$env);putenv('OPLEN_PLATFORM_CONFIG='.$config);
try{
 file_put_contents($env,"APP_URL=https://admin.oplenteams.io\nDB_HOST=127.0.0.1\nDB_DATABASE=fixture\nDB_USERNAME=fixture\nDB_PASSWORD=fixture\n");
 if(oplen_env()['APP_URL']!=='https://admin.oplenteams.io')throw new RuntimeException('Private env not read');
 file_put_contents($config,'<?php return '.var_export(['primary_host'=>'app.oplenteams.io','routing'=>'path','db_host'=>'localhost','db_name'=>'copied_registry','db_user'=>'test','db_pass'=>'test','tenants_dir'=>$root],true).';');
 if(oplen_admin_registry_config()['db_name']!=='copied_registry')throw new RuntimeException('Teams config not loaded');
 file_put_contents($config,"<?php return ['primary_host'=>'app.oplen.io','routing'=>'path'];");$blocked=false;try{oplen_admin_registry_config();}catch(RuntimeException $e){$blocked=true;}if(!$blocked)throw new RuntimeException('Old app registry accepted');
 $blocked=false;try{oplen_admin_private_file(dirname(__DIR__).'/index.php');}catch(RuntimeException $e){$blocked=true;}if(!$blocked)throw new RuntimeException('Public configuration accepted');
 echo "PASS Teams Admin requires private config and rejects the legacy app registry\n";
}finally{unlink($env);unlink($config);rmdir($root);}
