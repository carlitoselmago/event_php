<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once("event.php");
$E=new Event();

$E->passwordProtect(); 

$E->HTML->admin_head();
$E->admin();
$E->HTML->admin_bottom();