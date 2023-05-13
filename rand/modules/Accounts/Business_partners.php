<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];

if(isset($_POST['business_partners_name'])){
    $data = [
        'business_partner_name'=>$_POST['business_partner_name'], 
        'business_partner_phone_number'=>$_POST['business_partner_phone_number'], 
        'business_partner_details'=>$_POST['business_partner_details'], 
        'created_time'=>date('Y-m-d H:i:s')
    ];
    $k = $db->insert('business_partners', $data);
    if(!$db->error() && $k) {
        $msg = 'business_partners added successful';
        $ok =true;
    }
    else $msg = 'Error adding business_partners';
    var_dump($db->error());
}
$business_partners = $db->select('business_partners')->order_by('business_partners_id', 'desc')->fetchAll();
$data = ['business_partners'=>$business_partners,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request];
echo helper::find_template('business_partners', $data);