<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';


$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['supplier_name'])){
    $data = [
        'supplier_name'=>$_POST['supplier_name'], 
        'supplier_phone_number'=>$_POST['supplier_phone_number'], 
        'supplier_email'=>$_POST['supplier_email'],
        'supplier_physical_address'=>$_POST['supplier_physical_address'],
        'supplier_details'=>$_POST['supplier_details'],
        'create_time'=>date('Y-m-d H:i:s')
        
    ];
    $k = $db->insert('supplier', $data);
    //var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'supplier added successful';
        $ok =true;
    }
    else $msg = 'Error adding supplier';
    //var_dump($db->error());
}
 $supplier = $db->select('supplier')->order_by('supplier_id', 'desc')->fetchAll();
$data = ['supplier'=>$supplier,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('suppliers', $data);