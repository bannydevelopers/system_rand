<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['tool_name'])){
    $data = [
        'tool_name'=>$_POST['tool_name'], 
        'tool_description'=>$_POST['tool_description'], 
        'tool_quantity'=>$_POST['tool_quantity'], 
        'create_time'=>date('Y-m-d H:i:s')
        
    ];
    
    $k = $db->insert('tools', $data);
   
    if(!$db->error() && $k) {
        $msg = 'tool added successful';
        $ok =true;
    }
    else $msg = 'Error adding tool';
   //var_dump($db->error());
}
$tool = $db->select('tools')->order_by('tool_id', 'desc')->fetchAll();
//var_dump($tool);
$data = ['tool'=>$tool,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('tools', $data);