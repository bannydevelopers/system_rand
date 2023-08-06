<?php 

$db = db::get_connection(storage::init()->system_config->database);
$my = helper::init()->get_session_user();
if($helper->user_can('can_add_requests')){
    $my = helper::init()->get_session_user();
    $requests = $db->select('requests','status, COUNT(requests_id) as requestCount')
        ->where(['requester'=>$my['user_id']])->group_by('status')
        ->fetchAll();
    $myApartments = $db->select('invoice', 'apartment_name, category_name, category_description, invoice.pay_status, user_id')
        ->join('check_scheduling','invoice.check_scheduling = check_scheduling.check_id', 'LEFT')
        ->join('apartments','check_scheduling.apartment_reference = apartments.apartment_id', 'LEFT')
        ->join('user','check_scheduling.user_ref = user.user_id', 'LEFT')
        ->join('apartment_category','invoice.apartment_cat_reference = apartment_category.category_id', 'LEFT')
        //->where(['user.user_id' => intval($my['user_id'])])
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
        'myApartments' => $myApartments,
        'me' => $my['user_id']
    ];
}
else{
    $apartment = "SELECT COUNT(apartment_id) FROM apartments";
    $apartment = $db->query($apartment);
    $apartmentCount = $apartment->fetchColumn();

    $staff = "SELECT COUNT(staff_id) FROM staff";
    $staff = $db->query($staff);
    $staffCount = $staff->fetchColumn();

    $booked = "SELECT COUNT(check_id) FROM check_scheduling WHERE check_out > NOW()";
    $booked = $db->query($booked);
    $bookedCount = $booked->fetchColumn();

    $expenses = $db->select('expenses','SUM(expenses_amount) AS expenses_amount, expenses_date')
                    ->group_by('expenses_date')
                    ->order_by('expenses_date', 'asc')
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
    // $takenApartments = $db->select('apartment_category','category_name, COUNT(invoice_amount) as invoicesCount, SUM(invoice_amount) as invoicesSum')
    //             ->join('invoice','apartment_category.category_id = invoice.apartment_cat_reference', 'left')
    //             ->join('check_scheduling','invoice.check_scheduling = check_scheduling.check_id')
    //             //->where('check_out >= CURRENT_TIMESTAMP')
    //             ->group_by('apartment_category.category_id')
    //             ->order_by('category_id','asc')
    //             ->fetchAll();var_dump($takenApartments);
    $takenApartments = $db->select('check_scheduling', 'category_name, COUNT(invoice_amount) as invoicesCount, SUM(invoice_amount) as invoicesSum')
                ->join('apartments', 'check_scheduling.apartment_reference = apartments.apartment_id', 'left')
                ->join('apartment_category' ,'apartments.apartment_category = apartment_category.category_id', 'left')
                ->join('invoice' ,'invoice.check_scheduling = check_scheduling.check_id', 'left')
                //->where('check_out >= CURRENT_TIMESTAMP')
                ->group_by('apartment_category.category_id')
                ->order_by('category_id','asc')
                ->fetchAll();
    $revenueSum = 0;
    $aparCategories = [];
    $occupiedApartments = [];
    $freeApartments = [];
    if($takenApartments && $apartments){
        foreach($takenApartments as $key => $invoice) {
            $revenueSum += $invoice['invoicesSum'];
            array_push($aparCategories, $invoice['category_name']);
            if($apartments[$key]['apartCount'] < $invoice['invoicesCount']) {
                array_push($freeApartments, 0);
            }else {
                array_push($freeApartments, $apartments[$key]['apartCount'] - $invoice['invoicesCount']);
            }
            array_push($occupiedApartments, $invoice['invoicesCount']);
        }
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