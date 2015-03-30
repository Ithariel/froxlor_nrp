#!/usr/bin/php
<?php

define("FROXLOR_HOME", "/var/www/froxlor");
define("SCRIPT_HOME", "/etc/nginx/scripts");
define("NGINX_VHOST_PATH", "/etc/nginx/sites-enabled");

// Load Userdata
require_once(FROXLOR_HOME.'/lib/userdata.inc.php');

// Set vars

$froxlor_db = $sql['db'];
$froxlor_host = $sql['socket'];
$froxlor_user = $sql['user'];
$froxlor_pass = $sql['password'];

// Include info functions
require_once(SCRIPT_HOME.'/get_infos.php');

// Include vhost function
require_once(SCRIPT_HOME.'/create_vhosts.php');

create_vhosts(getDomainInfo());

?>
