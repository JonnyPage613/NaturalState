<?php
declare(strict_types=1);
$configFile=getenv('APP_CONFIG') ?: dirname(__DIR__).'/config/config.php';
if(!is_file($configFile)) {http_response_code(503);exit('Application setup is required. See START HERE.md.');}
$c=require $configFile;
ini_set('display_errors','0');
$secure=str_starts_with($c['base_url'],'https://');
if(PHP_SAPI!=='cli') {
 header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('Cache-Control: no-store');
 session_name('ns_csrf'); session_set_cookie_params(['lifetime'=>86400,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
 ini_set('session.use_strict_mode','1');session_start();
 $_SESSION['csrf']??=bin2hex(random_bytes(24));
}
function db(): PDO {global $c;static $pdo;if(!$pdo){$pdo=new PDO($c['dsn'],$c['db_user'],$c['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite')$pdo->exec('PRAGMA foreign_keys=ON');}return $pdo;}
function query(string $sql,array $v=[]): PDOStatement {$s=db()->prepare($sql);$s->execute($v);return $s;}
function uid():string{return bin2hex(random_bytes(16));}
function out(array $data,int $status=200):never {http_response_code($status);header('Content-Type: application/json');echo json_encode($data,JSON_THROW_ON_ERROR);exit;}
function fail(string $msg,int $status=422):never{if(db()->inTransaction())db()->rollBack();out(['error'=>$msg],$status);}
function csrf():void {if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf']??''))fail('Please refresh the page and try again.',403);}
function staff():array {$token=$_COOKIE['ns_staff']??'';$u=query('SELECT u.* FROM staff_sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires>? AND u.active=1',[hash('sha256',$token),time()])->fetch();if(!$u)fail('Please sign in to your staff workspace.',401);return $u;}
function rate(string $bucket,int $max,int $seconds):void {$key=hash('sha256',$bucket.'|'.($_SERVER['REMOTE_ADDR']??'cli'));$cut=time()-$seconds;query('DELETE FROM rate_events WHERE created<?',[$cut]);$n=query('SELECT COUNT(*) FROM rate_events WHERE bucket=?',[$key])->fetchColumn();if($n>=$max)fail('Please wait before trying again.',429);query('INSERT INTO rate_events (id,bucket,created) VALUES (?,?,?)',[uid(),$key,time()]);}
function mailqueue(string $to,string $subject,string $body):void {if(filter_var($to,FILTER_VALIDATE_EMAIL))query('INSERT INTO outbox (id,recipient,subject,body,attempts,next_attempt) VALUES (?,?,?,?,0,0)',[uid(),$to,$subject,$body]);}
function textfield(array $d,string $k,int $max=200,bool $required=false):string {$v=trim((string)($d[$k]??''));if(strlen($v)>$max||($required&&$v===''))throw new InvalidArgumentException('Please check '.$k.'.');return $v;}
function readjson():array {$d=json_decode(file_get_contents('php://input'),true);if(!is_array($d))fail('Invalid request.');return $d;}
function event(string $project,string $actor,string $kind,array $data):void {query('INSERT INTO events (id,project_id,actor,kind,payload,created) VALUES (?,?,?,?,?,?)',[uid(),$project,$actor,$kind,json_encode($data,JSON_THROW_ON_ERROR),time()]);}
