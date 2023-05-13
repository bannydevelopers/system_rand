<?php 
$children = ["Stock","Customers","Invoice","Purchase_order","Products","Suppliers"];
$req = storage::init()->request;
if(!isset($req[2])){
    echo helper::find_template('Sales', []);
}
else{
    if(in_array($req[2], $children)){
        // Work the thing
        include "{$req[2]}.php";
    }
}