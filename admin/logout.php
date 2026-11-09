<?php
require __DIR__ . '/inc.php';
$_SESSION = [];
session_regenerate_id(true);
redirect('login.php');
