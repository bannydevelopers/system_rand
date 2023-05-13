<?php 

$db = db::get_connection(storage::init()->system_config->database);
$user = $db->select('staff')
           ->join('user','user_id=user_reference')
           ->where(['user_id'=>helper::init()->get_session_user('user_id')])
           ->fetch();
echo helper::find_template('profile', ['user'=>$user]);