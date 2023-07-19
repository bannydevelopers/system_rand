<?php 

$children = ["Expenses","Invoices"];
$req = storage::init()->request;
if(in_array($req[2], $children)){
    // Work the thing
    include "{$req[2]}.php";
}