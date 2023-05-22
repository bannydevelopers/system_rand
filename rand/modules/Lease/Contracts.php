<?php 

$cdir = __DIR__.'/contracts';
$myhome = "$home/".basename(__DIR__).'/Contracts';
$msg = '';
$act = isset($registry->request[4]) ? $registry->request[3]:(isset($registry->request[3])?$registry->request[3]:'');
if(isset($_POST['contract_name'])){
    $contents = htmlspecialchars_decode($_POST['content']);
    $fn = str_replace(' ', '_', $_POST['contract_name']);
    if($_POST['contract_name'] != $_POST['old_name']  && is_readable("{$cdir}/{$fn}.html")){
        $msg = 'Contract exists';
    }
    else 
    {
        file_put_contents("{$cdir}/{$fn}.html", $contents);
        if($_POST['old_name'] && $_POST['contract_name'] != $_POST['old_name']){
            $oldname = str_replace(' ', '_', $_POST['old_name']);
            $old = "{$cdir}/{$oldname}.html";
            unlink($old);
        }
        header("Location: {$myhome}/Edit/{$fn}");
    }
}
if($act == 'Delete' && $registry->request[4]){
    $fn = str_replace(' ', '_', "{$cdir}/{$registry->request[4]}.html");
    @unlink($fn); // May not be there, should we care?
    header("Location: {$myhome}");
}
$dirs = scandir($cdir);
unset($dirs[0]);
unset($dirs[1]);
$data = [
            'msg'=>$msg,
            'dirs'=>$dirs,
            'myhome'=>$myhome,
            'action'=>$act,
            'cdir'=>$cdir,
            'content'=>'',
            'name'=>''
        ];
if($act == 'Edit'){
    $data['content'] = file_get_contents("{$cdir}/{$registry->request[4]}.html");
    $data['name'] = str_replace('_',' ',$registry->request[4]);
}
echo helper::find_template('Contracts', $data);