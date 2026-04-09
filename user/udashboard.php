<?php
require_once __DIR__ . '/../config/access_control.php';

iv_require_role_session(['user'], '../login.php');

header('Location: projects.php');
exit;
