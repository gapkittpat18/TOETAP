<?php
require __DIR__.'/auth/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){
 http_response_code(405);
 header('Allow: POST');
 exit('Method not allowed.');
}
requireUser();
requireCsrf();
logoutUser();
header('Location:login.php');
exit;
