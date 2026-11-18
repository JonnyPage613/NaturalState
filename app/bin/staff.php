<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/src/bootstrap.php';
$email=strtolower($argv[1]??'');$role=$argv[2]??'operations';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,['operations','engineer']))exit("Usage: php bin/staff.php name@business.com operations|engineer\n");
query('INSERT INTO users (id,email,role,active) VALUES (?,?,?,1)',[uid(),$email,$role]);echo "Staff member added. Sign in using an email link.\n";
