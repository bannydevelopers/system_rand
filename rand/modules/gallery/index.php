<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['add-to-gallery'])){
    $data = [
        'img_name'=>$_POST['img_name'],
        'img_cat'=>$_POST['img_cat']
       ];
    $k = $db->insert('gallery', $data);
    if(!$db->error() && $k) {
        $msg = 'image added successful';
        $status = 'success';
    }
    else $msg = 'Error adding image';
}

if(isset($_POST['ajax_del_gallery'])){
    if($helper->user_can('can_delete_gallery')){
        $img_id = intval($_POST['ajax_del_gallery']);
        $k = $db->delete('gallery')->where(['img_id'=>$img_id])->commit();
        if(!$db->error() && $k) {
            $msg = 'Deletion succesfully';
            $status = 'success';
        }
        else {
            $msg = 'Deletion failed';
        }
    }
    else {
        $msg = 'Permission denied';
    }
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

$images = $db->select('gallery')
        ->order_by('img_id', 'desc')
        ->fetchAll();

$data = [
    'images'=>$images, 
    'msg'=>$msg, 'status'=>$status, 'request_uri'=>$request];
echo helper::find_template('gallery', $data);