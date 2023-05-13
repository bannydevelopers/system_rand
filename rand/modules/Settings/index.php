<?php 
$children = ["System_config","System_backup","Payment_gateway","User_permission"];
$req = storage::init()->request;
if(!isset($req[2])){
    echo helper::find_template('Settings', []);
}
else{
    if(in_array($req[2], $children)){
        // Work the thing
        include "{$req[2]}.php";
    }
}