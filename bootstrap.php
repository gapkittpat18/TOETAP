<?php
if(session_status()!==PHP_SESSION_ACTIVE){
 session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
 session_start();
}
require_once __DIR__.'/../config/database.php';
function currentUserId(): int {
 if(!empty($_SESSION['toetap_user_id'])) return (int)$_SESSION['toetap_user_id'];
 return 0;
}
function requireUser(): int {
 $id=currentUserId();
 if(!$id){
 $next=$_SERVER['REQUEST_URI']??'/tapsole/index.php';
 // Always route auth through the app root, even when called from /strava/* or /webhook/*.
 $script=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/tapsole/index.php');
 $base=preg_replace('~/(?:strava|webhook|auth)(?:/.*)?$~','',$script);
 $base=preg_replace('~/[^/]+\.php$~','',$base);
 if($base===''||$base==='/') $base='/tapsole';
 header('Location:'.$base.'/login.php?next='.rawurlencode($next));exit;
}
 return $id;
}

function currentUserIsAdmin(): bool {
 global $pdo;
 $id=currentUserId();
 if(!$id) return false;
 try{
  $q=$pdo->prepare("SELECT is_admin FROM users WHERE id=? LIMIT 1");
  $q->execute([$id]);
  return (int)$q->fetchColumn()===1;
 }catch(Throwable $e){
  return false;
 }
}
function requireAdmin(): int {
 $id=requireUser();
 if(!currentUserIsAdmin()){
  http_response_code(403);
  exit('TOETAP Admin access required.');
 }
 return $id;
}

function loginUser(int $id): void {
 session_regenerate_id(true);
 $_SESSION['toetap_user_id']=$id;
 // New authenticated session gets a fresh CSRF secret.
 $_SESSION['toetap_csrf']=bin2hex(random_bytes(32));
}
function logoutUser(): void {
 $_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();
}
function safeNext(string $next): string {
 $next=trim($next);
 if($next==='') return 'index.php';
 // Never allow control characters, backslashes, absolute/protocol-relative URLs or URL schemes.
 if(preg_match('/[\x00-\x1F\x7F]/',$next) || strpos($next,'\\')!==false) return 'index.php';
 if(preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i',$next) || preg_match('~^[a-z][a-z0-9+.-]*:~i',$next)) return 'index.php';
 $parts=parse_url($next);
 if($parts===false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return 'index.php';
 $path=(string)($parts['path']??'');
 if($path==='' || preg_match('~(?:^|/)\.{1,2}(?:/|$)~',$path)) return 'index.php';
 // Absolute paths must stay inside this app. Relative paths stay inside the current app origin.
 if($path[0]==='/' && !preg_match('~^/tapsole(?:/|$)~',$path)) return 'index.php';
 return $next;
}

function csrfToken(): string {if(empty($_SESSION['toetap_csrf']))$_SESSION['toetap_csrf']=bin2hex(random_bytes(32));return $_SESSION['toetap_csrf'];}
function csrfField(): string {return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrfToken(),ENT_QUOTES,'UTF-8').'">';}
function requireCsrf(): void {$x=(string)($_POST['csrf']??'');if($x===''||!hash_equals(csrfToken(),$x)){http_response_code(403);exit('Invalid form token.');}}
