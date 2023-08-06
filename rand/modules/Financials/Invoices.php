<?php 
if($helper->user_can('can_view_invoice')){
    $db = db::get_connection(storage::init()->system_config->database);
    $status = 'fail';
    $msg = '';
    $my = helper::init()->get_session_user();

    $cps = realpath(__DIR__.'/../Settings');
    $settings = json_decode(file_get_contents("{$cps}/module.json"));
    $request = $_SERVER['REQUEST_URI'];

    if(isset($_POST['ajax_del_invoice'])){
        if($helper->user_can('can_delete_invoice')){
            $invoice_id = intval($_POST['ajax_del_invoice']);
            $invoice = $db->select('invoice', 'invoice_ref_number')
                        ->where(['invoice_id'=>$invoice_id])
                        ->fetch();
            if($invoice){
                $k = $db->delete('invoice')->where(['invoice_id'=>$invoice_id])->commit();
                if(!$db->error() && $k) {
                    $msg = "Deletion succesfully for invoice {$invoice['invoice_ref_number']}.";
                    $status = 'success';
                }
                else $msg = 'Deletion failed';
            }
            else $msg = 'invoice does not exist';
        }
        else $msg = 'Permission to delete invoice denied';
        die(json_encode(['status'=>$status,'msg'=>$msg]));
    }
    
    if(isset($_POST['add-invoice'])){
        if($helper->user_can('can_delete_invoice')){
            $check_scheduling = $db->select('check_scheduling', 'check_scheduling.payment_amount, apartments.apartment_category')
                            ->join('apartments', 'check_scheduling.apartment_reference = apartments.apartment_id')
                            ->where(['check_id' => $_POST['check_id']])->fetch();
            if($check_scheduling){
                $insInv = [
                    'invoice_ref_number' => 'INV141413', 
                    'issued_by' => $my['user_id'], 
                    'check_scheduling' => $_POST['check_id'],
                    'pay_status' => 'paid', 
                    'invoice_amount' => $check_scheduling['payment_amount'],
                    'apartment_cat_reference' => $check_scheduling['apartment_category']
                ];
                $invoice_id = $db->insert('invoice',$insInv);
                if(!$db->error() && $insInv){
                    $k = $db->update('invoice', ['invoice_ref_number' => 'INV00'.$invoice_id])
                            ->where(['invoice_id'=>intval($invoice_id)])
                            ->commit();
                    $msg = 'Invoice created';
                    $status = 'success';
                }
                else $msg = 'Something went wrong.';
            }
            else $msg = 'Something went wrong, try again later.';
        }
        else $msg = 'Permission to create invoice denied';
    }

    $invoice = $db->select('invoice','invoice.*, CONCAT(repr.first_name, " ", repr.last_name) as issued_by, check_in, check_out, CONCAT(cust.first_name, " ", cust.last_name) as customer, cust.email, cust.phone_number, residence_address, country, apartment_name')
                ->join('user repr','invoice.issued_by = repr.user_id', 'left')
                ->join('check_scheduling','invoice.check_scheduling = check_scheduling.check_id', 'left')
                ->join('apartments','check_scheduling.apartment_reference = apartments.apartment_id', 'left')
                ->join('user cust','check_scheduling.user_ref = cust.user_id', 'left')
                ->join('tenants','tenants.user_reference = cust.user_id', 'left')
                ->order_by('invoice_id', 'desc')->fetchAll(); 

    $xinvoice = $db->select('check_scheduling', 'check_id, check_in, payment_amount, apartment_name, CONCAT_WS(" ", first_name, last_name) as full_name')
                ->join('user', 'check_scheduling.user_ref = user.user_id')
                ->join('apartments', 'check_scheduling.apartment_reference = apartments.apartment_id', 'left')
                ->where('check_id NOT IN (SELECT check_scheduling FROM invoice WHERE invoice.check_scheduling = check_scheduling.check_id)')
                ->order_by('check_scheduling.check_id', 'desc')
                ->fetchAll();

    $data = [
        'invoice'=>$invoice,
        'xinvoice'=>$xinvoice,
        'company'=>$settings->config->company_profile,
        'msg'=>$msg, 
        'status'=>$status,
        'request_uri'=>$request
    ];
    echo helper::find_template('invoiceX', $data);
}
else echo helper::find_template('403');