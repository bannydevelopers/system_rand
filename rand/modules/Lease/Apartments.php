<?php 

$db = db::get_connection(storage::init()->system_config->database);
$ok = false;
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['apartment_category'])){
    $data = [
        'apartment_name'=>$_POST['apartment_name'],
        'apartment_block'=>$_POST['apartment_block'], 
        'apartment_floor'=>$_POST['apartment_floor'], 
        'apartment_category'=>$_POST['apartment_category']        
    ];
    $k = $db->insert('apartments', $data);
    //var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'apartment added successful';
        $ok =true;
    }
    else $msg = 'Error adding apartment';
    //var_dump($db->error());
}

if(isset($_POST['category_name'])){
    $data = [
        'category_name'=>$_POST['category_name'],
        'category_description'=>$_POST['category_description'], 
        'bedroom'=>$_POST['bedroom'], 
        'bathroom'=>$_POST['bathroom'],
        'kitchen'=>$_POST['kitchen'], 
        'livingroom'=>$_POST['livingroom'],
        'price'=>$_POST['price'], 
        'dinning_room'=>$_POST['dinning_room'],
        'asserts'=>json_encode($_POST['asserts'])
                
       ];
    $k = $db->insert('apartment_category', $data);
    //var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'category added successful';
        $ok =true;
    }
    else $msg = 'Error adding category';
    //var_dump($db->error());
}

$apartment_category = $db->select('apartment_category')
                         ->fetchALL();
$tree = [];
foreach($apartment_category as $cat){
    $cat['asserts'] = json_decode($cat['asserts'], true);
    if(!isset($tree[$cat['category_name']])) $tree[$cat['category_name']] = [];
    if(!isset($cat['children'])) $cat['children'] = [];
    $tree[$cat['category_name']] = $cat;
}

$apartment = $db->select('apartments','apartments.*,apartment_category.category_name')
                ->join('apartment_category','apartments.apartment_category=apartment_category.category_id')
                ->order_by('apartment_id', 'desc')->fetchAll();

$apart = $db->select('apartments', 'apartment_id, apartment_block, apartment_floor')
                ->order_by('apartment_block', 'asc')
                ->order_by('apartment_floor', 'asc')
                ->fetchAll();             

//var_dump($apartment);die;
foreach($apartment as $app){
    $tree[$app['category_name']]['children'][] = $app;
}
//var_dump('<pre>',$tree);die;
$data = ['apartment'=>$tree,'apart'=>$apart ,'msg'=>$msg, 'status'=>$ok,'request_uri'=>$request, 'conf'=>storage::init()->system_config];
echo helper::find_template('apartments', $data);
