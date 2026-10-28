<?php
declare(strict_types=1);
/** Shared contract with App platform/settings.php. No credentials belong here. */
function oplen_settings_defaults(): array {
    return ['support_email'=>'hello@oplen.io','admin_timezone'=>'America/Mexico_City','registration_open'=>true,'trial_days'=>7];
}
function oplen_settings_validate(array $input): array {
    $email=trim((string)($input['support_email']??''));
    $zone=(string)($input['admin_timezone']??'');
    $days=filter_var($input['trial_days']??null,FILTER_VALIDATE_INT);
    if(strlen($email)>190||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Enter a valid support email.');
    if(!in_array($zone,DateTimeZone::listIdentifiers(),true))throw new DomainException('Choose a valid time zone.');
    if($days===false||$days<1||$days>90)throw new DomainException('Trial duration must be between 1 and 90 days.');
    if(!is_bool($input['registration_open']??null))throw new DomainException('Choose whether registration is open.');
    return ['support_email'=>$email,'admin_timezone'=>$zone,'registration_open'=>$input['registration_open'],'trial_days'=>$days];
}
function oplen_settings_version(array $settings): string {return hash('sha256',json_encode($settings,JSON_THROW_ON_ERROR));}
function oplen_settings_read(PDO $pdo): array {
    try{$raw=$pdo->query('SELECT settings_json FROM oplen_platform_settings WHERE id=1')->fetchColumn();}
    catch(PDOException $e){
        $missing=(int)($e->errorInfo[1]??0)===1146||($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'&&str_contains($e->getMessage(),'no such table: oplen_platform_settings'));
        if($missing)return oplen_settings_defaults();
        throw $e;
    }
    if($raw===false)return oplen_settings_defaults();
    $data=json_decode((string)$raw,true,16,JSON_THROW_ON_ERROR);
    if(!is_array($data))throw new RuntimeException('Invalid platform settings.');
    return oplen_settings_validate(array_replace(oplen_settings_defaults(),$data));
}
function oplen_settings_schema(PDO $pdo): void {
    $mysql=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
    $tail=$mysql?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4':'';
    $id=$mysql?'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';
    $pdo->exec("CREATE TABLE IF NOT EXISTS oplen_platform_settings(id INT PRIMARY KEY,settings_json TEXT NOT NULL,updated_at BIGINT NOT NULL,updated_by VARCHAR(64) NOT NULL)$tail");
    $pdo->exec("CREATE TABLE IF NOT EXISTS oplen_platform_settings_audit(id $id,admin_id VARCHAR(64) NOT NULL,before_json TEXT NOT NULL,after_json TEXT NOT NULL,created_at BIGINT NOT NULL)$tail");
    $sql=($mysql?'INSERT IGNORE':'INSERT OR IGNORE').' INTO oplen_platform_settings(id,settings_json,updated_at,updated_by) VALUES(1,?,0,?)';
    $pdo->prepare($sql)->execute([json_encode(oplen_settings_defaults(),JSON_THROW_ON_ERROR),'']);
}
function oplen_settings_save(PDO $pdo,array $input,string $adminId,string $expectedVersion): void {
    $next=oplen_settings_validate($input);$pdo->beginTransaction();
    try{
        $lock=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
        $raw=$pdo->query('SELECT settings_json FROM oplen_platform_settings WHERE id=1'.$lock)->fetchColumn();
        if($raw===false)throw new RuntimeException('Settings unavailable.');
        $previous=oplen_settings_validate(array_replace(oplen_settings_defaults(),json_decode((string)$raw,true,16,JSON_THROW_ON_ERROR)));
        if(!hash_equals(oplen_settings_version($previous),$expectedVersion))throw new DomainException('Settings changed in another session. Reload before saving.');
        if($previous!==$next){
            $now=time();$json=json_encode($next,JSON_THROW_ON_ERROR);
            $pdo->prepare('UPDATE oplen_platform_settings SET settings_json=?,updated_at=?,updated_by=? WHERE id=1')->execute([$json,$now,$adminId]);
            $pdo->prepare('INSERT INTO oplen_platform_settings_audit(admin_id,before_json,after_json,created_at) VALUES(?,?,?,?)')->execute([$adminId,json_encode($previous,JSON_THROW_ON_ERROR),$json,$now]);
        }
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
