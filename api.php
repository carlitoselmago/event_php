<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//API for user tracking
include_once("event.php");
$E = new Event();
if (isset($_POST["time"])){
  if ($E->hasstarted()){
 
    $E->UserTrack($_SESSION["viewerid"],$_POST["time"]);
  }
}
die();
