<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];

if(isset($_POST['customer_name'])){
    $data = [
        'customer_name'=>$_POST['customer_name'], 
        'customer_phone_number'=>$_POST['phone'], 
        'customer_email'=>$_POST['email'], 
        'customer_physical_adress'=>$_POST['address'], 
        'tin_number'=>$_POST['tin'], 
        'vrn_number'=>$_POST['vrn'], 
        'created_time'=>date('Y-m-d H:i:s')
    ];
    $k = $db->insert('customer', $data);
    if(!$db->error() && $k) {
        $msg = 'Customer added successful';
        $ok =true;
    }
    else $msg = 'Error adding customer';
    //var_dump($db->error());
}
$customer = $db->select('customer')->order_by('customer_id', 'desc')->fetchAll();
$data = ['customer'=>$customer,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('Customers', $data);