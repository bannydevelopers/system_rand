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
            'status'=>'pending',
            'requester'=>$my['user_id']
            ];
        $k = $db->insert('requests', $data);
        if(!$db->error() && $k) {
            $msg = 'requests added successful';
            $status = 'success';
        }
        else $msg = 'Error adding requests';
    }
    else $msg = 'Permission to add request denied';
}

if(isset($_POST['edit-request'])){
    if($helper->user_can('can_edit_requests')){
        $data = ['descriptions'=>$_POST['descriptions']];
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
$requests = $db->select('requests')->where(['requester'=>$my['user_id']])->order_by('requests_id', 'desc')->fetchAll();
}
else $requests = $db->select('requests')->order_by('requests_id', 'desc')->fetchAll();

$data = ['requests'=>$requests,'msg'=>$msg, 'status'=>$status,'request_uri'=>$request];
echo helper::find_template('requests', $data);

