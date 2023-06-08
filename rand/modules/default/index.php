<?php 

$db = db::get_connection(storage::init()->system_config->database);


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


$apartmentCategories;
$orders = $db->select('orders','SUM(payment_amount) AS payment_amount, orders.apartment_category')
                    ->group_by('apartment_category')
                    ->join('apartment_category', 'apartment_category.category_name, apartment_category.category_id')
                    ->where('orders.apartment_category=apartment_category.category_id')
                    ->fetchAll();print(json_encode($orders));die();
// $expensesSum = 0;
$aparCategory = [];
foreach($apartmentCategories as $apartmentCategory) {
    // $expensesSum += $expense['expenses_amount'];
    array_push($aparCategory, $apartmentCategory['category_name']);
}
// print(json_encode($aparCategory));die();


$data = [
    'apartmentCount' => $apartmentCount, 
    'aparCategory' => json_encode($aparCategory), 
    'staffCount' => $staffCount, 
    'bookedCount' => $bookedCount, 
    'expensesSum' => $expensesSum,
    // 'ordersSum' => $ordersSum,
    'expensesAmount' => json_encode($expensesAmount),
    'expensesDate' => json_encode($expensesDate)
];

echo helper::find_template('home', $data);