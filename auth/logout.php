<?php
require_once __DIR__ . '/../config/db.php';
session_start_once();
$_SESSION = [];
session_destroy();
redirect('/auth/login.php');
