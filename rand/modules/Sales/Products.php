<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['product_name'])){
    $data = [
        'product_name'=>$_POST['product_name'], 
        'product_description'=>$_POST['product_description'], 
        'product_unit'=>$_POST['product_unit'], 
        'created_time'=>date('Y-m-d H:i:s')
        
    ];
    //var_dump($db->error());
    $k = $db->insert('product', $data);
   
    if(!$db->error() && $k) {
        $msg = 'product added successful';
        $ok =true;
    }
    else $msg = 'Error adding product';
    //var_dump($db->error());
}
$product = $db->select('product')->order_by('product_id', 'desc')->fetchAll();
$data = ['product'=>$product,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('products', $data);