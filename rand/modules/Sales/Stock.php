<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';
if(isset($_POST['customer_name'])){
    $data = [
        'customer_name'=>$_POST['customer_name'], 
        'phone_number'=>$_POST['phone'], 
        'email'=>$_POST['email'], 
        'physical_adress'=>$_POST['address'], 
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
}
$customer = $db->select('customer')->order_by('customer_id', 'desc')->fetchAll();
echo helper::find_template('Stock', ['customer'=>$customer,'msg'=>$msg, 'status'=>$ok]);