<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['add-to-gallery'])){
    $check = getimagesize($_FILES["img_name"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
    // $img_names = $_POST['img_names'];
    // foreach($img_names as $img_name){
    //     // upload_image($img_name, '', '');
    //     $k = $db->insert('gallery', ['img_name'=>$img_name]);
    // }
    // if(!$db->error() && $k) {
    //     $msg = 'image added successful';
    //     $status = 'success';
    // }
    // else $msg = 'Error adding image';
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
$apartment_categories = $db->select('apartment_category', 'category_id,category_name')->fetchAll();

$data = [
    'images'=>$images, 
    'msg'=>$msg, 'status'=>$status, 
    'request_uri'=>$request,
    'apartment_categories' => $apartment_categories
];
echo helper::find_template('gallery', $data);