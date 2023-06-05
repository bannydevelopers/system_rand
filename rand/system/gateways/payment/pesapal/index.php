<?php
  function send_request($post_data, $conf){
    //var_dump($post_data);die;
    include __DIR__.'/lib/pesapalV30Helper.php';
    $consumer_key = $conf->pesapal->consumer_key;
    $consumer_secret = $conf->pesapal->consumer_secret;

    $gateway = new pesapalV30Helper();

    $token = $gateway->getAccessToken($consumer_key, $consumer_secret);
   
    $access_token = $token->token;

    //$token_ok = $gateway->validateKeys($access_token, $consumer_key, $consumer_secret);
    $callback = "https://{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
    $ipn_id = $gateway->generateNotificationId("{$callback}/?ipn=1", $access_token);
    //print_r($next);
    //$ipn = $gateway->getRegisteredIpn($access_token);
    //print_r($ipn);
    $request = new stdClass();

    $request->currency = $conf->system_currency;
    $request->amount = $post_data['order_amount'];
    $request->pesapalMerchantReference = $post_data['order_reference'];
    $request->pesapalDescription = $post_data['order_description'];
    $request->account_number = '';
    $request->app_id = 1;
    $request->billing_phone = $post_data['phone'];
    $request->billing_email = $post_data['email'];
    $request->billing_country = $conf->system_currency;
    $request->billing_first_name = $post_data['first_name'];
    $request->billing_last_name = $post_data['last_name'];
    $request->billing_address_1 = '';//$post_data['address1'];
    $request->billing_address_2 = '';//$post_data['address2'];
    $request->billing_city = '';//$post_data['city'];
    $request->billing_state = $post_data['country'];
    $request->billing_postcode = '';//$post_data['postcode'];
    $request->callback_url = "{$callback}?pay_order=1";
    $request->notification_id = $ipn_id;
    $order = $gateway->getMerchertOrderURL($request, $access_token);
    if(isset($order->redirect_url)) {
      $src = $order->redirect_url;
      ob_start();
      include 'iframe.html';
      return ob_get_clean();
    }
    else return json_encode($order);
  }