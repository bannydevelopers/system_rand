<?php 

$db = db::get_connection(storage::init()->system_config->database);
$my = helper::init()->get_session_user();
$ok = false;
$me = $db->select('staff')->where(['user_reference'=>$my['user_id']])->fetch();
$msg = '';

$request = $_SERVER['REQUEST_URI'];
$storage = storage::init();

$file = __DIR__.'/module.json';
$json = file_get_contents($file);
$mod_conf = json_decode($json, false);

if(isset($_POST['project_manager_role'])){
    $mod_conf->pm_manager = intval($_POST['project_manager_role']);
    file_put_contents($file, json_encode($mod_conf, JSON_PRETTY_PRINT));
}
if(isset($_POST['project_name'])){
    $data = [
        'project_name'=>$_POST['project_name'], 
        'project_starting_date'=>$_POST['project_starting_date'],
        'project_ending_date'=>$_POST['project_ending_date'],
        'project_burget'=>$_POST['project_burget'],
        'project_description'=>$_POST['project_description'],
        'project_manager'=>$_POST['project_manager'],
        'project_client'=>intval($_POST['project_client']),
        'created_by'=>$my['user_id'],
        'created_time'=>date('Y-m-d H:i:s')
    ];
    
    $k = $db->insert('project', $data);
   
    if(!$db->error() && $k) {
        $msg = 'project added successful';
        $ok =true;
    }
    else $msg = 'Error adding project';
    //var_dump($db->error());
}

$users = $db->select('user',"user_id,concat(first_name,' ', last_name) as full_name")
            ->where("system_role IN ({$mod_conf->project_assignees})")
            ->fetchALL();

if(isset($storage->request[3]) && intval($storage->request[3])){
    $cols = "project.*,designation.designation_name,customer.*, concat(first_name,' ',last_name) as pm_name";
    
    if(isset($_POST['activity_name'])){
        $data = [ 
            'activity_name'=>$_POST['activity_name'], 
            'activity_duration'=>$_POST['activity_duration'], 
            'activity_description'=>$_POST['activity_description'], 
            'project_ref'=>$_POST['project_ref'], 
            'assignee_id'=>$_POST['project_assignee'], 
            'created_by'=>helper::init()->get_session_user('user_id'), 
            'activity_parent'=>$_POST['activity_parent'], 
        ];
        $k = $db->insert('activities', $data);
        if(intval($k)) $msg = 'Activity created successful';
        else $msg = 'Activity creation fail';
    }
    if(isset($_POST['activity_resource_type'])){
        //var_dump($_POST);
        $data = [
        'resource_type'=>addslashes($_POST['activity_resource_type']), 
        'resource_requester'=>helper::init()->get_session_user('user_id'), 
        'resource_activity'=>addslashes($_POST['activity_ref']), 
        'resource_status'=>'requested', 
        'resource_quantity'=>0,
        'request_date'=>date('Y-m-d H:i:s')
        ];
        if($_POST['activity_resource_type'] == 'deliverables' or $_POST['activity_resource_type'] == 'tools'){
            $data['resource_reference'] = implode(',',$_POST['resource_name']);
            $data['resource_quantity'] = implode(',',$_POST['resource_quantity']);
        }
        else{
            $data['resource_reference'] = implode(',',$_POST['resources']);
        }
        $k = $db->insert('project_resources', $data);
        //var_dump($db->error());
    }
    $project = $db->select('project', $cols)
                 ->join('user', 'project_manager=user_id')
                 ->join('staff','staff.user_reference=user.user_id')
                 ->join('customer','project.project_client=customer.customer_id')
                 ->join('designation', 'designation_id=staff.designation')
                 ->where(['project_id'=>$storage->request[3]])
                 ->order_by('project_id', 'desc')->fetch();

    $activities = $db->select('activities', "activities.*, concat(first_name,' ', last_name) as assignee")
                     ->join('user', 'assignee_id=user_id')
                     ->where(['project_ref'=>$storage->request[3]])
                     ->order_by('activity_id','ASC')
                     ->fetchAll();
                     
    $tools      = $db->select('tools','tool_id, tool_name')
                     ->where(['tool_status'=>'new'])
                     ->or(['tool_status'=>'active'])
                     ->fetchAll();

    $deliverables  = $db->select('product','product_id, product_name')
                        ->fetchAll();
    
    $whr = "resource_activity IN (SELECT activity_id FROM activities WHERE project_ref = {$storage->request[3]})";
    $resources = $db->select('project_resources')
                    ->where($whr)
                    ->fetchAll();
    
    $activities_tree = [];
    // Arrange in tree hierarchy
    foreach($activities as $child){
        if($child['activity_parent'] == 0) {
            $child['tasks'] = [];
            $activities_tree[$child['activity_id']] = $child;
        }
        else{
            $qty = [];
            foreach($resources as $tool){
                if(!isset($qty[$tool['resource_type']])) $qty[$tool['resource_type']] = 0;
                if(!isset($child[$tool['resource_type']])) $child['tools'] = [];
                if($tool['resource_type'] != 'people' && $tool['resource_activity'] == $child['activity_id']){
                    $qty[$tool['resource_type']] += array_sum(explode(',',$tool['resource_quantity'], true));
                    $child[$tool['resource_type']][] = $tool;
                }
                else{
                    $qty[$tool['resource_type']] += count(explode(',',$tool['resource_reference'], true)); // needs a fix
                    $child[$tool['resource_type']][] = $tool;
                }
            }
            $child['qty'] = $qty;
            $activities_tree[$child['activity_parent']]['tasks'][] = $child;
        }

    }
    //var_dump('<pre>', $activities_tree);echo '</pre>';
    rsort($activities_tree);
    
    $data = [
        'project'=>$project,
        'activity'=>$activities_tree,
        'users'=>$users,
        'tools'=>$tools,
        'deliverables'=>$deliverables,
        'currency'=>$storage->system_config->system_currency,
        'request_uri'=>$_SERVER['REQUEST_URI']
    ];
    if(isset($_POST['ajax_view_task'])){
        $activty_resources = $db->select('project_resources')
                                ->where(['resource_activity'=>intval($_POST['ajax_view_task'])])
                                ->fetchAll();
        
        $return = ['tools'=>[], 'people'=>[], 'deliverables'=>[], 'money'=>[]];
        
        foreach($activty_resources as $res_val){
            if($res_val['resource_type'] == 'people'){
                $return['people'][] = $db->select('user', "user_id, concat(first_name,' ', last_name) as full_name")
                                    ->where("user_id in ({$res_val['resource_reference']})")
                                    ->fetchAll();
            }
            if($res_val['resource_type'] == 'tools'){
                $return['tools'][] = $db->select('tools','tool_id, tool_name')
                                    ->where("tool_id IN ({$res_val['resource_reference']})")
                                    ->fetchAll();
            }
            if($res_val['resource_type'] == 'deliverables'){
                $return['deliverables'][] = $db->select('product','product_id, product_name')
                                        ->where("product_id IN ({$res_val['resource_reference']})")
                                        ->fetchAll();
                
            }
            if($res_val['resource_type'] == 'money'){
                
            }
        }
        die(json_encode($return, JSON_PRETTY_PRINT));
    }
    die(helper::find_template('project_details', $data));
}

$staff = $db->select('user',"user_id,concat(first_name,' ', last_name) as pm_name")
            ->where(['system_role'=>$mod_conf->pm_manager])
            ->fetchALL();

$clients = $db->select('customer','customer_id, customer_name, customer_email')->fetchAll();

$project = $db->select('project', "project.*, concat( first_name,' ',last_name) as pm_name")
              ->join('user', 'project_manager=user_id')
              ->join('staff', 'user_reference=user_id')
              ->order_by('project_id', 'desc')->fetchAll();
$roles = $db->select('role')->fetchAll();
$data = [
    'project'=>$project,
    'msg'=>$msg, 
    'status'=>$ok,
    'request_uri'=>$request,
    'user'=>$staff,
    'clients'=>$clients,
    'currency'=>$storage->system_config->system_currency,
    'roles'=>$roles,
    'request_uri'=>$_SERVER['REQUEST_URI']
];
echo helper::find_template('projects', $data);