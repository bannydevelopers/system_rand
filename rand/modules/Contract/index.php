<?php 

$contracts = $db->select('user')
    ->join('tenants','user.user_id=user_reference', 'left')
    ->join('check_scheduling', 'user.user_id = check_scheduling.user_ref', 'LEFT')
    ->join('invoice', 'invoice.check_scheduling = check_scheduling.check_id')
    ->join('apartment_category', 'invoice.apartment_cat_reference = apartment_category.category_id', 'LEFT')
    ->where(['user_id'=>helper::init()->get_session_user('user_id')])
    ->fetchAll();
$data = [
    'contracts' => $contracts 
];
echo helper::find_template('contract', $data);
