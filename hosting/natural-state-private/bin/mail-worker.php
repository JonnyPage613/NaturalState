<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/vendor/PHPMailer/Exception.php';require dirname(__DIR__).'/src/vendor/PHPMailer/SMTP.php';require dirname(__DIR__).'/src/vendor/PHPMailer/PHPMailer.php';
if(!$c['smtp_host'])exit("SMTP not configured. Saved requests remain safe in the database.\n");
$lock=fopen($c['storage'].'/mail.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
foreach(query('SELECT * FROM outbox WHERE sent IS NULL AND next_attempt<=? AND attempts<8 ORDER BY next_attempt LIMIT 30',[time()])->fetchAll() as $row){
 try{$m=new PHPMailer\PHPMailer\PHPMailer(true);$m->isSMTP();$m->Host=$c['smtp_host'];$m->Port=$c['smtp_port'];$m->Timeout=15;$m->SMTPAuth=$c['smtp_user']!=='';$m->Username=$c['smtp_user'];$m->Password=$c['smtp_password'];$m->SMTPSecure=$c['smtp_security'];$m->CharSet='UTF-8';$m->setFrom($c['mail_from'],'Natural State Engineering');$m->addAddress($row['recipient']);$m->Subject=$row['subject'];$m->Body=$row['body'];$m->MessageID='<'.$row['id'].'@'.parse_url($c['base_url'],PHP_URL_HOST).'>';$m->send();query('UPDATE outbox SET sent=? WHERE id=?',[time(),$row['id']]);}
 catch(Throwable $e){query('UPDATE outbox SET attempts=attempts+1,next_attempt=? WHERE id=?',[time()+min(21600,60*(2**(int)$row['attempts'])),$row['id']]);error_log('Natural State email retry: '.$row['id']);}
}
