<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];

if(isset($_POST['edit-expense'])){
    if($helper->user_can('can_edit_expenses')){
        $data = [
            'expenses_date'=>$_POST['expenses_date'],
            'expenses_description'=>$_POST['expenses_description'],
            'expenses_amount'=>$_POST['expenses_amount']
        ];
        $k = $db->update('expenses', $data)
                ->where(['expenses_id'=>intval($_POST['expenses_id'])])
                ->commit();
        if(!$db->error() && $k) {
            $msg = 'Expese updated successful';
            $status = 'success';
        }
        else $msg = 'Error updating expense';
    }
    else $msg = 'Permission denied';
}

if(isset($_POST['ajax_del_exp'])){
    if($helper->user_can('can_delete_expenses')){
        $expense_id = intval($_POST['ajax_del_exp']);
        $k = $db->delete('expenses')->where(['expenses_id'=>$expense_id])->commit();
        if(!$db->error() && $k) {
            $msg = 'Deletion succesfully';
            $status = 'success';
        }
        else {
            $msg = 'Deletion failed';
        }
    }
    else {
        $msg = 'Permission denied';
    }
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

if(isset($_POST['add-expense'])){
    $data = [
        'expenses_date'=>$_POST['expenses_date'], 
        'expenses_description'=>$_POST['expenses_description'], 
        'expenses_amount'=>$_POST['expenses_amount']
       
       ];
    $k = $db->insert('expenses', $data);
   
    if(!$db->error() && $k) {
        $msg = 'Expense added successful';
        $status = 'success';
    }
    else $msg = 'Error adding expenses';
}

$expenses = $db->select('expenses')->order_by('expenses_id', 'desc')->fetchAll();
$data = [
    'expenses'=>$expenses,
    'msg'=>$msg, 
    'status'=>$status,
    'request_uri'=>$request,
    'currency'=>$storage->system_config->system_currency,
];
echo helper::find_template('expenses', $data);

