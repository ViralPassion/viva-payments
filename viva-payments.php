<?php
/*
  Plugin Name: Viva payments Native
  Description: Viva gateway.
  Version: 3.0
  Author: Viral Passion
  Text Domain: viva
  Domain Path: /vp-ucf
*/
defined( 'ABSPATH' ) or die();

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'http://assets.vpsites.eu/my_plugins/viva-payments-versions/update.json',
    __FILE__
);

add_action('plugins_loaded', 'woocommerce_Viva_init', 0);
function woocommerce_Viva_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Viva extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'viva';
      //$this->order_button_text  = __( 'Pay with card', 'woocommerce' );
      $this -> medthod_title = 'Viva Payments';
      $this -> has_fields = $this -> is_native == 'yes' ? true : false;// This basically defines your settings which are then loaded with init_settings()

      $this->supports = array(
        //'products',
        'refunds'
      );

      $this -> init_form_fields();
      $this -> init_settings();

	// Turn these settings into variables we can use
	foreach ( $this->settings as $setting_key => $value ) {
		$this->$setting_key = $value;
	}

	  $this -> icon = $this -> settings['image'];
      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      //$this -> liveurl = 'http://demo.vivapayments.com/';

      $this -> msg['message'] = "";
      $this -> msg['class'] = "";

	  add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	  add_action( 'woocommerce_api_wc_viva', array( $this, 'check_ipn_response' ) );


   }
    function init_form_fields(){

       $this->form_fields = array(
	'enabled' => array(
		'title' => __( 'Enable/Disable', 'woocommerce' ),
		'type' => 'checkbox',
		'label' => 'Enable Viva Payments',
		'default' => 'yes'
	),
	'title' => array(
		'title' => __( 'Title', 'woocommerce' ),
		'type' => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default' => 'Card',
		'desc_tip'      => true,
	),
	'description' => array(
		'title' => __( 'Customer Message', 'woocommerce' ),
		'type' => 'textarea',
		'default' => ''
	),
	'image' => array(
		'title' => __( 'Logo Url', 'woocommerce' ),
		'type' => 'text',
		'default' => plugins_url('cards_logo.png', __FILE__),
		'description' => 'default logo is: '.plugins_url('cards_logo.png', __FILE__)
	),
	'demo_mode' => array(
		'title' => 'Demo mode',
		'type' => 'checkbox',
		'label' => 'Enable demo mode',
		'description' => 'Enable this if you use a demo viva payments account',
		'default' => 'no',
		'desc_tip' => true
	),
	'source_code' => array(
		'title' => 'Source Code',
		'description' => 'You can find it at vivapayments.com, (Sales > Payment Sources)',
		'type' => 'text',
		'desc_tip' => true
	),
	'Merchant_ID' => array(
		'title' => 'Merchant ID',
		'description' => 'You can find it at vivapayments.com, (Settings > API Access)',
		'type' => 'text',
		'desc_tip'      => true,
	),
	'API_Key' => array(
		'title' => 'API Key',
		'description' => 'You can find it at vivapayments.com, (Settings > API Access)',
		'type' => 'text',
		'desc_tip'      => true,
	),
	'is_native' => array(
		'title' => 'Use native checkout',
		'type' => 'checkbox',
		'label' => 'Enable native',
		'default' => 'no',
		'description' => 'Setup the source to accept native checkout. You also need to fill your public key',
		'desc_tip'      => true,
	),
	'Public_Key' => array(
		'title' => 'Public Key',
		'description' => 'You can find it at vivapayments.com, (Settings > API Access)',
		'type' => 'text',
		'desc_tip'      => true,
	),
	'show_name' => array(
		'title' => 'Ask for card holder\'s name',
		'type' => 'checkbox',
		'label' => 'Enable native',
		'default' => 'no',
		'description' => 'If you enable this option the user could enter manualy the name, if you keep it desabled billing name will be used',
		'desc_tip'      => true,
	)
);
    }

	function payment_fields($order_id){
      if($this -> is_native == "yes"){
		global $woocommerce;
        echo '<div id="viva_form">';
        echo '<input id="viva_card_no" maxlength="16" class="numonly" type="text" name="card_no"  placeholder="Card number" />';
        echo '<input id="viva_card_exp"  maxlength="5" class="numonly nobksp" type="text" name="card_dt"  placeholder="MM/YY" />';
        echo '<input id="viva_card_ccv"  maxlength="4" class="numonly" type="text" name="card_ccv"  placeholder="ccv" />';
        echo '<input id="viva_card_name" class="" style="text-transform: uppercase;" type="'.($this->show_name ?'text':'hidden').'" name="card_nm" placeholder="Holder\'s name" />';
        echo '<span id="viva_msg">'.$this -> description .'</span>';
        //echo "<div class='onoffswitch'><input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' id='myonoffswitch' checked><label class='onoffswitch-label' for='myonoffswitch'></label></div>";
        echo '</div>';
        echo "<script> var viva_plugin_dir = '".plugin_dir_url(__FILE__)."'; </script>";
        echo "<script src='".plugin_dir_url(__FILE__) . 'assets/checkout.js'."'></script>";
        echo "<style>#viva_card_no{width:50%}#viva_card_exp{width:30%}#viva_card_ccv{width:20%}#viva_card_name{width:100%}</style>";
        echo "<style>@media(max-width:500px){#viva_card_no{width:100%}#viva_card_exp{width:60%}#viva_card_ccv{width:40%}#viva_card_name{width:100%}}</style>";
        //echo "<style>.onoffswitch{display:inline-block;position:relative;width:57px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none}.onoffswitch-checkbox{display:none}.onoffswitch-label{display:block;overflow:hidden;cursor:pointer;height:30px;padding:0;line-height:30px;border:0 solid #FFF;border-radius:25px;background-color:#9E9E9E}.onoffswitch-label:before{content:'';display:block;width:25px;margin:2.5px;background:#F7F7F7;position:absolute;top:0;bottom:0;right:23px;border-radius:25px;box-shadow:0 6px 12px 0 #757575}.onoffswitch-checkbox:checked+.onoffswitch-label{background-color:#7BE320}.onoffswitch-checkbox:checked+.onoffswitch-label,.onoffswitch-checkbox:checked+.onoffswitch-label:before{border-color:#7BE320}.onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-inner{margin-left:0}.onoffswitch-checkbox:checked+.onoffswitch-label:before{right:0;background-color:#FFF;box-shadow:3px 6px 18px 0 rgba(0,0,0,.2)}</style>";
      }
    }

    function check_ipn_response() {
				// die('die');
            	global $woocommerce;
            	//$order = new WC_Order( $order_id );
				// The POST URL and parameters

				$request =  'https://www.vivapayments.com/api/transactions/';	// production environment URL
				if($this -> demo_mode =='yes'){
					$request =  'http://demo.vivapayments.com/api/transactions/';	// demo environment URL
				}
				// Your merchant ID and API Key can be found in the 'Security' settings on your profile.
				$MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
				$APIKey = $this -> settings['API_Key'];//'weO.XU';


				//Set the ID of the Initial Transaction
				$request .= $_GET['t'];


				//$qry_str  = 'ordercode='.$_GET['s'];

				// Get the curl session object
				$curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => $request,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "GET",
                  CURLOPT_POSTFIELDS => "",
                  CURLOPT_HTTPHEADER => array(
                    "authorization: Basic ".base64_encode($MerchantId.':'.$APIKey),
                    "cache-control: no-cache",
                    "content-type: application/json"
                  ),
                ));
				$resultObj = json_decode( curl_exec($curl) );
                //die('a:'. $resultObj);exit;
                $err = curl_error($curl);

                curl_close($curl);
                if ($err) {
                  return false;
                }
				if ($resultObj->ErrorCode==0){
					// print JSON output
					//die ('ok: '.json_encode($resultObj));

					$order_id = $resultObj -> Transactions[0] -> Order -> Tags[0];
					$order_id = str_replace("0=", "",$order_id);
					$order = wc_get_order( $order_id );
					if ($resultObj -> Transactions[0] -> StatusId == 'F'){

						// Reduce stock levels
						$order->reduce_order_stock();
						$woocommerce->cart->empty_cart();

						$amound = $resultObj -> Transactions[0] -> Amount;
						$commission = $resultObj -> Transactions[0] -> Commission;
						$TransactionId = $resultObj -> Transactions[0] -> TransactionId;
						$OrderCode = $resultObj -> Transactions[0] -> Order -> OrderCode;

						$card_no = $resultObj -> Transactions[0] -> CreditCard -> Number;
						$card_country = $resultObj -> Transactions[0] -> CreditCard -> CountryCode;
						$card_bank = $resultObj -> Transactions[0] -> CreditCard -> IssuingBank;
						$card_holders_name = $resultObj -> Transactions[0] -> CreditCard -> CardHolderName;
						$card_type = $resultObj -> Transactions[0] -> CreditCard -> CardType -> Name;


						add_post_meta($order_id, 'Order_Code', $OrderCode);
						add_post_meta($order_id, 'Amount', $amound);
						add_post_meta($order_id, 'Commission', $commission);
						add_post_meta($order_id, 'Transaction_Id', $TransactionId);

						add_post_meta($order_id, 'Card_Number', $card_no);
						add_post_meta($order_id, 'Card_Holders_Name', $card_holders_name);
						add_post_meta($order_id, 'Card_Bank', $card_bank);
						add_post_meta($order_id, 'Card_Type', $card_type);
						add_post_meta($order_id, 'Card_Country', $card_country);


						if ((float)$amound == (float)$order->get_total()) {
							$order->update_status('processing','Is Payed via Viva Payments');
							$order->payment_complete();



						}else{
							$order->update_status('failed','The amount paid is different from order\'s total');

						}



						//die($amound.' '.$commission.' '.$TransactionId.' '.$OrderCode.' '.$card_no.' '.$card_country.' '.$card_bank.' '.$card_holders_name.' '.$card_type);


					}

					else if ($resultObj -> Transactions[0] -> StatusId == 'E'){
						$order->update_status('failed','The transaction was not completed because of an error');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'M'){
						$order->update_status('failed','The cardholder has disputed the transaction with the issuing Bank');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'MS'){
						$order->update_status('failed','Suspected Dispute');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'X'){
						$order->update_status('failed','The transaction was cancelled by the merchant');
					}
					else{
						$order->update_status('failed','Unknown error');
					}
					wp_redirect($this->get_return_url( $order ));

				}
				else{
					die (json_encode($resultObj));
					//die ($postargs.' '.$resultObj->ErrorText);
				}


					wp_redirect($this->get_return_url( $order ));

        }

        /**
     * Process the payment and return the result
     **/

    function get_viva_order_key($the_order){
			 // The POST URL and parameters
		$request =  'https://www.vivapayments.com/api/orders';	// production environment URL
		if($this -> demo_mode =='yes'){
			$request =  'http://demo.vivapayments.com/api/orders';	// demo environment URL
		}
		// Your merchant ID and API Key can be found in the 'Security' settings on your profile.
		$MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
		$APIKey = $this -> settings['API_Key'];//'weO.XU';

		//Set the Payment Amount
		$Amount = ((float) $the_order->get_total())*100;	// Amount in cents

		//Set some optional parameters (Full list available here: https://github.com/VivaPayments/API/wiki/Optional-Parameters)
		$AllowRecurring = 'false'; // This flag will prompt the customer to accept recurring payments in tbe future.
		$RequestLang = 'el-GR'; //This will display the payment page in English (default language is Greek)
		$Source = 'Default'; // This will assign the transaction to the Source with Code = "Default". If left empty, the default source will be used.
		$Email = $the_order -> billing_email;
		$Phone = $the_order -> billing_phone;
		$FullName = $the_order -> billing_first_name .' '.$the_order -> billing_last_name;
		$PaymentTimeOut = 86400;
		$MaxInstallments = 1;
		$IsPreAuth = 'true';
		$MerchantTrns = "Your reference";
		$CustomerTrns = 'Order Id: '.$the_order -> id;
		$DisableIVR = 'true';
		$DisableCash = 'true';
		$DisablePayAtHome = 'true';
		$SourceCode =  $this -> settings['source_code'];
		$Tags = http_build_query(array($the_order -> id));

		$postargs = 'Amount='.urlencode($Amount).'&AllowRecurring='.$AllowRecurring.'&RequestLang='.$RequestLang.'&Source='.$Source.'&Email='.$Email.'&Phone='.$Phone.'&FullName='.$FullName.'&MerchantTrns='.$MerchantTrns.'&CustomerTrns='.$CustomerTrns.'&DisableIVR='.$DisableIVR.'&DisableCash='.$DisableCash.'&DisablePayAtHome='.$DisablePayAtHome.'&SourceCode='.urlencode($SourceCode).'&Tags='.$Tags;


		// Get the curl session object
		$session = curl_init($request);


		// Set the POST options.
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
		curl_setopt($session, CURLOPT_HEADER, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_USERPWD, $MerchantId.':'.$APIKey);
		curl_setopt($session, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');

		// Do the POST and then close the session
		$response = curl_exec($session);

		// Separate Header from Body
		$header_len = curl_getinfo($session, CURLINFO_HEADER_SIZE);
		$resHeader = substr($response, 0, $header_len);
		$resBody =  substr($response, $header_len);

		curl_close($session);

		// Parse the JSON response
		try {
			if(is_object(json_decode($resBody))){
			  	$resultObj=json_decode($resBody);
			}else{
				preg_match('#^HTTP/1.(?:0|1) [\d]{3} (.*)$#m', $resHeader, $match);
						throw new Exception("API Call failed! The error was: ".trim($match[1]));
			}
		} catch( Exception $e ) {
			echo $e->getMessage();
		}

		if ($resultObj->ErrorCode==0){	//success when ErrorCode = 0
			$orderId = $resultObj->OrderCode;
			return   $orderId;
		}
		else{
			return 'error';// . $resultObj->ErrorText;
		}


 }

    function viva_tokenize_card($card){



		$MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
		$APIKey = $this -> settings['API_Key'];//'weO.XU';
      	$request = "https://www.vivapayments.com/api/cards?key=".$this -> settings['Public_Key'];
      	if($this -> demo_mode =='yes'){
			$request =  'http://demo.vivapayments.com/api/cards?key='.$this -> settings['Public_Key'];	// demo environment URL
		}

      // Get the curl session object

      	$curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $request,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{'Number': '".$card['number']."','CVC': ".$card['ccv'].",'ExpirationDate': '20".$card['year']."-".$card['month']."-15','CardHolderName': '".$card['name']."'}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Basic ".base64_encode($MerchantId.':'.$APIKey),
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));


        $response = json_decode( curl_exec($curl) );
        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
          return false;
        } else {
          if($response->Token){
              return $response->Token;
          }else{
              return false;
          }
        }
        return false;
    }

    function create_transaction($order_code, $card_token, $recuring){

	  $MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
	  $APIKey = $this -> settings['API_Key'];//'weO.XU';

      $url = 'https://www.vivapayments.com/api/Transactions';
      if($this -> demo_mode =='yes'){
			$url =  'http://demo.vivapayments.com/api/Transactions';	// demo environment URL
	  }

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{  \"OrderCode\" : $order_code,  \"SourceCode\": ".$this-> settings['source_code'].",  \"AllowsRecurring\": \"". ($recuring?'true':'false') ."\",  \"PreauthCapture\": \"true\",  \"CreditCard\" : {     \"Token\": \"$card_token\"  }  \n}",
        CURLOPT_HTTPHEADER => array(
          "authorization: Basic ".base64_encode($MerchantId.':'.$APIKey),
          "cache-control: no-cache",
          "content-type: application/json",
        ),
      ));

      $response = json_decode(curl_exec($curl));
      $err = curl_error($curl);
      curl_close($curl);
      if ($err) {
        return false;
      } else {

        if( ($response->ErrorCode == '0') && ($response->StatusId == 'F')){
            return $response->TransactionId;
        }else{
            return false;
        }
      }
      return false;
    }

    function update_order_with_payment($order_id,$transaction_id){

      			global $woocommerce;
            	//$order = new WC_Order( $order_id );
				// The POST URL and parameters

				$request =  'https://www.vivapayments.com/api/transactions/';	// production environment URL
				if($this -> demo_mode =='yes'){
					$request =  'http://demo.vivapayments.com/api/transactions/';	// demo environment URL
				}
				// Your merchant ID and API Key can be found in the 'Security' settings on your profile.
				$MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
				$APIKey = $this -> settings['API_Key'];//'weO.XU';


				//Set the ID of the Initial Transaction
				$request .= $transaction_id;


				//$qry_str  = 'ordercode='.$_GET['s'];

				// Get the curl session object
				$session = curl_init();


				// Set query data here with the URL

				//curl_setopt($ch, CURLOPT_TIMEOUT, '3');

				curl_setopt($session, CURLOPT_URL, $request . $qry_str);
				// Set query data here with the URL
				//curl_setopt($session, CURLOPT_POST, true);
				//curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
				//curl_setopt($session, CURLOPT_HEADER, false);
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($session, CURLOPT_USERPWD, $MerchantId.':'.$APIKey);
				//curl_setopt($session, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');

				$response = trim(curl_exec($session));
				curl_close($session);

				// Parse the JSON response
				try {
					$resultObj=json_decode($response);
				} catch( Exception $e ) {
					die ($e->getMessage());

				}

				if ($resultObj->ErrorCode==0){
					// print JSON output
					//die ('ok: '.json_encode($resultObj));
					if(!$order_id){
						$order_id = $resultObj -> Transactions[0] -> Order -> Tags[0];
						$order_id = str_replace("0=", "",$order_id);
                    }
					$order = wc_get_order( $order_id );
					if ($resultObj -> Transactions[0] -> StatusId == 'F'){

						// Reduce stock levels
						$order->reduce_order_stock();
						$woocommerce->cart->empty_cart();

						$amound = $resultObj -> Transactions[0] -> Amount;
						$commission = $resultObj -> Transactions[0] -> Commission;
						$TransactionId = $resultObj -> Transactions[0] -> TransactionId;
						$OrderCode = $resultObj -> Transactions[0] -> Order -> OrderCode;

						$card_no = $resultObj -> Transactions[0] -> CreditCard -> Number;
						$card_country = $resultObj -> Transactions[0] -> CreditCard -> CountryCode;
						$card_bank = $resultObj -> Transactions[0] -> CreditCard -> IssuingBank;
						$card_holders_name = $resultObj -> Transactions[0] -> CreditCard -> CardHolderName;
						$card_type = $resultObj -> Transactions[0] -> CreditCard -> CardType -> Name;


						add_post_meta($order_id, 'Order_Code', $OrderCode);
						add_post_meta($order_id, 'Amount', $amound);
						add_post_meta($order_id, 'Commission', $commission);
						add_post_meta($order_id, 'Transaction_Id', $TransactionId);
                      	add_post_meta($order_id, '_transaction_id', $TransactionId, true );

						add_post_meta($order_id, 'Card_Number', $card_no);
						add_post_meta($order_id, 'Card_Holders_Name', $card_holders_name);
						add_post_meta($order_id, 'Card_Bank', $card_bank);
						add_post_meta($order_id, 'Card_Type', $card_type);
						add_post_meta($order_id, 'Card_Country', $card_country);


						if ((float)$amound == (float)$order->get_total()) {
							$order->update_status('processing','Is Payed via Viva Payments');
							$order->payment_complete();



						}else{
							$order->update_status('failed','The amount paid is different from order\'s total');

						}


					}

					else if ($resultObj -> Transactions[0] -> StatusId == 'E'){
						$order->update_status('failed','The transaction was not completed because of an error');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'M'){
						$order->update_status('failed','The cardholder has disputed the transaction with the issuing Bank');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'MS'){
						$order->update_status('failed','Suspected Dispute');
					}
					else if ($resultObj -> Transactions[0] -> StatusId == 'X'){
						$order->update_status('failed','The transaction was cancelled by the merchant');
					}
					else{
						$order->update_status('failed','Unknown error');

					}
					return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );

				}
				else{
					die (json_encode($resultObj));
					//die ($postargs.' '.$resultObj->ErrorText);
				}
      				return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
					//wp_redirect($this->get_return_url( $order ));




    }

    function process_payment( $order_id ) {
	global $woocommerce;
	$order = new WC_Order( $order_id );

	// Mark as on-hold (we're awaiting the cheque)
	$order->update_status('pending','Pending Viva Payment');
	// Reduce stock levels
	//$order->reduce_order_stock();



      $viva_id = $this -> get_viva_order_key($order);


      if ($viva_id == 'error'){

      }
      else{

        if($this -> is_native == "yes"){
          	$date_arr =  explode("/",$_POST['card_dt']);
      		$viva_card_token = $this ->  viva_tokenize_card(array("number"=>$_POST['card_no'],"ccv"=>$_POST['card_ccv'],"month"=>$date_arr[0],"year"=>$date_arr[1],"name"=>$_POST['card_nm']));

          	if($viva_card_token){
          		$transaction_id = $this -> create_transaction($viva_id, $viva_card_token, false);

              	if($transaction_id){
                	return $this -> update_order_with_payment($order_id,$transaction_id);
                }
            }
      		return array(
                        'result' => 'failure',
                        'messages' => "We coulden't charge your card, please try again"
                    );
        }
        else{
          // Return thankyou redirect
          if($this -> settings['demo_mode']=='yes'){
              return array(
                  'result' => 'success',
                  'redirect' => 'https://demo.vivapayments.com/web/newtransaction.aspx?ref='.$viva_id
              );
          }else{
              return array(
                  'result' => 'success',
                  'redirect' => 'https://www.vivapayments.com/web/newtransaction.aspx?ref='.$viva_id
              );
          }
      }

    }
}

    public function process_refund( $order_id, $amount = null ) {
      // Do your refund here. Refund $amount for the order with ID $order_id
      $MerchantId = $this -> settings['Merchant_ID'];// '853c3324-25a1-4a7d-a466-d87eb25339ca';
	  $APIKey = $this -> settings['API_Key'];//'weO.XU';
      $transaction_id = get_post_meta( $order_id, 'Transaction_Id', true );
      $url =  "https://www.vivapayments.com/api/transactions/".$transaction_id."?amount=".($amount*100);
      if($this -> demo_mode =='yes'){
      	$url =  "http://demo.vivapayments.com/api/transactions/".$transaction_id."?amount=".($amount*100);
      }
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
          "authorization: Basic ".base64_encode($MerchantId.':'.$APIKey),
          "cache-control: no-cache",
          "content-type: application/json"
        ),
      ));

      $response = json_decode(curl_exec($curl));
      $err = curl_error($curl);
      curl_close($curl);
      if ($err) {
        return false;
      } else {

        if( ($response->ErrorCode == '0') && ($response->StatusId == 'F')){
            return true;
        }else{
            return false;
        }
      }

      return true;
    }





}
}



   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_Viva_gateway($methods) {
        $methods[] = 'WC_Viva';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_Viva_gateway' );

	function viva_enqueue_checkout_scripts() {
      if(is_checkout()){
        wp_enqueue_script('creditCardValidator', plugin_dir_url(__FILE__) . 'assets/jquery.creditCardValidator.js');
        //wp_enqueue_script('viva_checkout', plugin_dir_url(__FILE__) . 'assets/checkout.js');
      }
    }
	add_action('wp_enqueue_scripts', viva_enqueue_checkout_scripts);


/**

add metabox on admin

**/

//
//Adding Meta container admin shop_order pages
//
add_action( 'add_meta_boxes', 'viva_admin_meta_box' );
if ( ! function_exists( 'mv_add_meta_boxes' ) )
{
    function viva_admin_meta_box()
    {
        global $woocommerce, $order, $post;
		if(get_post_meta( $post->ID, 'Transaction_Id', true )){
        	add_meta_box( 'mv_other_fields', __('Viva Payment','woocommerce'), 'viva_admin_box_content', 'shop_order', 'side', 'core' );
        }
    }
}

//
//adding Meta field in the meta container admin shop_order pages
//
if ( ! function_exists( 'mv_save_wc_order_other_fields' ) )
{
    function viva_admin_box_content()
    {
        global $woocommerce, $order, $post;

        $amount = get_post_meta( $post->ID, 'Amount', true );
        $commission = get_post_meta( $post->ID, 'Commission', true );
        $country = get_post_meta( $post->ID, 'Card_Country', true );
        $card_no = get_post_meta( $post->ID, 'Card_Number', true );
        $bank = get_post_meta( $post->ID, 'Card_Bank', true );
        $card_type = get_post_meta( $post->ID, 'Card_Type', true );
        $holder = get_post_meta( $post->ID, 'Card_Holders_Name', true );

        echo "
        	<ul>
            	<li>Amount paid: $amount</li>
            	<li>Commission: $commission</li>
            	<li>Card type: $card_type</li>
            	<li>Bank: $bank ($country)</li>
            	<li>Card number: $card_no</li>
            	<li>Name on card: $holder</li>
            </ul>


        ";

    }
}
