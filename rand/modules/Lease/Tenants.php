<?php 
$db = db::get_connection(storage::init()->system_config->database);
$msg = '';
$status = 'fail';
$request = $_SERVER['REQUEST_URI'];

$role = $db->select('role','role_id')
               ->fetch();

$apartment = $db->select('apartments','apartment_id')
               ->fetch();

if(isset($_POST['userId'])){
    if($helper->user_can('can_delete_tenants')){
        $user_id = intval($_POST['userId']);
        $check_id = intval($_POST['check_id']);

        $user = $db->select('user', 'user_id, check_id')
        ->join('tenants', 'user.user_id = tenants.user_reference')
        ->join('check_scheduling', 'user.user_id = check_scheduling.user_ref', 'left')
        ->where(['user_id'=>$user_id])
        ->fetchAll();
        $msg = 'Tenant details ' . implode(" & ",array_map(function($a) {return implode(" ~ ",$a);},$user));
        if(count($user) == 1) {
            $k = $db->delete('user')->where(['user_id'=>$user_id])->commit();
            if(!$db->error() && $k) {
                $msg = 'Tenant deletion succesfully';
                $status = 'success';
            }
            else {
                $msg = 'Tenant deletion failed';
            }
        }
        else{
            $k = $db->delete('check_scheduling')->where(['check_id'=>$check_id])->commit();
            if(!$db->error() && $k) {
                $msg = 'Deletion succesfully';
                $status = 'success';
            }
            else {
                $msg = 'Tenant deletion failed';
            }
        }
    }
    else {
        $msg = 'Permission denied to delete Tenant';
    }
    die(json_encode(['status'=>$status,'msg'=>$msg]));
}

if(isset($_POST['edit-tenant'])){
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
            'phone_number'=>helper::format_phone_number($_POST['phone_number']), 
            'email'=>helper::format_email($_POST['tenants_email']), 
        ];
        $db->update('user', $user)->where(['user_id'=>intval($_POST['user_id'])])->commit();
        $tenants = [
            'passport_number'=>$_POST['passport_number'], 
            'residence_address'=>$_POST['residence_address'], 
            'country'=>$_POST['country']
        ];
        $db->update('tenants', $tenants)->where(['tenants_id'=>intval($_POST['tenants_id'])])->commit();
        $tenantDetails = [
            'check_in' => $_POST['date_in'],
            'check_out' => $_POST['date_out'],
            'adults' => $_POST['adults'],
            'children' =>  $_POST['children']?$_POST['children']:0,
            'apartment_reference' => $_POST['apartment_reference'],
            'payment_amount' => $_POST['order_amount'],
        ];
        $db->update('check_scheduling', $tenantDetails)->where(['check_id'=>intval($_POST['check_id'])])->commit();
        if(!$db->error()) {
            $msg = "Tenant {$user['first_name']} updated successful!";
            $status = 'success';
        }
        else $msg = 'Something went wrong!';
    }
    else $msg = 'Not enough privilege, sorry';
}

if(isset($_POST['add-tenant'])){
    if($helper->user_can('can_add_tenants')){
        if(!isset($_POST['user_id'])) {
            $token = helper::create_hash(time());
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
                'phone_number'=> helper::format_phone_number($_POST['phone_number']),
                'email' => helper::format_email($_POST['tenants_email']),
                'password' => helper::create_hash('tenant123'), 
                'activation_token' => $token, 
                'system_role' => 10, 
                'status'=>'active', 
            ];
            $test = $db->select('user','user_id')
                    ->where(['email' => $user['email']])
                    ->or(['phone_number' => $user['phone_number']])
                    ->fetch();
            if($test) $msg = 'Tenant with same information (Email/Phone number) exists';
            else {
                $user_id = $db->insert('user',$user);
                if(intval($user_id)){
                    $tenantsD = [
                        'user_reference' => $user_id, 
                        'passport_number'=>$_POST['passport_number'], 
                        'residence_address'=>$_POST['residence_address'], 
                        'country'=>$_POST['country']
                    ];
                    $k = $db->insert('tenants',$tenantsD);
                    $tenantDetails = [
                        'apartment_reference' => $_POST['apartment_reference'],
                        'check_in' => $_POST['date_in'],
                        'check_out' => $_POST['date_out'],
                        'adults' => $_POST['adults'],
                        'children' => $_POST['children'] ? $_POST['children'] : 0,
                        'payment_amount' => $_POST['order_amount'],
                        'user_ref' => $user_id
                    ];
                    $l = $db->insert('check_scheduling', $tenantDetails);

                    if (!$db->error() && $l) {
                        $msg = 'Tenant created';
                        $status = 'success';
                    } else {
                        $db->delete('user')->where(['user_id', $user_id])->commit();
                        $msg = 'Something went wrong';
                    }
                }
            }
        }
        else{
            $tenantDetails = [
                'apartment_reference' => $_POST['apartment_reference'],
                'check_in' => $_POST['date_in'],
                'check_out' => $_POST['date_out'],
                'adults' => $_POST['adults'],
                'children' => $_POST['children'] ? $_POST['children'] : 0,
                'payment_amount' => $_POST['order_amount'],
                'user_ref' => $_POST['user_id']
            ];
            $l = $db->insert('check_scheduling', $tenantDetails);

            if (!$db->error() && $l) {
                $msg = 'Appartment booked';
                $status = 'success';
            } else {
                $db->delete('user')->where(['user_id', $user_id])->commit();
                $msg = 'Something went wrong';
            }
        }
    }
}
            
if($helper->user_can('can_view_tenants')){
    $apartment = $db->select('apartments','apartment_id, apartment_name, apartment_floor, category_name, price_per_day, price')
                ->join('apartment_category', 'apartment_category.category_id = apartments.apartment_category', 'right')
                ->where('apartment_id NOT IN (SELECT DISTINCT apartment_reference FROM check_scheduling)')
                ->order_by('category_name', 'asc')
                ->fetchAll();

    $tenants = $db->select('user')
                ->join('tenants', 'user.user_id = tenants.user_reference')
                ->join('check_scheduling', 'user.user_id = check_scheduling.user_ref', 'left')
                ->join('apartments', 'check_scheduling.apartment_reference = apartments.apartment_id', 'left')
                ->order_by('user.user_id', 'desc')
                ->fetchAll();

    $tenants = $db->select('user')
                ->join('tenants', 'user.user_id = tenants.user_reference')
                ->join('check_scheduling', 'user.user_id = check_scheduling.user_ref', 'left')
                ->join('apartments', 'check_scheduling.apartment_reference = apartments.apartment_id', 'left')
                ->order_by('user.user_id', 'desc')
                ->fetchAll();
    $data = [
        'apartment' => $apartment,
        'tenants' => $tenants,
        'msg' => $msg, 
        'status' => $status,
        'request_uri' => $request,
        'currency'=>$storage->system_config->system_currency,
    ];
    echo helper::find_template('tenants', $data);
}
else echo helper::find_template('permission_denied');
            