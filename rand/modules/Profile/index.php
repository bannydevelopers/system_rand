<?php 

$db = db::get_connection(storage::init()->system_config->database);
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

if(isset($_POST['set-avatar'])){
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

    // File upload path 
    $uploadPath = "rand/system/assets/uploads/avatar/"; 
    
    $statusMsg = ''; 
    $status = 'danger'; 
    

    // Check whether user inputs are empty 
    if(!empty($_FILES["avatar"]["name"])) { 
        // File info 
        $fileName = basename($_FILES["avatar"]["name"]);  
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); 
        $my = helper::init()->get_session_user();
        $imageUploadPath = $uploadPath . 'avatar_' . $my['user_id'] . '.' . $fileType;
        
        // Allow certain file formats 
        $allowTypes = array('jpg','png','jpeg','gif'); 
        if(in_array($fileType, $allowTypes)){ 
            // Image temp source and size 
            $imageTemp = $_FILES["avatar"]["tmp_name"]; 
            $imageSize = convert_filesize($_FILES["avatar"]["size"]); 
            
            // Compress size and upload image 
            $compressedImage = compressImage($imageTemp, $imageUploadPath, 75); 
            
            if($compressedImage){ 
                $compressedImageSize = filesize($compressedImage); 
                $compressedImageSize = convert_filesize($compressedImageSize); 
                
                $status = 'success'; 
                $statusMsg = "Image compressed successfully."; 
            }else{ 
                $statusMsg = "Image compress failed!"; 
            } 
        }else{ 
            $statusMsg = 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.'; 
        } 
    }else{ 
        $statusMsg = 'Please select an image file to upload.'; 
    } 
    
}
echo helper::find_template('profile', ['user'=>$user]);