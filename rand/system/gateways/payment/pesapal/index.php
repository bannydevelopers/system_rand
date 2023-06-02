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
    $request->billing_state = 'TZ';//$post_data['country'];
    $request->billing_postcode = '';//$post_data['postcode'];
    $request->callback_url = "{$callback}?pay_order=";
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
?>
<!--form method="post">
    <div class="container">
        <div class="brand-logo"></div>
        <div class="brand-title">TWITTER</div>
        <div class="inputs">
            <label>FULL NAME</label>
            <input type="text" name="fullname" placeholder="At least two names" />
            <label>PASSWORD</label>
            <input type="text" name="billing_address[first_name]" placeholder="...." />
            <button type="submit">LOGIN</button>
        </div>
    </div>
</form>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;900&display=swap");

  input {
    caret-color: red;
  }

  body {
    margin: 0;
    width: 100vw;
    height: 100vh;
    background: #ecf0f3;
    display: flex;
    align-items: center;
    text-align: center;
    justify-content: center;
    place-items: center;
    overflow: hidden;
    font-family: poppins;
  }

  .container {
    position: relative;
    width: 350px;
    height: 500px;
    border-radius: 20px;
    padding: 40px;
    box-sizing: border-box;
    background: #ecf0f3;
    box-shadow: 14px 14px 20px #cbced1, -14px -14px 20px white;
  }

  .brand-logo {
    height: 100px;
    width: 100px;
    background: url("https://img.icons8.com/color/100/000000/twitter--v2.png");
    margin: auto;
    border-radius: 50%;
    box-sizing: border-box;
    box-shadow: 7px 7px 10px #cbced1, -7px -7px 10px white;
  }

  .brand-title {
    margin-top: 10px;
    font-weight: 900;
    font-size: 1.8rem;
    color: #1da1f2;
    letter-spacing: 1px;
  }

  .inputs {
    text-align: left;
    margin-top: 30px;
  }

  label,
  input,
  button {
    display: block;
    width: 100%;
    padding: 0;
    border: none;
    outline: none;
    box-sizing: border-box;
  }

  label {
    margin-bottom: 4px;
  }

  label:nth-of-type(2) {
    margin-top: 12px;
  }

  input::placeholder {
    color: gray;
  }

  input {
    background: #ecf0f3;
    padding: 10px;
    padding-left: 20px;
    height: 50px;
    font-size: 14px;
    border-radius: 50px;
    box-shadow: inset 6px 6px 6px #cbced1, inset -6px -6px 6px white;
  }

  button {
    color: white;
    margin-top: 20px;
    background: #1da1f2;
    height: 40px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 900;
    box-shadow: 6px 6px 6px #cbced1, -6px -6px 6px white;
    transition: 0.5s;
  }

  button:hover {
    box-shadow: none;
  }

  a {
    position: absolute;
    font-size: 8px;
    bottom: 4px;
    right: 4px;
    text-decoration: none;
    color: black;
    background: yellow;
    border-radius: 10px;
    padding: 2px;
  }

  h1 {
    position: absolute;
    top: 0;
    left: 0;
  }

</style-->