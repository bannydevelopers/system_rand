<?php 

$mod_config = json_decode(file_get_contents(__DIR__.'/module.json'));
if(isset($_POST['company_legal_name'])){
    $mod_config->config->company_profile->company_legal_name = $_POST['company_legal_name'];
    $mod_config->config->company_profile->company_slogan = $_POST['company_slogan'];
    $mod_config->config->company_profile->email = $_POST['email'];
    $mod_config->config->company_profile->TIN_number = $_POST['TIN_number'];
    $mod_config->config->company_profile->VRN_number = $_POST['VRN_number'];
    $mod_config->config->company_profile->company_address = $_POST['company_address'];
    if(isset($_FILES['company_logo']) && is_readable($_FILES['company_logo']['tmp_name']))
    {
        $fname = $_FILES['company_logo']['name'];
        $src = $_FILES['company_logo']['tmp_name'];
        $dst = realpath(__DIR__.'/../../system/assets/img/');

        move_uploaded_file($src, "{$dst}/{$fname}");
        if(is_readable("{$dst}/{$fname}"))
        {
            $mod_config->config->company_profile->company_logo = $fname;
        }
    }
    else{
        $mod_config->config->company_profile->company_logo = $mod_config->config->company_profile->company_logo;
    }
    file_put_contents(__DIR__.'/module.json', json_encode($mod_config, JSON_PRETTY_PRINT));
    // Refresh...
    $mod_config = json_decode(file_get_contents(__DIR__.'/module.json'));
}

echo helper::find_template('company_details', ['company_data'=>$mod_config->config->company_profile]);