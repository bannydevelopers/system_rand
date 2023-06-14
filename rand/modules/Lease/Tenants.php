<?php 
$db = db::get_connection(storage::init()->system_config->database);
$msg = 'fail';
$status = '';
$request = $_SERVER['REQUEST_URI'];

$role = $db->select('role','role_id')
               ->fetch();

$apartment = $db->select('apartments','apartment_id')
               ->fetch();

if(isset($_POST['edit-tenant']) ){
    if($helper->user_can('can_edit_tenants')){
        $names = explode(' ', addslashes($_POST['full_name']));
        $fn = $names[0];
        $ln = end($names);
        array_shift($names);
        array_pop($names);
        $mn = implode(' ', $names);
        $user = [
            'first_name' => $fn,
            'middle_name' => $mn,
            'last_name' => $ln,
            'system_role' => $role['role_id'],
            'phone_number'=>helper::format_phone_number($_POST['phone_number']), 
            'email'=>helper::format_email($_POST['email']), 
        ];
        $whr ="(email='{$user['email']}' OR phone_number = '{$user['phone_number']}')";
        $test = $db->select('tenants')
                ->join('user','user_reference=user_id')
                ->where($whr)
                ->and("tenants_id != {$_POST['tenants_id']}")
                ->fetch();
        if(!$test){
            $whr = "user_id IN (SELECT user_reference FROM tenants WHERE tenants_id = '{$_POST['tenants_id']}')";
            $db->update('user', $user)->where($whr)->commit();

            $tenants = [
                'passport_number'=>addslashes($_POST['passport_number']), 
                'resident_adress'=>addcslashes($_POST['resident_adress'])
            ];

            $db->update('tenants', $tenants)->where(['tenants_id'=>intval($_POST['tenants_id'])])->commit();
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

if(isset($_POST['add-tenant'])){
    if($helper->user_can('can_add_tenants')){
        $token = helper::create_hash(time());
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';
        $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
        $names = explode(' ', addslashes($full_name));
        $fn = $names[0];
        $ln = end($names);
        array_shift($names);
        array_pop($names);
        $mn = implode(' ', $names);
        $user = [
            'first_name' => $fn, 
            'middle_name' => $mn, 
            'last_name' => $ln, 
            'phone_number'=> isset($_POST['phone_number'])
            ? helper::format_phone_number($_POST['phone_number']) : '',
            'email' => isset($_POST['tenants_email']) 
            ? helper::format_email($_POST['tenants_email']) : '',
            'password' => helper::create_hash('tenant123'), 
            'activation_token' => $token, 
            'system_role' => $_POST['role'], 
            'status'=>'active', 
        ];
        $test = $db->select('tenants', 'user_reference')
                ->join('user','user_reference=user_id')
                ->where(['email' => $user['email']])
                ->or(['phone_number' => $user['phone_number']])
                ->fetch();
        if($test) $msg = 'tenants information exists, try to edit existing one if necessary';
        else {
            $user_id = $db->insert('user',$user);
            // var_dump('<pre>', $tenantDetails);
            if(intval($user_id)){
                $tenantsD = [
                    'user_reference' => $user_id, 
                ];
                $k = $db->insert('tenants',$tenantsD);

                $tenantDetails1 = [
                    'check_in' => $_POST['date_in'], 
                    'check_out' => $_POST['date_out'], 
                    'adults' => $_POST['adults'], 
                    'children' => $_POST['children'], 
                    'apartment_reference' => $_POST['occupied_apartment'], 
                    'check_status' => 'pending'
                ];
                $l = $db->insert('check_scheduling',$tenantDetails1);
                //var_dump($db->error());
                if($db->error() or !$k or !$l) $db->delete('user')->where(['user_id',$user_id])->commit(); // revert changes, tenants issues
                else {
                    $msg = 'tenants created'; 
                    $status = 'success';
                }
            }
            else $msg = 'Fatal error occured';
        }
    }
    else $msg = 'Not enough privilege, sorry';
}


$roles = $db->select('role','role_id,role_name')
            ->order_by('role_name', 'asc')
            ->fetchAll();
                  
$apartment = $db->select('apartments','apartment_id,apartment_name')
            ->order_by('apartment_name', 'asc')
            ->fetchAll();


$tenants = $db->select('tenants')
            ->join('user','user.user_id=tenants.user_reference')
            // ->where("user.status != 'deleted'")
            ->order_by('user_id', 'desc')
            ->fetchAll();
            
if($helper->user_can('can_view_tenants')){
    $data = [
        'roles' => $roles,
        'apartment' => $apartment,
        'tenants' => $tenants,
        'msg' => $msg, 
        'status' => $status,
        'request_uri' => $request,
        // 'user' => $user
    ];

    echo helper::find_template('tenants', $data);
}
else echo helper::find_template('permission_denied');