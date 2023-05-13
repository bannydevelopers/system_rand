<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';


$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['invoice_date'])){
    $data = [
        'invoice_date'=>$_POST['invoice_date'], 
        'invoice_type'=>$_POST['invoice_type'], 
        'ref_number'=>$_POST['ref_number'],
        'invoice_amount'=>$_POST['invoice_amount'],
        'invoice_expire_date'=>$_POST['invoice_expire_date'],
        'customer_id'=>$_POST['customer_id'],
        'created_time'=>date('Y-m-d H:i:s')
        
    ];
    $k = $db->insert('invoice', $data);
    var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'invoice added successful';
        $ok =true;
    }
    else $msg = 'Error adding invoice';
    //var_dump($db->error());
}
 $customer= $db->select('customer','customer_id,customer_name')
                  ->fetchALL();


$invoice = $db->select('invoice')->order_by('invoice_id', 'desc')->fetchAll();
$data = ['invoice'=>$invoice,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('invoice', $data);