<?php 
$db = db::get_connection(storage::init()->system_config->database);
$status = false;
$msg = '';
if(isset($_POST['role_name'])){
    $role_id = $db->insert('role',['role_name'=>$_POST['role_name']]);
    if(intval($role_id)) $msg = 'Role added successful';
    else $msg = 'Role adding failed';
}
if(isset($_POST['delete_role'])){
    $role = $db->select('role')->where(['role_name'=>addslashes($_POST['role'])])->fetch();
    if(!$db->error() && $role){
        $db->delete('designation_role')->where(['role_id'=>$role['role_id']])->commit();
        $db->delete('role_permission_list')->where(['role_id'=>$role['role_id']])->commit();
        $db->delete('role')->where(['role_id'=>$role['role_id']])->commit();
        if(!$db->error()){
            $msg = "Role named '{$_POST['role']}' deleted successfully";
            $status = 'success';
        }
        else{
            $msg = "Sorry! Role named '{$_POST['role']}' delete had issues";
            $status = 'fail';
        }
    }
    else{
        $msg = "Sorry! Role named '{$_POST['role']}' does not exist";
        $status = 'fail';
    }
    if($_POST['delete_role'] == 'ajax'){
        die(json_encode(['status'=>$status, 'msg'=>$msg]));
    }
}
if(isset($_POST['perms'])){
    $role = array_key_first($_POST['perms']);
    $role_key = $db->select('role','role_id')->where(['role_name'=>$role])->fetch();

    $data = [];
    $plist = [];
    foreach($_POST['perms'] as $role) 
    {
        foreach($role as $v) {
            $data[] = "({$role_key['role_id']}, {$v})";
            $plist[] = $v;
        }
    }
    $plist = implode(',', $plist);
    $data = implode(',', $data);
    $db->delete('role_permission_list')->where(['role_id' =>$role_key['role_id']])->commit();
    $db->query("INSERT INTO role_permission_list(role_id, permission_id) VALUES{$data}");
}
$roles = $db->select('role_permission_list')
            ->join('permission','permission.permission_id=role_permission_list.permission_id')
            ->join('role','role.role_id=role_permission_list.role_id')
            ->order_by('legend')->fetchAll();
// Roles without permission hides from list, fixing it...
$new_roles = $db->select('role','role_name,role_id')->fetchAll();
$legends = $db->select('permission', 'DISTINCT legend')->fetchAll();

$role_tree = [];
foreach ((array) $new_roles as $nr) {
    $role_tree[$nr['role_name']] = [];
    foreach($legends as $leg) $role_tree[$nr['role_name']][$leg['legend']] = [];
}
foreach ((array) $roles as $role) {
    /*if(!isset($role_tree[$role['role_name']])) {
        $role_tree[$role['role_name']] = [];
    }
    if(!isset($role_tree[$role['role_name']][$role['legend']])) {
        $role_tree[$role['role_name']][$role['legend']] = [];
    }*/
    $role_tree[$role['role_name']][$role['legend']][] = [
        //'role_id'=>$role['role_id'],
        'permission_id'=>$role['permission_id'],
        'permission_name'=>$role['permission_name']
    ];
}
//var_dump('<pre>',$role_tree);
$designations = $db->select('designation','designation_id,designation_name')->fetchAll();
//if(isset($_POST['get_permission'])){
    $permission = $db->select('permission')
                    ->fetchAll();
    $perm_tree = [];

    foreach ((array) $permission as $perm) {
   
        if(!isset($perm_tree[$perm['legend']])) {
            $perm_tree[$perm['legend']] = [];
        }
        /*$checked = false;
        if(isset($role_tree[$_POST['get_permission']])){
            if(isset($role_tree[$_POST['get_permission']][$perm['legend']])){
                foreach($role_tree[$_POST['get_permission']][$perm['legend']] as $p){
                    if($p['permission_id'] == $perm['permission_id']){
                        $checked = true;
                        continue;
                    }
                }
            }
        }*/
        $perm_tree[$perm['legend']][] = [
            'permission_id'=>$perm['permission_id'],
            'permission_name'=>$perm['permission_name'],
            //'checked'=>$checked
        ];
    }
    //die(helper::get_sub_template('user_permission_edit', ['permission'=>$perm_tree]));
//}
//var_dump('<pre>',$perm_tree);
echo helper::find_template(
    'User_permission', 
    [
        'roles' => $role_tree,
        'designations'=>$designations,
        'permissions'=>$perm_tree,
        'msg' => $msg
    ]
);