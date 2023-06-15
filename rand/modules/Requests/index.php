<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';
$my = helper::init()->get_session_user();

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['house_details'])){
    $data = [
        'house_details'=>$_POST['house_details'], 
        'descriptions'=>$_POST['descriptions'], 
        'submitted_date'=>$_POST['submitted_date'],
        'status'=>$_POST['status'],
        'status'=>$my['user_id']
        ];
    $k = $db->insert('requests', $data);
    var_dump($db->error());
    var_dump($k);
   
    if(!$db->error() && $k) {
        $msg = 'requests added successful';
        $ok =true;
    }
    else $msg = 'Error adding requests';
    //var_dump($db->error());
}
$requests = $db->select('requests')->where(['requester'=>$my['user_id']])->order_by('requests_id', 'desc')->fetchAll();
$data = ['requests'=>$requests,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('requests', $data);

