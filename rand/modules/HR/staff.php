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
                $msg = 'Deletion succesfully';
                $status = 'success';
            }
            else {
                $msg = 'Deletion failed';
                $status = 'fail';
            }
        }
        else {
            $msg = 'Staff does not exist';
            $status = 'not-exist';
        }
    }
    else {
        $msg = 'Permission denied';
        $status = 'denied';
    }
    die(json_encode(['status'=>$status,'msg'=>$msg,'staff'=>$staff]));
}
if(isset($_POST['ajax_activate_user'])){
        $status = 'fail';
    if($helper->user_can('can_edit_user')){
        $msg = 'Status update failed';
        $k = $db->update('user', ['status'=>$_POST['status']])
                ->where(['user_id'=>intval($_POST['ajax_activate_user'])])
                ->commit();
        if(!$db->error() && $k){
            $msg = 'Status updated';
            $status = 'success';
        }
    }
    else $msg = 'Permission denied';
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}
if(isset($_POST['designation_name']) && isset($_POST['designation_details'])){
    if($helper->user_can('can_add_designation')){
        $data = [
            'designation_name'=>addslashes($_POST['designation_name']),
            'designation_detail'=>addslashes($_POST['designation_details'])
        ];
        $k = $db->insert('designation', $data);
        $db_error = $db->error();
        if(intval($k)) $msg = 'Designation added successful';
        else  $msg = 'Error adding designation. Possibly duplicate entry encounted';
        //$k = $db->insert('role', ['name'=>str_replace(' ', '_', $data['designation_name'])]);
    }
    else $msg = 'You do not have permission for the action';
}
if(isset($_POST['role_name'])){
    if($helper->user_can('can_add_role')){
        $role_id = $db->insert('role',['role_name'=>$_POST['role_name']]);
        if(intval($role_id)) $msg = 'Role added successful';
        else $msg = 'Role adding failed, possibly a duplicate already exists';
    }
    else $msg = 'Permission denied';
}
if(isset($_POST['department_name'])){
    if($helper->user_can('can_add_department')){
        $dept_id = $db->insert('department',['department_name'=>$_POST['department_name']]);
        if(intval($dept_id)) $msg = 'Department added successful';
        else $msg = 'Department adding failed, possibly a duplicate already exists';
    }
    else $msg = 'Permission denied';
}
if(isset($_POST['branch_name'])){
    if($helper->user_can('can_add_branch')){
        $data = [
            'branch_name'=>addslashes($_POST['branch_name']),
            'branch_location'=>addslashes($_POST['branch_location']),
            'branch_address'=>addslashes($_POST['branch_address'])
        ];
        $branch_id = $db->insert('branches', $data);
        if(intval($branch_id)) $msg = 'Branch added successful';
        else $msg = 'Branch adding failed, possibly a duplicate already exists';
    }
    else $msg = 'Permission denied';
}
if(isset($_POST['full_name'])){
    
    $role = $db->select('designation_role','role_id')
               ->where(['designation_id'=>intval($_POST['designation'])])
               ->fetch();

    if(isset($_POST['staff_id']) && intval($_POST['staff_id'])){
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
                'system_role'=>$role['role_id'],
                //'status'=>'active', 
                'phone_number'=>helper::format_phone_number($_POST['phone_number']), //
                'email'=>helper::format_email($_POST['email']), //
                //'password'=>md5($token), 
                //'activation_token'=>$token, 
                //'created_by'=>helper::init()->get_session_user('user_id'), 
                //'created_time'=>date('Y-m-d H:i:s')
            ];
            $reg_no = addslashes($_POST['registration_number']);
            $whr ="(email='{$user['email']}' OR phone_number = '{$user['phone_number']}' OR staff_registration_number = '{$reg_no}')";
            $test = $db->select('staff')
                    ->join('user','user_reference=user_id')
                    ->where($whr)
                    ->and("staff_id != {$_POST['staff_id']}")
                    ->fetch();
            if(!$test){
                $whr = "user_id IN (SELECT user_reference FROM staff WHERE staff_id = '{$_POST['staff_id']}')";
                $db->update('user', $user)->where($whr)->commit();

                $staff = [
                    'staff_registration_number'=>addslashes($_POST['registration_number']), //
                    'staff_residence_address'=>addslashes($_POST['residence_address']), //
                    //'work_location'=>addslashes($_POST['work_location']), 
                    'designation'=>addslashes($_POST['designation']), 
                    //'user_reference'=>$user_id, 
                    'staff_date_employed'=>helper::format_time($_POST['date_employed'], 'Y-m-d H:i:s'), //
                    //'employment_length'=>2,//addslashes($_POST['employment_length']), 
                    //'employment_status'=>'active'
                ];

                $db->update('staff', $staff)->where(['staff_id'=>intval($_POST['staff_id'])])->commit();
                if(!$db->error()) {
                    $msg = 'Updated successful!';
                }
                else $msg = 'Something went wrong!';
            }
            else $msg = 'Some unique information already used by other employee';
        }
        else $msg = 'Not enough privilege, sorry';
    }
    else{
        if($helper->user_can('can_add_staff')){
            $token = helper::create_hash(time());
            $role = $db->select('designation_role','role_id')
                    ->where(['designation_id'=>intval($_POST['designation'])])
                    ->fetch();
            
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
                'system_role'=>$role['role_id'], 
                'status'=>'active', 
                'phone_number'=>helper::format_phone_number($_POST['phone_number']), 
                'email'=>helper::format_email($_POST['email']), 
                'password'=>md5($token), 
                'activation_token'=>$token, 
                'created_by'=>helper::init()->get_session_user('user_id'), 
                'created_time'=>date('Y-m-d H:i:s')
            ];
            $test = $db->select('staff')
                    ->join('user','user_reference=user_id')
                    ->where(['email'=>$user['email']])
                    ->or(['phone_number'=>$user['phone_number']])
                    ->or(['staff_registration_number'=>addslashes($_POST['registration_number'])])
                    ->fetch();
            if($test) $msg = 'Staff information exists, try to edit existing one if necessary';
            else {
                $user_id = $db->insert('user',$user);
                //var_dump('<pre>',$db->error());
                if(intval($user_id)){
                    $staff = [
                        'staff_registration_number'=>addslashes($_POST['registration_number']), 
                        'staff_residence_address'=>addslashes($_POST['residence_address']), 
                        'work_location'=>addslashes($_POST['work_location']), 
                        'designation'=>addslashes($_POST['designation']), 
                        'user_reference'=>$user_id, 
                        'staff_date_employed'=>helper::format_time($_POST['date_employed'], 'Y-m-d H:i:s'), 
                        'employment_length'=>2,//addslashes($_POST['employment_length']), 
                        'employment_status'=>'active'
                    ];
                    $k = $db->insert('staff',$staff);
                    // var_dump('<pre>',$db->error());
                    if($db->error() or !$k) $db->delete('user')->where(['user_id',$user_id])->commit(); // revert changes, staff issues
                    else {
                        $msg = 'Staff created'; 
                    }
                }
                else $msg = 'Fatal error occured';
            }
        }
        else $msg = 'Not enough privilege, sorry';
    }
}
$designation = $db->select('designation','designation_id,designation_name')
                  ->order_by('designation_name', 'asc')
                  ->fetchAll();
                  
$roles = $db->select('role','role_id,role_name')
                  ->order_by('role_name', 'asc')
                  ->fetchAll();
                  
$depts = $db->select('department','department_id,department_name')
                  ->order_by('department_name', 'asc')
                  ->fetchAll();
                  
$branches = $db->select('branches','branch_id,branch_name')
                  ->order_by('branch_name', 'asc')
                  ->fetchAll();
                  
$banks = $db->select('bank','bank_id,bank_name')
                  ->order_by('bank_name', 'asc')
                  ->fetchALL();

$staff = $db->select('staff')
            ->join('user','user.user_id=staff.user_reference')
            //->join('user as creator', 'creator.user_id=user.created_by')
            ->join('designation', 'designation_id=designation','LEFT')
            ->join('bank', 'bank.bank_id=staff.bank_id','LEFT')
            ->where("user.status != 'deleted'")
            ->order_by('user_id', 'desc')
            ->fetchAll();
            
if($helper->user_can('can_view_staff')){
    $data = [
        'designation'=>$designation,
        'roles'=>$roles, 
        'branches'=>$branches, 
        'departments'=>$depts, 
        'banks'=>$banks, 
        'staff'=>$staff,
        'msg'=>$msg, 
        'status'=>$status,
        'request_uri'=>$request
    ];

    echo helper::find_template('Staff', $data);
}
else echo helper::find_template('permission_denied');