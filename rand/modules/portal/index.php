<?php 
function get_apartment_cards($opts = []){
    $db = db::get_connection(storage::init()->system_config->database);
    $today = date('Y-m-d H:i:s');
    $whr = 1;//"check_out is null or check_out IN (SELECT MAX(check_out) WHERE check_out < '{$today}')";
    $apartments = $db->select('apartments','*')
                     ->join('apartment_category', 'category_id=apartment_category')
                     ->join('check_scheduling', 'apartment_reference=apartment_id', 'LEFT')
                     ->where($whr)
                     ->fetchAll();
    $tree = [];
    if(!$db->error()){
        foreach($apartments as $apt){
            if(!isset($tree[$apt['category_name']])) $tree[$apt['category_name']] = [];
            $tree[$apt['category_name']][] = $apt;
        }
    }
    else print_r($db->error()['message']);

    return $tree;
}
function get_apartment_real_cards(){
    $db = db::get_connection(storage::init()->system_config->database);
    $cards = $db->select('apartment_category','*')
                     ->fetchAll();
    $tree = [];
    if(!$db->error()){
        return $cards;
    }
    else print_r($db->error()['message']);
}
$storage= storage::init();
$helper = helper::init();
$db = db::get_connection($storage->system_config->database);
if(isset($_POST['cust_namez'])){
    die(
         json_encode([
            'status'=>'success',
            'data.user_fullname'=>'DOA',
            'req'=>$_POST
        ])
         );
}
if(isset($_POST['ajax_newsletter'])){
    $newsletterData = [
        'newsletter_email'=>$_POST['email'], 
    ];
    $test = $db->select('newsletter')
            ->where(['newsletter_email'=>$newsletterData['newsletter_email']])
            ->fetch();
    if($test) {
        die(json_encode([
            'message'=>'Email already exist!'
         ]));
    }
    else {
        $newsletter = $db->insert('newsletter', $newsletterData);
        if(!$db->error() && $newsletter){
            die(json_encode([
                'message'=>'Successful subscribed to newsletters.'
            ]));
        } else {
            die(json_encode([
                'message'=>'Failed to subscribe! Try again.'
            ]));
        }
    }
}
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
$user = $helper->get_session_user();

$price = 0;
if(isset($_GET['book'])){
    $p = $db->select('apartment_category','price')
            ->where(['category_name'=>addslashes($_GET['book'])])
            ->fetch();
    if(!$db->error() && $p) $price = $p['price'];
}
if(isset($_POST['customer_name'])){//var_dump($_POST);die;
    if(strtotime($_POST['checkout']) > strtotime($_POST['checkin'])){
        $whr = "apartment_category = '{$_POST['apartment_category']}' AND apartment_id NOT IN (SELECT apartment_reference FROM check_scheduling WHERE check_out < '{$_POST['checkin']}')";
        $available = $db->select('apartments', 'apartment_id')
                        ->where($whr)
                        ->fetchAll();
        if($available){
            shuffle($available);
            // insert into check_schedule
            $schedule = [
                'check_in'=>$_POST['checkin'], 
                'check_out'=>$_POST['checkout'], 
                'check_status'=>'pending', 
                'apartment_reference'=>$available[0]['apartment_id']
            ];
            $check_schedule = $db->insert('check_scheduling', $schedule);
            if(!$db->error() && $check_schedule){
                // insert into orders
                $order = [
                    'special_requests'=>$_POST['special_request'], 
                    'apartment_category'=>$_POST['apartment_category'], 
                    'check_schedule'=>$check_schedule, 
                    'payment_amount'=>$_POST['order_amount'], 
                    'order_customer'=>$user['user_id']
                ];

                $k = $db->insert('orders', $order);
                if(!$db->error() && $k) {
                    $msg = 'Order added successful';
                    $ok = true;
                    $pg = storage::init()->system_config->payment_gateway;
                    $path = realpath(__DIR__.'/../../')."/system/gateways/payment/{$pg}/index.php";
                    if(is_readable($path)) {
                        include $path;
                        $name = explode(' ',$_POST['customer_name']);
                        $fn = $name[0];
                        $ln = end($name);
                        $post_data = [
                            'first_name'=>$fn,
                            'last_name'=>$ln,
                            'phone'=>helper::format_phone_number($_POST['phone_number']),
                            'email'=>helper::format_email($_POST['customer_email']),
                            'order_amount'=>200,
                            'order_description'=>'blah blah',
                            'order_reference'=>$k,
                            'country'=>$_POST['country']
                        ];
                        $iframe = send_request($post_data, storage::init()->system_config);
                        //var_dump('<pre>',$iframe);die;
                        include "rand/payment.html";
                    }
                    exit;
                }
                else {
                    // reverting...
                    $db->delete('check_scheduling')->where(['check_id'=>$check_schedule])->commit();
                    $msg = 'Error adding order';
                }
            }
        }
        $msg = 'The apartment selected is not available in selected dates';
    }
    else $msg = 'Invalid check-in or check-out dates';
}
if(isset($_REQUEST['OrderMerchantReference'])){
    $dash = "{$storage->request_dir}/{$storage->system_config->dashboard}";
    //OrderTrackingId=1ea70d3b-c05c-4da0-aaf8-e0465ac2596e&OrderMerchantReference=12
    $json = json_encode($_REQUEST);
    $oid = intval( $_REQUEST['OrderMerchantReference'] );
    $o = $db->update('orders', ['order_response'=>$json])
               ->where(['order_id'=>$oid])
               ->commit();
    
    $cs = $db->update('check_scheduling', ['check_status'=>'paid'])
             ->where("check_id IN (SELECT check_schedule FROM orders WHERE order_id = {$oid})")
             ->commit();
    header("Location: {$dash}");
    exit;
}


$page = '404';
$parts = explode('/',$_SERVER['REQUEST_URI']);
$req = end($parts);
$req = strtok($req, '?');
if(empty($req)) $page = 'index';
if(is_readable(__DIR__."/rand/{$req}.html")) $page = $req;
include "rand/{$page}.html";