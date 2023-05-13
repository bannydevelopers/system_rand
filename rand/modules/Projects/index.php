<?php 
$children = ["Projects","Activities","Resources","Project_report"];
$req = storage::init()->request;
if(!isset($req[2])){
    echo helper::find_template('Projects', []);
}
else{
    if(in_array($req[2], $children)){
        // Work the thing
        include "{$req[2]}.php";
    }
}