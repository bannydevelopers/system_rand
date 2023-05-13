<?php 

$db = db::get_connection(storage::init()->system_config->database);
$my = helper::init()->get_session_user();
$me = $db->select('staff')->where(['user_reference'=>$my['user_id']])->fetch();

$msg = '';
if(isset($_POST['leave_type'])){
    $data = [
        'staff_id'=>$me['staff_id'], 
        'leave_application_description'=>addslashes($_POST['leave_application_description']), 
        'leave_start_date'=>helper::format_time($_POST['leave_start'], 'Y-m-d H:i:s'), 
        'leave_application_type'=>intval($_POST['leave_type']), 
        'leave_length'=>intval($_POST['leave_length']), 
        'application_date'=>date('Y-m-d H:i:s'), 
        'responsibility_assignee'=>intval($_POST['responsibility_assignee'])
          
    ];
    if(!intval($data['responsibility_assignee'])) $data['assignee_response_date'] = date('Y-m-d H:i:s');
    $k = $db->insert('leave_application', $data);
    if(!$db->error() && $k) $msg = 'Leave application is a success';
    else $msg = 'Leave application failed';
    //var_dump($db->error());
}
$leave = $db->select('leave_application', 'leave_application.*,user.first_name,user.last_name,user.middle_name')
            ->join('staff','staff.staff_id=leave_application.staff_id','left') // It's here to link other tables
            ->join('user','user_id=user_reference','left')
            ->where("leave_application.staff_id != {$me['staff_id']}")
            ->fetchAll();
            //var_dump('<pre>',$leave);
$assignee = $db->select('user')
               ->where("system_role='{$my['system_role']}' AND user_id != '{$my['user_id']}'")
               ->fetchAll();

 $roles = $db->select('role','role_id,role_name')
              ->order_by('role_name', 'asc')
              ->fetchAll();
                              
echo helper::find_template('leave', ['assignee'=>$assignee,'leave'=>$leave,'msg'=>$msg,'roles'=>$roles]);