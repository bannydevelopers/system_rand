<?php 
$conf = storage::init()->system_config;
$data = [
    'configs'=>$conf
];  
if(isset($_POST['system_name'])){
    foreach($_POST as $key=>$value){
        if(isset($conf->$key)){
            if(is_object($value)){
                foreach($value as $k=>$v) $conf->$key->$k = $v;
            }
            else $conf->$key = $value;
        }
    }
    $json = json_encode($conf, JSON_PRETTY_PRINT);
    $fn = realpath(__DIR__.'/../../config/conf.json');
    file_put_contents($fn, $json);
    header("Location: ./");
}
echo helper::find_template('System_config', $data);