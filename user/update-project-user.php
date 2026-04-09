<?php
require_once __DIR__ . '/../config/access_control.php';

iv_require_role_session(['user'], '../login.php');

http_response_code(403);
exit('Worker accounts cannot reassign projects.');
