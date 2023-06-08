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


$expenses = $db->select('expenses','expenses.expenses_amount,expenses.expenses_date')
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


$orders = $db->select('orders','orders.payment_amount')
                ->fetchAll();
$ordersSum = 0;
foreach($orders as $order) {
    $ordersSum += $order['payment_amount'];
}


$apartmentG = $db->select('apartments','apartments.apartment_block')
                ->group_by('apartment_block')->fetchAll();
// $apartmentArray = [];
// foreach($apartmentG as $apartmentBlock) {
//     $apartmentArray = array_merge($apartmentArray, $apartmentBlock);
// }
// print_r($apartmentArray);
// die();


$data = [
    'apartmentCount' => $apartmentCount, 
    'apartmentG' => $apartmentG, 
    'staffCount' => $staffCount, 
    'bookedCount' => $bookedCount, 
    'expensesSum' => $expensesSum,
    'ordersSum' => $ordersSum,
    'expensesAmount' => json_encode($expensesAmount),
    'expensesDate' => json_encode($expensesDate)
];

echo helper::find_template('home', $data);