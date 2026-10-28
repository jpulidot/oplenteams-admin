<?php
declare(strict_types=1);
require_once __DIR__.'/teams-config.php';
function oplen_env(): array {
    static $env=null; if($env!==null) return $env; $env=[];
    $file=oplen_admin_private_file((string)(getenv('OPLEN_ADMIN_ENV')?:dirname(__DIR__,2).'/private/admin.env'));
    foreach(file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [] as $line){
        $line=trim($line); if($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
        [$k,$v]=explode('=',$line,2); $env[trim($k)]=trim($v," \t\n\r\0\x0B\"'");
    }
    return $env;
}
function oplen_pdo(): PDO {
    $e=oplen_env();
    foreach(['DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD'] as $k){if(!array_key_exists($k,$e)) throw new RuntimeException('Missing '.$k);}
    $dsn='mysql:host='.$e['DB_HOST'].';port='.($e['DB_PORT']??'3306').';dbname='.$e['DB_DATABASE'].';charset=utf8mb4';
    return new PDO($dsn,$e['DB_USERNAME'],$e['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3]);
}