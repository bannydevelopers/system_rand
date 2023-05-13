<?php 
$children = ["Payroll","Salary",'Expenses','Debts',"Revenues","Business_partners","Banks","Loan"];
$req = storage::init()->request;

$db = db::get_connection(storage::init()->system_config->database);
if(!isset($req[2])){
    echo helper::find_template('Accounts', []);
}
else{
    if(in_array($req[2], $children)){
        // Work the thing
        include "{$req[2]}.php";
    }
}