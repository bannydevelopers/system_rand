<?php 
$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$data['products'] = [];

echo helper::find_template('Sales', $data);