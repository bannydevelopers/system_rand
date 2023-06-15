<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['add-faq'])){
    $data = [
        'faq_tittle'=>$_POST['question'],
        'faq_description'=>$_POST['answer']
       ];
    $k = $db->insert('faq', $data);
   
    if(!$db->error() && $k) {
        $msg = 'FAQ added successful';
        $status = 'success';
    }
    else $msg = 'Error adding question';
}

if(isset($_POST['edit-faq'])){
    $data = [
        'faq_tittle'=>$_POST['question'],
        'faq_description'=>$_POST['answer']
       ];
    $k = $db->update('faq', $data)
            ->where(['faq_id'=>intval($_POST['faq_id'])])
            ->commit();
    if(!$db->error() && $k) {
        $msg = 'FAQ updated successful';
        $status = 'success';
    }
    else $msg = 'Error updating question';
}

$faqs = $db->select('faq')
        ->order_by('faq_id', 'desc')
        ->fetchAll();

$data = [
    'faqs'=>$faqs, 
    'msg'=>$msg, 'status'=>$status, 'request_uri'=>$request];
echo helper::find_template('faq', $data);