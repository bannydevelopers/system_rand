 <?php 
$registry = storage::init();
$helper = helper::init();

$home = str_replace('//','/', "/{$registry->request_dir}/{$registry->request[0]}");
$db = db::get_connection(storage::init()->system_config->database);
$msgs = [
    'password_changed'=>'Password changed successful'
];
if(isset($_GET['msg']) && isset($msgs[$_GET['msg']])) $msg = $msgs[$_GET['msg']];

if(isset($_POST['login']) && isset($_POST['password'])){
    $helper->login_user($_POST);
    if(!$helper->check_user_session()) $msg = 'Login creditial mismatch';
}
if(isset($_GET['logout'])){
    // Unset session
    $helper->end_user_session();
    // Remove query string
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: {$url}");
}
if(isset($registry->request[1]) && $registry->request[1] == 'forgot_password'){
    if(isset($_GET['recover'])){
        // verify token and let go
        $token = addslashes($_GET['recover']);
        if(isset($_POST['password1'])){
            if($_POST['password1'] == $_POST['password2']){
                $user = $db->select('user','user_id')
                   ->where(['activation_token'=>$token])
                   ->limit(1)
                   ->fetch();
                if($user){
                    $password = helper::create_hash($_POST['password1']);
                    $k = $db->update('user', ['activation_token'=>null,'password'=>$password])
                            ->where(['user_id'=>$user['user_id']])
                            ->commit();
                    if($k->rowCount()){
                        header("Location: {$home}?msg=password_changed");
                    }
                    else {
                        die($helper::get_sub_template('login', ['error'=>'Password change failed']));
                    }
                }
                else{
                    $msg = 'Token invalid or expired';
                    die($helper::get_sub_template('change-password', ['error'=>$msg]));
                }
            }
            else{
                $msg = 'Passwords do not match';
                die($helper::get_sub_template('change-password', ['error'=>$msg]));
            } 
        }
        $user = $db->select('user')
                   ->where(['activation_token'=>$token])
                   ->limit(1)
                   ->fetch();
        if($user){
            die($helper::get_sub_template('change-password',['error'=>'']));
        }
        else{
            die($helper::get_sub_template('forgot-password',['error'=>'Token verification failed!']));
        }
    }
    $msg = '';
    if(isset($_POST['login'])){
        $user = $db->select('user')
                    ->where(['email'=>helper::format_email($_POST['login'])])
                    ->or(['phone_number'=>helper::format_phone_number($_POST['login'])])
                    ->limit(1)
                    ->fetch();
        if($user){//var_dump(storage::init()->system_config);
            $token = helper::create_hash(microtime(true));

            $db->update('user', ['activation_token'=>$token])->where(['user_id'=>$user['user_id']])->commit();

            $first_name = $user['first_name'];
            $site_name = storage::init()->system_config->system_name;
            $link = "https://{$_SERVER['HTTP_HOST']}/{$home}/forgot_password?recover={$token}";
            $user['phone_number'] = "{$user['phone_number']}"; // stringify
            $phone = "{$user['phone_number'][0]}{$user['phone_number'][1]}{$user['phone_number'][2]}{$user['phone_number'][3]}********";
            
            $template = file_get_contents(realpath(__DIR__.'/../').'/config/mails/emails/password_recovery.html');
            $body = str_replace(['{$first_name}','{$site_name}','{$link}'],[$first_name, $site_name,$link], $template);
            $sms_opts = [
                'recipients'=>$user['phone_number'],
                'body'=>strip_tags($body)
            ];
            if($user['phone_number']) $result = helper::send_sms($sms_opts);

            $email_opts = [
                'body'=>$body, 
                'subject'=>'Password recovery instruction',
                'recipient'=>$user['email']
            ];
            if($user['email']) $result = helper::send_email($email_opts);
            if($result == 'mail_send_ok'){
                die($helper::get_sub_template('token-sent',['phone'=>$phone,'email'=>'*****']));
            }
            else $msg = $result;
        }
        else $msg = 'Account not found. Please retry!';
    }
    die($helper::get_sub_template('forgot-password',['msg'=>$msg]));
}
if(!$helper->check_user_session()){
    $msg = isset($msg) ? $msg : '';
    die($helper::get_sub_template('login',['error'=>$msg]));
}

$session_user = $helper->get_session_user();
$user_permission = $helper->get_user_permissions($session_user['system_role']);

if(isset($registry->request[1])){
    if(is_readable(__DIR__."/{$registry->request[1]}/index.php")){
        include __DIR__."/{$registry->request[1]}/index.php";
    }
    else
    {
        $storage = storage::init();
        $root = realpath(__DIR__.'/../system/templates/')."";
        $base = trim("{$storage->request_dir}/cmis/system/assets/",'/');
        if(is_readable("{$root}/{$storage->system_config->theme}/404.html"))
        {
            include "{$root}/{$storage->system_config->theme}/404.html";
        }
        else{
            include "{$root}/default/404.html";
        }
    }
}
else include __DIR__.'/default/index.php';
