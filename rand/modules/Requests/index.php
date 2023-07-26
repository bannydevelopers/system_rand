<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';
$my = helper::init()->get_session_user();
$request = $_SERVER['REQUEST_URI'];

if(isset($_POST['add-request'])){
    if($helper->user_can('can_add_requests')){
        $data = [
            'descriptions'=>$_POST['descriptions'], 
            'house'=>$_POST['apartment'], 
            'status'=>'pending',
            'requester'=>$my['user_id']
            ];
        $k = $db->insert('requests', $data);
        if(!$db->error() && $k) {
            $msg = 'Requests added successful';
            $status = 'success';
        }
        else $msg = 'Error adding requests';
    }
    else $msg = 'Permission to add request denied';
}

if(isset($_POST['edit-request'])){
    if($helper->user_can('can_edit_requests')){
        $data = [
            'descriptions'=>$_POST['descriptions'],
            'house'=>$_POST['apartment'], 
        ];
        $k = $db->update('requests', $data)
            ->where(['requests_id'=>intval($_POST['requests_id'])])
            ->commit();
        if(!$db->error() && $k) {
            $msg = 'Request updated successful';
            $status = 'success';
        }
        else $msg = 'Error updating requests';
    }
    else $msg = 'Permission to edit request denied';
}

if(isset($_POST['ajax_update_request'])){
    if($helper->user_can('can_edit_requests')){
        $k = $db->update('requests', ['status'=>$_POST['status']])
                ->where(['requests_id'=>intval($_POST['requests_id'])])
                ->commit();
        if(!$db->error() && $k){
            $msg = 'Request status updated';
            $status = 'success';
        } 
        else $msg = 'Request status updation failed';
    }
    else $msg = 'Permission denied for request update';
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

if(isset($_POST['ajax_del_req'])){
    if($helper->user_can('can_delete_requests')){
        $requests_id = intval($_POST['ajax_del_req']);
        $k = $db->delete('requests')->where(['requests_id'=>$requests_id])->commit();
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

if($helper->user_can('can_add_requests')){
$requests = $db->select('requests')
    ->join('apartments','requests.house=apartments.apartment_id', 'LEFT')
    ->where(['requester'=>$my['user_id']])
    ->order_by('requests_id', 'desc')
    ->fetchAll();
$myApartments = $db->select('user','apartment_id, apartment_name')
        ->join('tenants','user.user_id=user_reference')
        ->join('check_scheduling', 'user.user_id = check_scheduling.user_ref', 'LEFT')
        ->join('invoice', 'invoice.check_scheduling = check_scheduling.check_id')
        ->join('apartments','check_scheduling.apartment_reference=apartments.apartment_id', 'LEFT')
        ->where(['user_id'=>$my['user_id']])
        ->fetchAll();
}
else {
    $requests = $db->select('requests')
    ->join('apartments','requests.house=apartments.apartment_id', 'LEFT')
    ->order_by('requests_id', 'desc')->fetchAll();
    $myApartments=NULL;
}

$data = [
    'requests'=>$requests,
    'msg'=>$msg, 
    'status'=>$status, 
    'myApartments'=>$myApartments, 
    'request_uri'=>$request
];
echo helper::find_template('requests', $data);

