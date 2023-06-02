<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['tenants_name'])){
    $data = [
        'tenants_name'=>$_POST['tenants_name'], 
        'tenants_function'=>$_POST['tenants_function'], 
        'tenants_status'=>$_POST['tenants_status'],
        'tenants_datein'=>$_POST['tenants_datein'],
        'created_time'=>date('Y-m-d H:i:s')
        
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
echo helper::find_template('Tenants', $data);

