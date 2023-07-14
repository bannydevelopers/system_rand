<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['add-expense'])){
    $data = [
        'expenses_date'=>$_POST['expenses_date'], 
        'expenses_description'=>$_POST['expenses_description'], 
        'expenses_amount'=>$_POST['expenses_amount']
       
       ];
    $k = $db->insert('expenses', $data);
   
    if(!$db->error() && $k) {
        $msg = 'expense added successful';
        $status = 'success';
    }
    else $msg = 'Error adding expenses';
}
$expenses = $db->select('expenses')->order_by('expenses_id', 'desc')->fetchAll();
$data = [
    'expenses'=>$expenses,
    'msg'=>$msg, 
    'status'=>$status,
    'request_uri'=>$request,
    'currency'=>$storage->system_config->system_currency,
];
echo helper::find_template('expenses', $data);

