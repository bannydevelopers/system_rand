<?php
class helper{
    private static $instance = null;

    private function get_config(){
        return storage::init()->system_config;
    }
    public static function init(){
        if(self::$instance === null) self::$instance = new Static();
        return self::$instance;
    }
    public function save_user($user_data){
        $db = db::get_connection(storage::init()->system_config->database);
        $ret = ['status'=>'unknown', 'details'=>'not set', 'user_id'=>0];
        $data = [];
        // Some fields are optional
        if(isset($user_data['first_name'])) $data['first_name'] = addcslashes($user_data['first_name']);
        if(isset($user_data['middle_name'])) $data['middle_name'] = addcslashes($user_data['middle_name']);
        if(isset($user_data['last_name'])) $data['last_name'] = addcslashes($user_data['last_name']);
        if(isset($user_data['system_role'])) $data['system_role'] = addcslashes($user_data['system_role']);
        if(isset($user_data['status'])) $data['status'] = addcslashes($user_data['status']);
        if(isset($user_data['phone_number'])) $data['phone_number'] = self::format_phone_number($user_data['phone_number']);
        if(isset($user_data['email'])) $data['email'] = self::format_email($user_data['email']);

        if(isset($user_data['user_id']) && $user_data['user_id']){
            $id = intval($user_data['user_id']);
            $result = $db->update('user', $data)->where(['user_id'=>$id])->commit();
        }
        else{
            if(!isset($data['first_name']) or (!isset($data['phone_number']) && !isset($data['email']))) return;
            $user_data['created_by'] = self::get_session_user('user_id');
            $user_data['created_time'] = date('Y-m-d H:i:s');
            $id = $db->insert('user', $data);
        }
        if($db->error()) $ret = ['status'=>'fail', 'details'=>$db->error(), 'user_id'=>$id];
        else $ret = ['status'=>'ok', 'details'=>'Save was a success!', 'user_id'=>$id];

        return (object)$ret;
    }
    public function get_session_user($index = null){
        $conf = storage::init()->system_config;
        $user = isset($_SESSION[$conf->session_name]) ? $_SESSION[$conf->session_name] : null;
        if($user && isset($user['user'])){
            if($index == null) return $user['user'];
            elseif(isset($user['user'][$index])) return $user['user'][$index];
        }
        return null;
    }
    public function set_session_user($user_info){
        $conf = storage::init()->system_config;
        if(!is_array($user_info)) return false;
        $_SESSION[$conf->session_name]['user'] = $user_info;
        if(is_array($_SESSION[$conf->session_name]['user'])) return true;
        else return false;
    }
    public function end_user_session(){
        $conf = storage::init()->system_config;
        $_SESSION[$conf->session_name]['user'] = null;
        unset($_SESSION[$conf->session_name]['user']);
    }
    public function get_user_permissions($role){
        $db = db::get_connection(storage::init()->system_config->database);
        
        $whr = " permission_id IN (SELECT permission_id FROM `role_permission_list` WHERE role_id = {$role})";
        $permission = $db->select('permission','permission_name')->where($whr)->fetchAll();
        if($permission) {
            $return = [];
            foreach($permission as $v){
                $return[] = $v['permission_name'];
            }
            return $return;
        }
        else return [];
    }
    public function user_can($reference){
        $permission = $this->get_user_permissions($this->get_session_user('system_role'));
        return in_array($reference, $permission) ? true : false;
    }
    public static function get_user_avatar($user_id){
        $conf = storage::init();
        $dir = realpath(__DIR__.'/../system/assets/uploads/avatar');
        $default = 'img/no-profile.jpg';

        if(is_readable("$dir/avatar_$user_id.jpg")) 
            return "uploads/avatar/avatar_{$user_id}.jpg";
        else
            return $default;
    }
    public function login_user($login_info){
        $db = db::get_connection(storage::init()->system_config->database);
        $obj = new static();
        $whr = "password = :password AND (phone_number = :pnumber OR email = :email) AND status = 'active'";
        if(intval($login_info['login'])){
            $login = $obj::format_phone_number($login_info['login']);
        }
        else $login = $obj::format_email($login_info['login']);
        if(!$login) return false;
        $pass = $obj::create_hash($login_info['password']);
        $user = $db->select('user')
                    ->where($whr, ['password'=>$pass, 'pnumber'=>$login, 'email'=>$login])
                    ->limit(1)->fetch();
        if(!$db->error() && isset($user['password'])){
            unset($user['password']);
            unset($user['activation_token']);
            return $obj->set_session_user($user);
        }
        return false;
    }
    public function check_user_session(){
        $obj = new static();
        $user = $obj->get_session_user();
        if($user == null) return false;
        else return true;
    }
    
    public static function create_hash($plain_text){
            $plain_txt = md5(str_rot13($plain_text ?? ''));
            $ret = hash('sha512', md5($plain_txt));
            return md5(str_rot13($ret ?? ''));
        }
        
    public static function format_time($time, $format = 'Y-m-d H:i:s') {
        if ($time !== null) {
            $timestamp = strtotime($time);
            return date($format, $timestamp);
        } else {
            return ''; // or any default value or error handling logic you want
        }
    }
    public static function format_phone_number($number) {
        if (!empty($number)) {
            $number = preg_replace('/\D/', '', $number);
            if ($number && ($number[0] == '0' || strlen($number) < 10)) {
                $number = '255' . intval($number);
            }
        }
        return $number;
    }
    public static function format_phone($number){
        return self::format_phone_number($number);
    }
    public static function format_email($email){
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    public static function upload_image($source, $destination, $sizes = ['width'=>800, 'height'=>600]){
        list($width, $height, $type) = getimagesize($source);
        $finaldst = $destination;

        if( $type == IMAGETYPE_JPEG ) $src = imagecreatefromjpeg($source);
        elseif( $type == IMAGETYPE_GIF ) $src = imagecreatefromgif($source);
        elseif( $image_type == IMAGETYPE_PNG ) $src = imagecreatefrompng($source);
        else return 'Unsupported image format';

        $w = $sizes['width'];
        $h = $sizes['height'];
        $ir = $width/$height; // Source ratio
        $fir = $w/$h; // Destination ratio
        if($ir >= $fir){
            $newheight = $h; 
            $newwidth = round($w * ($width / $height));
        }
        else {
            $newheight = round($w / ($width/$height));
            $newwidth = $w;
        }   
        $xcor = round(0 - ($newwidth - $w) / 2);
        $ycor = round(0 - ($newheight - $h) / 2);
     
        $dst = imagecreatetruecolor($w, $h);

        $dest_info = explode('.',$destination);
        $dest_ext = end($dest_info);
        $dest_ext = strtolower($dest_ext);
        $transparent_images = ['gif', 'png'];

        if( in_array($dest_ext, $transparent_images) && ( $type == IMAGETYPE_GIF or $type == IMAGETYPE_PNG) ){
            $background = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagecolortransparent($dst, $background);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, $xcor, $ycor, 0, 0, $newwidth, $newheight, $width, $height);
        
        if($dest_ext == 'jpg' or $dest_ext == 'jpeg') imagejpeg($dst, $finaldst);
        elseif($dest_ext == 'png') imagepng($dst, $finaldst);
        elseif($dest_ext == 'gif') imagegif($dst, $finaldst);

        imagedestroy($dst);
        unlink($source);
        return $destination;
    }
    public static function find_template($template_name, $data = []){
        $storage = storage::init();
        $that = new static();
        if(is_array($data) or is_object($data)) extract($data);
        //$dirs = scandir(__DIR__);
        $dirs = storage::init()->system_config->modules;
        $home = str_replace('//','/', "/{$storage->request_dir}/{$storage->request[0]}");
        $nav = [
                    [
                        'name'=>'Dashboard', 
                        'href'=>str_replace('//','/', "/{$storage->request_dir}/{$storage->request[0]}/"),
                        'icon'=>'home'
                    ]
                ];
        $user_permission = $that->get_user_permissions($that->get_session_user('system_role'));
        //var_dump($user_permission);
        foreach($dirs as $dir){
            $jfile = realpath(__DIR__.'/../')."/modules/{$dir}/module.json";
            // Check existance and permission before continuing...
            if(!is_readable($jfile)) continue;
            $info = json_decode(file_get_contents($jfile));
            if(!isset($info->nav) or !is_array($info->nav)){
                $info->nav = null;
            }
            if(!isset($info->permissions)) continue;
            //print_r(array_intersect($info->permissions, $user_permission));
            $got_any_permission = (bool) array_intersect($info->permissions, $user_permission);
             if(!$got_any_permission) continue;
            $nav[] = [
                'href'=>str_replace('//','/', "/{$home}/$dir"),
                'icon'=>$info->icon,
                'name'=>$info->name,
                'children'=>$info->nav
            ];
        }
        $pdata = self::get_sub_template($template_name, $data);
        $user = self::init()->get_session_user();
        ob_start();
        $site_name = $storage->system_config->system_name;
        $page_title = "Dashboard &raquo; {$template_name}";
        $root = realpath(__DIR__.'/../system/templates/')."/{$storage->system_config->theme}";
        $base = trim("{$storage->request_dir}/rand/system/assets/",'/');
        include $root.'/index.html';
    }
    public static function send_email($opts){
        require realpath(__DIR__.'/../../')."/vendor/autoload.php";
        //use PHPMailer\PHPMailer\PHPMailer;
        //use PHPMailer\PHPMailer\Exception;

        $developmentMode = false;
        $config = (array)storage::init()->system_config->phpmailer;
        $config['sender_name'] = isset($config['sender_name']) ? @$config['sender_name'] : '';
        $opts['recipient_name'] = isset($opts['recipient_name']) ? $opts['recipient_name'] : '';
        
        $mailer = new PHPMailer\PHPMailer\PHPMailer($developmentMode);
        try {
            $mailer->SMTPDebug = intval($developmentMode);
            $mailer->isSMTP();
            if ($developmentMode) {
                $mailer->SMTPOptions = [
                    'ssl'=> [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }
            $mailer->Host = $config['host'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->SMTPSecure = 'tls';
            $mailer->Port = 587;
            $mailer->setFrom($config['sender'], $config['sender_name']);
            $mailer->addAddress($opts['recipient'], $opts['recipient_name']);
            $mailer->isHTML(true);
            $mailer->Subject = $opts['subject'];
            $mailer->Body = $opts['body'];
            $ok = $mailer->send();
            $mailer->ClearAllRecipients();
            if($ok) return 'mail_send_ok';
            else return $mailer->ErrorInfo;
        } 
        catch (Exception $e) {
            return $mailer->ErrorInfo;
        }
    }
    public static function send_sms($opts){

        $curl = curl_init();
        $config = storage::init()->system_config->nextsms;
        $reference = self::create_hash(microtime(true));
        if(!is_array($opts['recipients'])) $opts['recipients'] = [$opts['recipients']];
        $reqPayload = [
            "from" => $config->sender,
            "to" => $opts['recipients'],
            "text" => $opts['body'],
            "reference" => $reference,
        ];

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://messaging-service.co.tz/api/sms/v1/text/single',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($reqPayload),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic {$config->token}",
            'Content-Type: application/json',
            'Accept: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $res = json_decode($response);
        //var_dump($res);
        if(isset($res->messages) && isset($res->messages->to) && isset($res->messages->to->id)) return $reference;
        else return false;
        /*{
            "messages":[
                {
                    "to":"255757569016","status":
                    {
                        "groupId":105,
                        "groupName":"PENDING",
                        "id":7,
                        "name":"PENDING_ENROUTE",
                        "description":"Message sent to next instance"
                    },
                    "messageId":"8250397020021226786",
                    "smsCount":3
                }
            ]
        }*/
    }
    public static function get_sub_template($template_name, $data = []){
        $storage = storage::init();
        $home = str_replace('//','/', "/{$storage->request_dir}/{$storage->request[0]}");
        if(isset($storage->request[1])) $myhome = "$home/{$storage->request[1]}";

        if(is_array($data) or is_object($data)) extract($data);
        ob_start();
        $root = realpath(__DIR__.'/../system/templates/')."/{$storage->system_config->theme}";
        $base = trim("{$storage->request_dir}/rand/system/assets/",'/');
        if(is_readable($root."/{$template_name}.html")) include $root."/{$template_name}.html";
        else include $root."/404.html";
        return ob_get_clean();
    }

}