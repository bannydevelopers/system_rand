<?php 

$db = db::get_connection(storage::init()->system_config->database);
$status = 'fail';
$msg = '';

$request = $_SERVER['REQUEST_URI'];
if(isset($_POST['add-apar'])){
    $data = [
        'apartment_name'=>$_POST['apartment_name'],
        'apartment_block'=>$_POST['apartment_block'], 
        'apartment_floor'=>$_POST['apartment_floor'], 
        'apartment_category'=>$_POST['apartment_category']        
    ];
    $k = $db->insert('apartments', $data);
    if(!$db->error() && $k) {
        $msg = 'Apartment added successful';
        $status = 'success';
    }
    else $msg = 'Error adding apartment';
}

if(isset($_POST['edit-apar'])){
    $data = [
        'apartment_name'=>$_POST['apartment_name'],
        'apartment_block'=>$_POST['apartment_block'], 
        'apartment_floor'=>$_POST['apartment_floor'], 
        'apartment_category'=>$_POST['apartment_category']        
    ];
    $k = $db->update('apartments', $data)
        ->where(['apartment_id'=>intval($_POST['apartment_id'])])
        ->commit();
    if(!$db->error() && $k) {
        $msg = 'Apartment edited successful';
        $status = 'success';
    }
    else $msg = 'Error editing apartment';
}

if(isset($_POST['add-ap-category'])){
    $data = [
        'category_name'=>$_POST['category_name'],
        'category_description'=>$_POST['category_description'], 
        'bedroom'=>$_POST['bedroom'], 
        'bathroom'=>$_POST['bathroom'],
        'kitchen'=>$_POST['kitchen'], 
        'livingroom'=>$_POST['livingroom'],
        'price_per_day'=>$_POST['price_per_day'], 
        'price'=>$_POST['price_per_month'], 
        'dinning_room'=>$_POST['dinning_room'],
        'assets'=>json_encode($_POST['assets'])    
       ];
    $k = $db->insert('apartment_category', $data);
    //var_dump($db->error());
   
    if(!$db->error() && $k) {
        $msg = 'category added successful';
        $status = 'success';
    }
    else $msg = 'Error adding category';
}

if(isset($_POST['edit-apar-category'])){
    $data = [
        'category_name'=>$_POST['category_name'],
        'category_description'=>$_POST['category_description'], 
        'bedroom'=>$_POST['bedroom'], 
        'bathroom'=>$_POST['bathroom'],
        'kitchen'=>$_POST['kitchen'], 
        'livingroom'=>$_POST['livingroom'],
        'price_per_day'=>$_POST['price_per_day'], 
        'price'=>$_POST['price_per_month'], 
        'dinning_room'=>$_POST['dinning_room'],
        'assets'=>json_encode($_POST['assets'])    
       ];
    $k = $db->update('apartment_category', $data)
        ->where(['category_id'=>intval($_POST['category_id'])])
        ->commit();
   
    if(!$db->error() && $k) {
        $msg = 'Category updated successful';
        $status = 'success';
    }
    else $msg = 'Error updating category';
    //var_dump($db->error());
}

$apartment_category = $db->select('apartment_category')
                         ->fetchALL();
$tree = [];
foreach($apartment_category as $cat){
    $cat['assets'] = json_decode($cat['assets'], true);
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
$data = ['apartment'=>$tree,'apart'=>$apart ,'msg'=>$msg, 'status'=>$status,'request_uri'=>$request, 'conf'=>storage::init()->system_config];
echo helper::find_template('apartments', $data);
