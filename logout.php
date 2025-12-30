<?php
require_once __DIR__ . '/session.php';

$_SESSION = [];
session_destroy();

header("Location: /Forms-php/client/login/login.php");
exit;
