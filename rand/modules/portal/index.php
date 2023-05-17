<?php 
function get_apartment_cards($opts = []){
    $db = db::get_connection(storage::init()->system_config->database);
    $today = date('Y-m-d H:i:s');
    $whr = "check_out is null or check_out IN (SELECT MAX(check_out) WHERE check_out < '{$today}')";
    $apartments = $db->select('apartments','*')
                     ->join('apartment_category', 'category_id=apartment_category')
                     ->join('check_scheduling', 'apartment_reference=apartment_id', 'LEFT')
                     ->where($whr)
                     ->fetchAll();

    $tree = [];
    foreach($apartments as $apt){
        if(!isset($tree[$apt['category_name']])) $tree[$apt['category_name']] = [];
        $tree[$apt['category_name']][] = $apt;
    }
    //var_dump('<pre>',$tree);

    return $tree;
}
$page = '404';
$parts = explode('/',$_SERVER['REQUEST_URI']);
$req = end($parts);
$req = strtok($req, '?');
if(empty($req)) $page = 'index';
if(is_readable(__DIR__."/rand/{$req}.html")) $page = $req;
include "rand/{$page}.html";