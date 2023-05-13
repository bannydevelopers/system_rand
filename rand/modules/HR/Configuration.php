<?php 
$db = db::get_connection(storage::init()->system_config->database);
$msg = '';
$request = $_SERVER['REQUEST_URI'];

$data = [];

if(!$helper->user_can('can_edit_hr_configuration')){
    echo helper::find_template('hr_configuration', $data);
}
else echo helper::find_template('permission_denied');