<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';
$me = helper::init()->get_session_user();

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['store_name'])){
    $data = [
        'store_name'=>$_POST['store_name'], 
        'store_location'=>$_POST['store_location'], 
        'staff_id'=>$me['user_id'],
        'created_time'=>date('Y-m-d H:i:s')
        
    ];
    //var_dump($db->error());
    $k = $db->insert('store', $data);
   
    if(!$db->error() && $k) {
        $msg = 'store added successful';
        $ok =true;
    }
    else $msg = 'Error adding store';
    //var_dump($db->error());
}
$staff= $db->select('staff','staff_id,staff_name')
                  ->fetchALL();


$store = $db->select('store')->order_by('store_id', 'desc')->fetchAll();
$data = ['store'=>$store,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('stores', $data);