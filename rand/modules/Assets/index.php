<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['assets_name'])){
    $data = [
        'assets_name'=>$_POST['assets_name'], 
        'descriptions'=>$_POST['assets_description'], 
        'assets_quantity'=>$_POST['assets_quantity'], 
        'date_added'=>date('Y-m-d H:i:s')
        
    ];
    $k = $db->insert('assets', $data);
    var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'assets added successful';
        $ok =true;
    }
    else $msg = 'Error adding assets';
    //var_dump($db->error());
}
$assets = $db->select('assets')->order_by('assets_id', 'desc')->fetchAll();
$data = ['assets'=>$assets,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('Assets', $data);
