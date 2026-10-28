<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/organizations.php';
try{
 $env=oplen_env();if(($env['APP_URL']??'')!=='https://admin.oplenteams.io')throw new RuntimeException('Invalid Admin host');
 $admin=oplen_pdo();$admin->query('SELECT id FROM admin_accounts LIMIT 1')->fetch();$registry=oplen_registry_pdo();
 $rows=$registry->query('SELECT config_path FROM oplen_tenants')->fetchAll(PDO::FETCH_ASSOC);$config=oplen_admin_registry_config();$root=realpath($config['tenants_dir']);if(!$root)throw new RuntimeException('Private tenant directory unavailable');
 foreach($rows as $row){$real=oplen_admin_private_file($row['config_path']);if(!str_starts_with($real,$root.DIRECTORY_SEPARATOR))throw new RuntimeException('Tenant outside Teams directory');}
 echo "PASS Teams Admin private configuration, administrative database and Teams registry connectivity.\nRead-only check; no accounts, subscriptions, messages or schedules changed.\nDatabase-copy isolation and browser acceptance remain separate checks.\n";
}catch(Throwable $e){fwrite(STDERR,"Teams Admin is not ready. Verify private configuration, copied databases and access permissions.\n");exit(1);}
