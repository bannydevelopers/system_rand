<?php 
function get_apartment_cards($opts = []){
    $db = db::get_connection(storage::init()->system_config->database);
    $today = date('Y-m-d H:i:s');
    $whr = "check_out is null or check_out IN (SELECT MAX(check_out) WHERE check_out < '{$today}')";
    $apartments = $db->select('apartments','*')
                     ->join('apartment_category', 'category_id=apartment_category')
                     ->join('check_scheduling', 'apartment_reference=apartment_id', 'LEFT')
                     ->where($whr)
                     ->fetchAll();

    $tree = [];
    foreach($apartments as $apt){
        if(!isset($tree[$apt['category_name']])) $tree[$apt['category_name']] = [];
        $tree[$apt['category_name']][] = $apt;
    }
    //var_dump('<pre>',$tree);

    return $tree;
}

$helper = helper::init();
$user = $helper->get_session_user();
$db = db::get_connection(storage::init()->system_config->database);

if(isset($_POST['customer_name'])){
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
                    $ok =true;
                    include "rand/payment.html";
                    exit;
                }
                else {
                    // reverting...
                    $db->delete('check_scheduling')->where(['check_id'=>$check_schedule])->commit();
                    $msg = 'Error adding order';
                }
            }
        }
    }
    else $msg = 'Invalid check-in or check-out dates';
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


$page = '404';
$parts = explode('/',$_SERVER['REQUEST_URI']);
$req = end($parts);
$req = strtok($req, '?');
if(empty($req)) $page = 'index';
if(is_readable(__DIR__."/rand/{$req}.html")) $page = $req;
include "rand/{$page}.html";