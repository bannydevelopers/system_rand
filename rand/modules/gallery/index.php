<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';
$request = $_SERVER['REQUEST_URI'];

function convert_filesize($bytes, $decimals = 2) { 
    $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB'); 
    $factor = floor((strlen($bytes) - 1) / 3); 
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor]; 
}

function compressImage($source, $destination, $quality) { 
    // Get image info 
    $imgInfo = getimagesize($source); 
    $mime = $imgInfo['mime']; 
    
    // Create a new image from file 
    switch($mime){ 
        case 'image/jpeg': 
            $image = imagecreatefromjpeg($source); 
            break;
        case 'image/png': 
            $image = imagecreatefrompng($source); 
            break; 
        case 'image/gif': 
            $image = imagecreatefromgif($source); 
            break; 
        default: 
            $image = imagecreatefromjpeg($source); 
    } 
    // Save image 
    imagejpeg($image, $destination, $quality); 
    // Return compressed image 
    return $destination; 
}

if(isset($_POST["upload-images"])){ 
    $uploadPath = "rand/system/assets/uploads/gallery/";
    $images = array_filter($_FILES['images']["name"]);
    if(!empty($images)) { 
        foreach($_FILES['images']['name'] as $key => $val){
            $fileName = basename($_FILES["images"]["name"][$key]);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            $imageUploadPath = $uploadPath . $fileName;

            $allowTypes = array('jpg','png','jpeg','gif'); 
            if(in_array($fileType, $allowTypes)){ 
                
                // if (!file_exists($imageUploadPath)) {
                    $imgSize = $_FILES["images"]["size"][$key];
                    $imageSize = convert_filesize($imgSize); 
                    if($imageSize > 1600000) {$setQuality = 50;}
                    else if($imageSize > 1600000) $setQuality = 60;
                    else if($imageSize > 800000) $setQuality = 70;
                    else if($imageSize > 400000) $setQuality = 80;
                    else if($imageSize > 200000) $setQuality = 90;
                    else $setQuality = 100;

                    // Image temp source and size
                    $imageTemp = $_FILES["images"]["tmp_name"][$key]; 
                    // Compress size and upload image 
                    $compressedImage = compressImage($imageTemp, $imageUploadPath, $setQuality); 
                    
                    if($compressedImage){ 
                        $k = $db->insert('gallery', ['img_name'=>$fileName]);
                        if(!$db->error() && $k) {
                            $status = 'success'; 
                            $msg = "Image uploaded successfully."; 
                        }
                        else $msg = "Something went wrong! Try another time.";
                    }
                    else $msg = "Something went wrong! Try another image."; 
                // }
                // else  $msg = "Sorry, image already exists."; 
            }
            else $msg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
        }
    }
    else $msg = 'Please select an image file to upload.';  
}

if(isset($_POST['ajax_del_photo'])){
    if($helper->user_can('can_delete_on_gallery')){
        $uploadPath = "rand/system/assets/uploads/gallery/";
        $countSuccess = 0;
        $countFail = 0;
        foreach($_POST['images'] as $filename){
            $k = $db->delete('gallery')->where(['img_name'=>$filename])->commit();
            if(!$db->error() && $k) {
                if(file_exists($uploadPath.$filename)) {
                    unlink($uploadPath.$filename);
                    $countSuccess++;
                }
            }
            else $countFail++;
            if($countSuccess > 0) {
                $msg = $countSuccess.' Images has been deleted succesfully.';
                $status = 'success';
            }
            else if($countFail > 0) $msg = $countFail.' Images fails to be deleted!';
            else $msg = 'Select Images to delete.';
        }
    }
    else $msg = 'Permission denied';
    die(json_encode(['status'=>$status,'msg'=> $msg]));
}

$images = $db->select('gallery')
        ->order_by('img_id', 'desc')
        ->fetchAll();
$apartment_categories = $db->select('apartment_category', 'category_id,category_name')->fetchAll();

$data = [
    'images'=>$images, 
    'msg'=>$msg, 
    'status'=>$status, 
    'request_uri'=>$request,
    'apartment_categories' => $apartment_categories
];
echo helper::find_template('gallery', $data);