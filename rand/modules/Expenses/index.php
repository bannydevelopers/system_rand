<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['expenses_description'])){
    $data = [
        'expenses_date'=>$_POST['expenses_date'], 
        'expenses_number_item'=>$_POST['expenses_number_item'], 
        'expenses_description'=>$_POST['expenses_description'], 
        'expenses_amount'=>$_POST['expenses_amount'],
        'purchased_by'=>$_POST['purchased_by'], 
        'approved_by'=>$_POST['approved_by'],
        'approval_date'=>date('Y-m-d H:i:s')  
       ];
    $k = $db->insert('expenses', $data);
    //var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'expenses added successful';
        $ok =true;
    }
    else $msg = 'Error adding expenses';
   //var_dump($db->error());
}
$expenses = $db->select('expenses')->order_by('expenses_id', 'desc')->fetchAll();
$data = ['expenses'=>$expenses,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('expenses', $data);

