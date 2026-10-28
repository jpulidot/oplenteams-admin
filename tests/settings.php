<?php
require __DIR__.'/../includes/platform-settings.php';
function settings_check(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
settings_check(oplen_settings_read($pdo)['trial_days']===7,'Legacy installations keep seven days');
oplen_settings_schema($pdo);$old=oplen_settings_read($pdo);$v=oplen_settings_version($old);
$new=$old;$new['registration_open']=false;$new['trial_days']=14;$new['support_email']='support@example.com';$new['admin_timezone']='Europe/Madrid';
oplen_settings_save($pdo,$new,'admin-1',$v);
settings_check(oplen_settings_read($pdo)===$new,'All settings persisted');
settings_check((int)$pdo->query('SELECT COUNT(*) FROM oplen_platform_settings_audit')->fetchColumn()===1,'Successful change audited');
try{oplen_settings_save($pdo,$old,'admin-2',$v);throw new RuntimeException('Concurrent edit accepted');}catch(DomainException $e){}
settings_check(oplen_settings_read($pdo)===$new,'Stale save does not overwrite');
foreach([['trial_days'=>0],['trial_days'=>91],['support_email'=>"bad\r\n@example.com"],['admin_timezone'=>'not-a-zone'],['registration_open'=>'true']] as $bad){try{oplen_settings_validate(array_replace($new,$bad));throw new RuntimeException('Invalid settings accepted');}catch(DomainException $e){}}
oplen_settings_save($pdo,$new,'admin-1',oplen_settings_version($new));
settings_check((int)$pdo->query('SELECT COUNT(*) FROM oplen_platform_settings_audit')->fetchColumn()===1,'Unchanged saves do not create false history');
$pdo->exec("CREATE TRIGGER audit_failure BEFORE INSERT ON oplen_platform_settings_audit BEGIN SELECT RAISE(ABORT,'fixture'); END");
try{oplen_settings_save($pdo,$old,'admin-1',oplen_settings_version($new));throw new RuntimeException('Audit failure ignored');}catch(PDOException $e){}
settings_check(oplen_settings_read($pdo)===$new,'Audit failure rolls back settings');
$pdo->exec('DROP TABLE oplen_platform_settings; CREATE TABLE oplen_platform_settings(id INT PRIMARY KEY,settings_json TEXT)');
$pdo->exec("INSERT INTO oplen_platform_settings VALUES(1,'invalid')");
try{oplen_settings_read($pdo);throw new RuntimeException('Corrupt settings silently defaulted');}catch(JsonException $e){}
echo "Settings persistence, validation, concurrency and audit checks passed.\n";
