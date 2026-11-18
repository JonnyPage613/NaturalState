<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/src/bootstrap.php';
$email=strtolower($argv[1]??'');$u=query('SELECT id FROM users WHERE email=?',[$email])->fetch();if(!$u)exit("Staff member not found.\n");
query('UPDATE users SET active=0 WHERE id=?',[$u['id']]);query('DELETE FROM staff_sessions WHERE user_id=?',[$u['id']]);query('DELETE FROM login_tokens WHERE user_id=?',[$u['id']]);echo "Staff access and remembered sessions revoked.\n";
