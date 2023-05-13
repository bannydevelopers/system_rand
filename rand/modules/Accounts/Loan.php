<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
//var_dump($_REQUEST);
if(isset($_POST['debt_description'])){
    $data = [
        'debt_date'=>$_POST['debt_date'], 
        'debt_description'=>$_POST['debt_description'], 
        'debt_amount'=>$_POST['debt_amount'], 
        'debt_party_type'
              =>$_POST['debt_Party_type'],
        'debt_type'=>$_POST['debt_type'],
        'debt_party_id'=>$_POST['debt_party_id'],
        'create_time'=>date('Y-m-d H:i:s')
        
    ];
   //var_dump($db->error());
    $k = $db->insert('debts', $data);
   
    if(!$db->error() && $k) {
        $msg = 'loan added successful';
        $ok =true;
    }
    else $msg = 'Error adding loan';
    //var_dump($db->error());
}
$debt = $db->select('debts')->where(['debt_type'=>'loan'])->order_by('debt_id', 'desc')->fetchAll();
//var_dump($db->error());
$data = ['debt'=>$debt,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('loan', $data);
