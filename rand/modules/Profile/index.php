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
echo helper::find_template('profile', ['user'=>$user]);