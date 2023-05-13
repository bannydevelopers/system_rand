<?php 
$children = ["Dispensing","Tools","Stores"];
$req = storage::init()->request;
if(!isset($req[2])){
    echo helper::find_template('Store', []);
}
else{
    if(in_array($req[2], $children)){
        // Work the thing
        include "{$req[2]}.php";
    }
}