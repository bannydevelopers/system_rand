<?php 
$status = 'fail';
$msg = '';
$db = db::get_connection(storage::init()->system_config->database);
$my = helper::init()->get_session_user();

if(isset($_POST["update-my-profile"])){
    $names = explode(' ', addslashes($_POST['full_name']));
    $fn = $names[0];
    $ln = end($names);
    array_shift($names);
    array_pop($names);
    $mn = implode(' ', $names);
    $user = [
        'first_name'=>$fn,
        'middle_name'=>$mn,
        'last_name'=>$ln,
        'phone_number'=>helper::format_phone_number($_POST['phone_number']), //
        'email'=>helper::format_email($_POST['email']), //
        //'password'=>md5($token), 
    ];
    $whr ="(email='{$user['email']}' OR phone_number = '{$user['phone_number']}')";
    $test = $db->select('staff')
            ->join('user','user_reference=user_id')
            ->where($whr)
            ->and("user_id != {$my['user_id']}")
            ->fetch();
    $next = [
        'residence_address'=>addslashes($_POST['residence_address']), 
    ];
    if(!$test){
        $db->update('user', $user)->where(['user_id' => $my['user_id']])->commit();
        if($helper->user_can('can_add_requests')){
            $db->update('tenants', $next)->where(['user_reference'=>$my['user_id']])->commit();
            if(!$db->error()) {
                $msg = 'Profile updated successful!';
                $status = 'success';
            }
            else $msg = 'Something went wrong!';
        }
        else{
            $db->update('staff', $next)->where(['user_reference'=>$my['user_id']])->commit();
            if(!$db->error()) {
                $msg = 'Profile updated successful!';
                $status = 'success';
            }
            else $msg = 'Something went wrong!';
        }
    }
    else $msg = 'Email OR Phone number already taken!';
}

if(isset($_POST['update-my-password'])){
    $chek = $db->select('user', 'user_id, password')
            ->where([
                'password' => helper::create_hash($_POST['old_password']), 
                'user_id' => $my['user_id']
                ])
            ->fetch();
    if($chek){
        if($_POST['new_password1'] == $_POST['new_password2']){
            $data = ['password'=>helper::create_hash($_POST['new_password1']),];
            $put = $db->update('user', $data)->where(['user_id' => $my['user_id']])->commit();
            if(!$db->error() && $put){
                $msg = 'Password successful changed.';
                $status = 'success';
            }
            else $msg = 'Unexpected error occured';
        }
        else $msg = 'Passwords mismatch';
    }
    else $msg = 'Incorrect current password';
}

if(isset($_POST["upload-image"])){ 

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

    $uploadPath = "rand/system/assets/uploads/avatar/";  
    if(!empty($_FILES["image"]["name"])) { 
        $fileName = basename($_FILES["image"]["name"]); 
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); 
        // $imageUploadPath = $uploadPath . $fileName;
        $imageUploadPath = $uploadPath . 'avatar_' . $my['user_id'] . '.' . $fileType;

        $allowTypes = array('jpg','png','jpeg','gif'); 
        if(in_array($fileType, $allowTypes)){ 
            
            // if (!file_exists($imageUploadPath)) {
                $imgSize = $_FILES["image"]["size"];
                $imageSize = convert_filesize($imgSize); 
                if($imageSize > 1600000) {$setQuality = 50;}
                else if($imageSize > 1200000) $setQuality = 60;
                else if($imageSize > 800000) $setQuality = 70;
                else if($imageSize > 400000) $setQuality = 80;
                else $setQuality = 90;

                // Image temp source and size 
                $imageTemp = $_FILES["image"]["tmp_name"]; 
                // Compress size and upload image 
                $compressedImage = compressImage($imageTemp, $imageUploadPath, $setQuality); 
                
                if($compressedImage){ 
                    $compressedImageSize = filesize($compressedImage); 
                    // $compressedImageSize = convert_filesize($compressedImageSize); 
                    $status = 'success'; 
                    $msg = "Image uploaded successfully."; 
                }
                else $msg = "Something went wrong! Try another image."; 
            // }
            // else  $msg = "Sorry, image already exists."; 
        }
        else $msg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
    }
    else $msg = 'Please select an image file to upload.'; 
}

if(helper::init()->user_can('can_add_requests')){
    $user = $db->select('user')
    ->join('tenants','user_id=user_reference', 'left')
    ->where(['user_id'=>helper::init()->get_session_user('user_id')])
    ->fetch();
} else {
    $user = $db->select('user')
    ->join('staff','user_id=user_reference', 'left')
    ->where(['user_id'=>helper::init()->get_session_user('user_id')])
    ->fetch();
}

$data = [
    'user'=>$user,
    'msg' => $msg,
    'status' => $status
];
echo helper::find_template('profile', $data);