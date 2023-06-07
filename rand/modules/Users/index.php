<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['full_name'])){
    $data = [
        'full_name'=>$_POST['full_name'], 
        'tenants_email'=>$_POST['tenants_email'], 
        'resident_adress'=>$_POST['residence_address'],
        'phone_number'=>$_POST['phone_number'], 
        'adults'=>$_POST['adults'],
        'children'=>$_POST['children'],
        'occupied_apartment'=>$_POST['occupied_apartment'], 
        'date_in'=>$_POST['date_in'],
        'date_out'=>$_POST['date_out']
    ];
    //var_dump($db->error());
    $k = $db->insert('tenants', $data);
   
    if(!$db->error() && $k) {
        $msg = 'tenants added successful';
        $ok =true;
    }
    else $msg = 'Error adding tenants';
    //var_dump($db->error());
}
$tenants = $db->select('tenants')->order_by('tenants_id', 'desc')->fetchAll();
$data = ['tenants'=>$tenants,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('users', $data);


