<?php 
$db = db::get_connection(storage::init()->system_config->database);
$msg = '';
$status = 'fail';
$request = $_SERVER['REQUEST_URI'];

$role = $db->select('role','role_id')
               ->fetch();

$apartment = $db->select('apartments','apartment_id')
               ->fetch();

if(isset($_POST['tenants_id']) && intval($_POST['tenants_id'])){
        if($helper->user_can('can_edit_tenants')){
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
                'phone_number'=>helper::format_phone_number($_POST['phone_number']), //
                'email'=>helper::format_email($_POST['email']), //
                
            ];
            $whr ="(email='{$user['email']}' OR phone_number = '{$user['phone_number']}' OR tenants_registration_number = '{$reg_no}')";
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
                   
                ];

                $db->update('tenants', $tenants)->where(['tenants_id'=>intval($_POST['tenants_id'])])->commit();
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
                'first_name'=>$fn, 
                'middle_name'=>$mn, 
                'last_name'=>$ln, 
                'system_role'=>$role['role_id'], 
                'status'=>'active', 
                'phone_number'=> isset($_POST['phone_number'])
                ? helper::format_phone_number($_POST['phone_number']) : '',
                'email' => isset($_POST['email']) 
                ? helper::format_email($_POST['email']) : '',
                'password' => isset($_POST['password'])
                 ? helper::create_hash($_POST['password']) : '', 
                'activation_token'=>$token, 
            
            ];
            $test = $db->select('tenants')
                    ->join('user','user_reference=user_id')
                    ->where(['email'=>$user['email']])
                    ->or(['phone_number'=>$user['phone_number']])
                    ->fetch();
            if($test) $msg = 'tenants information exists, try to edit existing one if necessary';
            else {
                $user_id = $db->insert('user',$user);
                //var_dump('<pre>',$db->error());
                if(intval($user_id)){
                    $tenants = [
                        'passport_number'=>addslashes($_POST['passport_number']), 
                        
                    ];
                    $k = $db->insert('tenants',$tenants);
                    // var_dump('<pre>',$db->error());
                    if($db->error() or !$k) $db->delete('user')->where(['user_id',$user_id])->commit(); // revert changes, tenants issues
                    else {
                        $msg = 'tenants created'; 
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
            ->where("user.status != 'deleted'")
            ->order_by('user_id', 'desc')
            ->fetchAll();
            
if($helper->user_can('can_view_tenants')){
    $data = [
        'roles'=>$roles,
        'apartment'=>$apartment,
        'tenants'=>$tenants,
        'msg'=>$msg, 
        'status'=>$status,
        'request_uri'=>$request
    ];

    echo helper::find_template('tenants', $data);
}
else echo helper::find_template('permission_denied');