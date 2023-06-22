<?php 

$user = $db->select('user')
    ->join('tenants','user_id=user_reference', 'left')
    ->join('orders', 'orders.order_customer=user.user_id', 'LEFT')
    ->join('apartment_category', 'orders.apartment_category=apartment_category.category_id', 'LEFT')
    ->join('check_scheduling', 'orders.check_schedule=check_scheduling.check_id', 'LEFT')
    ->where(['user_id'=>helper::init()->get_session_user('user_id')])
    ->fetch();
$data = [
    'user' => $user, 
    // 'totalRequests' => $totalRequests
];
echo helper::find_template('contract', $data);
