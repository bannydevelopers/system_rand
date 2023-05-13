<?php 

spl_autoload_register(
    function ($class_name){
        $cls = __DIR__."/libs/{$class_name}_class.php";
        if(is_readable($cls)) include $cls;
    }
);
// System configs
$storage = storage::init();
$conf_data = file_get_contents(__DIR__.'/config/conf.json');
$storage->system_config = json_decode($conf_data, false);
unset($conf_data); // Reclaim memmory

// Replace \ with / to standardize
$docroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$install_dir = str_replace('\\', '/', realpath(__DIR__.'/../'));
$request = str_replace($docroot, '', $install_dir); // Find real requested url
$storage->request_dir = trim($request, '/');

// Reduce request to our file structure
if(!isset($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING'] = ''; // Some kidos complain it does not exist
$req = str_replace($request, '', str_replace($_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI'])); 

$req_parts = explode('/', trim($req,'/?'));
$storage->request = $req_parts;
$config = storage::init()->system_config;
//var_dump('<pre>',$req_parts);die;
if($req_parts[0] == $config->dashboard) include __DIR__.'/modules/dashboard.php';
else include __DIR__.'/modules/portal/index.php';