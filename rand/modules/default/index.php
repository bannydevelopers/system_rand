<?php 

$db = db::get_connection(storage::init()->system_config->database);

$apartment = "SELECT COUNT(apartment_id) FROM apartments";
$apartment = $db->query($apartment);
$apartmentC = $apartment->fetchColumn();

$staff = "SELECT COUNT(staff_id) FROM staff";
$staff = $db->query($staff);
$staffC = $staff->fetchColumn();

$booked = "SELECT COUNT(check_id) FROM check_scheduling";
$booked = $db->query($booked);
$bookedC = $booked->fetchColumn();

$expenses = $db->select('expenses','expenses.expenses_amount,expenses.expenses_date')
                ->order_by('expenses_date', 'desc')->fetchAll();
$expenseSum = 0;
foreach($expenses as $expense) {
    $expenseSum += $expense['expenses_amount'];
}

$apartmentG = $db->select('apartments','apartments.apartment_block')
                ->group_by('apartment_block')->fetchAll();
// $apartmentArray = [];
// foreach($apartmentG as $apartmentBlock) {
//     $apartmentArray = array_merge($apartmentArray, $apartmentBlock);
// }
// print_r($apartmentArray);
// die();

$data = ['apartmentC'=>$apartmentC, 'apartmentG'=>$apartmentG, 'staffC'=>$staffC, 'bookedC'=>$bookedC, 'expenseSum'=>$expenseSum];

echo helper::find_template('home', $data);