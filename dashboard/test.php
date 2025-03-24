<?php
require_once "../vendor/autoload.php";
use PHPGangsta_GoogleAuthenticator\GoogleAuthenticator;
$ga = new GoogleAuthenticator();
echo $ga->createSecret();