<?php
// modules/login/logout.php
require_once '../../includes/functions.php';

$_SESSION = [];

session_destroy();

header('Location: ' . url('modules/login/login.php'));
exit();
?>