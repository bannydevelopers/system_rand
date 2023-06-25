<?php 

$db = db::get_connection(storage::init()->system_config->database);

if($helper->user_can('can_add_requests')){
    $my = helper::init()->get_session_user();
    $requests = $db->select('requests','status, COUNT(requests_id) as requestCount')
        ->where(['requester'=>$my['user_id']])->group_by('status')
        ->fetchAll();
    $myApartments = $db->select('user','order_response, category_name, category_description, apartment_name')
        ->join('orders','user.user_id=orders.order_customer', 'LEFT')
        ->join('apartment_category','orders.apartment_category=apartment_category.category_id', 'LEFT')
        ->join('check_scheduling','orders.check_schedule=check_scheduling.check_id', 'LEFT')
        ->join('apartments','check_scheduling.apartment_reference=apartments.apartment_id', 'LEFT')
        ->where(['user_id'=>$my['user_id']])
        ->fetchAll();
                
    $totalRequests = 0;
    if (is_array($requests) || is_object($requests)) {
        foreach($requests as $request) {
        $totalRequests += $request['requestCount'];
        }
    }
    $data = [
        'requests' => $requests, 
        'totalRequests' => $totalRequests,
        'myApartments' => $myApartments
    ];
}
else{
    $apartment = "SELECT COUNT(apartment_id) FROM apartments";
    $apartment = $db->query($apartment);
    $apartmentCount = $apartment->fetchColumn();

    $staff = "SELECT COUNT(staff_id) FROM staff";
    $staff = $db->query($staff);
    $staffCount = $staff->fetchColumn();

    $booked = "SELECT COUNT(payment_amount) FROM orders WHERE orders.payment_amount";
    $booked = $db->query($booked);
    $bookedCount = $booked->fetchColumn();

    $expenses = $db->select('expenses','SUM(expenses_amount) AS expenses_amount, expenses_date')
                    ->group_by('expenses_date')
                    ->order_by('expenses_date', 'desc')
                    ->fetchAll();
    $expensesSum = 0;
    $expensesAmount = [];
    $expensesDate = [];
    foreach($expenses as $expense) {
        $expensesSum += $expense['expenses_amount'];
        array_push($expensesAmount, $expense['expenses_amount']);
        $bad_date = DateTime::createFromFormat('Y-m-d H:i:s e', $expense['expenses_date'].' EDT');
        $nice_date = $bad_date->format('Y M j');
        array_push($expensesDate, $nice_date);
    }

    $apartments = $db->select('apartment_category','category_name, COUNT(apartment_id) as apartCount')
                ->join('apartments','apartments.apartment_category=apartment_category.category_id')
                ->group_by('category_id')
                ->order_by('category_id','asc')
                ->fetchAll();
    $orders = $db->select('apartment_category','category_name, COUNT(payment_amount) as ordersCount, SUM(payment_amount) as ordersSum')
                ->join('orders','orders.apartment_category=apartment_category.category_id', 'left')
                ->group_by('apartment_category.category_id')
                ->order_by('category_id','asc')
                ->fetchAll();
    $revenueSum = 0;
    $aparCategories = [];
    $occupiedApartments = [];
    $freeApartments = [];
    foreach($orders as $key => $order) {
        $revenueSum += $order['ordersSum'];
        array_push($aparCategories, $order['category_name']);
        if($apartments[$key]['apartCount'] < $order['ordersCount']) {
            array_push($freeApartments, 0);
        }else {
            array_push($freeApartments, $apartments[$key]['apartCount'] - $order['ordersCount']);
        }
        array_push($occupiedApartments, $order['ordersCount']);
    }

    $data = [
        'apartmentCount' => $apartmentCount, 
        'staffCount' => $staffCount, 
        'bookedCount' => $bookedCount,
        'expensesSum' => $expensesSum,
        'revenueSum' => $revenueSum,
        'expensesAmount' => json_encode($expensesAmount),
        'expensesDate' => json_encode($expensesDate),
        'aparCategories' => json_encode($aparCategories), 
        'occupiedApartments' => json_encode($occupiedApartments),
        'freeApartments' => json_encode($freeApartments),
        'currency'=>$storage->system_config->system_currency
    ];
}

echo helper::find_template('home', $data);