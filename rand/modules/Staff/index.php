<?php 
$db = db::get_connection(storage::init()->system_config->database);
$msg = '';
$status = 'fail';
$request = $_SERVER['REQUEST_URI'];

if(isset($_POST['ajax_del_staff'])){
    if($helper->user_can('can_delete_staff')){
        $staff_id = intval($_POST['ajax_del_staff']);
        $staff = $db->select('staff', 'user.user_id,user.first_name,user.middle_name,user.last_name')
                    ->join('user', 'user_id=user_reference')
                    ->where(['staff_id'=>$staff_id])
                    ->fetch();
        if($staff){
            $k = $db->delete('staff')->where(['staff_id'=>$staff_id])->commit();
            if(!$db->error() && $k) {
                $k = $db->delete('user')->where(['user_id'=>$staff['user_id']])->commit();
                $msg = "Deletion succesfully for staff {$staff['first_name']}.";
                $status = 'success';
            }
            else $msg = 'Deletion failed';
        }
        else $msg = 'Staff does not exist';
    }
    else $msg = 'Permission to delete staff denied';
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

if(isset($_POST['ajax_activate_user'])){
    if($helper->user_can('can_edit_staff')){
        $k = $db->update('user', ['status'=>$_POST['status']])
                ->where(['user_id'=>intval($_POST['ajax_activate_user'])])
                ->commit();
        if(!$db->error() && $k){
            if($_POST['status'] == 'active'){
                $msg = 'Account activated successful';
            }
            else{
                $msg = 'Account deactivated successful';
            }
            $status = 'success';
        }
        else $msg = 'Status update failed';
    }
    else $msg = 'Permission denied';
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

if(isset($_POST['add-designation'])){
    if($helper->user_can('can_add_designation')){
        $data = [
            'designation_name'=>addslashes($_POST['designation_name']),
            'designation_detail'=>addslashes($_POST['designation_details'])
        ];
        $k = $db->insert('designation', $data);
        if(intval($k)) {
            $msg = 'Designation added successful';
            $status = 'success';
        }
        else  $msg = 'Error adding designation. Possibly duplicate entry encounted';
    }
    else $msg = 'You do not have permission for the action';
}

if(isset($_POST['add-role'])){
    if($helper->user_can('can_add_role')){
        $role_id = $db->insert('role',['role_name'=>$_POST['role_name']]);
        if(intval($role_id)) {
            $msg = 'Role added successful';
            $status = 'success';
        }
        else $msg = 'Role adding failed, possibly a duplicate already exists';
    }
    else $msg = 'Permission denied';
}

if(isset($_POST['edit-staff'])){
    if($helper->user_can('can_edit_staff')){
        $names = explode(' ', addslashes($_POST['full_name']));
        $fn = $names[0];
        $ln = end($names);
        array_shift($names);
        array_pop($names);
        $mn = implode(' ', $names);
        $user = [
            'first_name'=>$fn,
            'middle_name'=>$mn,
            'last_name'=>$ln,
            'system_role'=>$_POST['role_id'],
            'phone_number'=>helper::format_phone_number($_POST['phone_number']), //
            'email'=>helper::format_email($_POST['email']), //
            //'password'=>md5($token), 
        ];
        $reg_no = addslashes($_POST['staff_registration_number']);
        $whr ="(email='{$user['email']}' OR staff_registration_number = '{$reg_no}')";
        $test = $db->select('staff')
                ->join('user','user_reference=user_id')
                ->where($whr)
                ->and("staff_id != {$_POST['staff_id']}")
                ->fetch();
        if(!$test){
            $db->update('user', $user)->where(['user_id' => $_POST['user_id']])->commit();

            $staff = [
                'staff_registration_number'=>$reg_no,
                'residence_address'=>addslashes($_POST['residence_address']), 
                'designation'=>addslashes($_POST['designation']), 
                'staff_date_employed'=>helper::format_time($_POST['date_employed'], 'Y-m-d H:i:s'),
            ];

            $db->update('staff', $staff)->where(['staff_id'=>$_POST['staff_id']])->commit();
            if(!$db->error()) {
                $msg = 'Updated successful!';
                $status = 'success';
            }
            else $msg = 'Something went wrong!';
        }
        else $msg = 'Some unique information already used by other employee';
    }
    else $msg = 'Not enough privilege, sorry';
}
    
if(isset($_POST['add-staff'])){
    if($helper->user_can('can_add_staff')){
        $token = helper::create_hash(time());
        $names = explode(' ', addslashes($_POST['full_name']));
        $fn = $names[0];
        $ln = end($names);
        array_shift($names);
        array_pop($names);
        $mn = implode(' ', $names);
        $user = [
            'first_name'=>$fn, 
            'middle_name'=>$mn, 
            'last_name'=>$ln, 
            'system_role'=>$_POST['role'], 
            'status'=>'active', 
            'phone_number'=>helper::format_phone_number($_POST['phone_number']), 
            'email'=>helper::format_email($_POST['email']), 
            'password' =>helper::create_hash('staff123'), 
            'activation_token'=>$token, 
            'created_by'=>helper::init()->get_session_user('user_id'), 
            'created_time'=>date('Y-m-d H:i:s')
        ];
        $test = $db->select('staff')
                ->join('user','user_reference=user_id')
                ->where(['email'=>$user['email']])
                ->or(['phone_number'=>$user['phone_number']])
                ->or(['staff_registration_number'=>addslashes($_POST['staff_registration_number'])])
                ->fetch();
        if($test) $msg = 'Staff information exists, try to edit existing one if necessary';
        else {
            $user_id = $db->insert('user',$user);
            if(intval($user_id)){
                $staff = [
                    'staff_registration_number'=>addslashes($_POST['staff_registration_number']), 
                    'residence_address'=>addslashes($_POST['residence_address']), 
                    'designation'=>addslashes($_POST['designation']), 
                    'user_reference'=>$user_id, 
                    'staff_date_employed'=>helper::format_time($_POST['date_employed'], 'Y-m-d H:i:s'), 
                    'employment_length'=>2,
                    'employment_status'=>'active'
                ];
                $k = $db->insert('staff',$staff);
                if($db->error() or !$k) $db->delete('user')->where(['user_id',$user_id])->commit(); // revert changes, staff issues
                else {
                    $msg = 'Staff created'; 
                    $status = 'success';
                }
            }
            else $msg = 'Fatal error occured';
        }
    }
    else $msg = 'Not enough privilege, sorry';
}
            
if($helper->user_can('can_view_staff')){
    $designation = $db->select('designation','designation_id,designation_name')
        ->order_by('designation_name', 'asc')
        ->fetchAll();
    
    $roles = $db->select('role','role_id,role_name')
        ->order_by('role_name', 'asc')
        ->fetchAll();

    $staff = $db->select('staff')
        ->join('user','user.user_id=staff.user_reference')
        ->join('designation', 'designation_id=designation','LEFT')
        ->join('role', 'role.role_id=user.system_role','LEFT')
        ->order_by('user_id', 'desc')
        ->fetchAll();
    $data = [
        'designation'=>$designation,
        'roles'=>$roles, 
        'staff'=>$staff,
        'msg'=>$msg, 
        'status'=>$status,
        'request_uri'=>$request
    ];
    echo helper::find_template('Staff', $data);
}
else echo helper::find_template('permission_denied');





