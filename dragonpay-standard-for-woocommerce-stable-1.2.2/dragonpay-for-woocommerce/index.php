<?php
/*
	Plugin Name: Dragonpay.ph Standard For WooCommerce
	Plugin URI: https://github.com/siteshop-ph/dragonpay-standard-for-woocommerce/
	Description: Dragonpay Standard for accepting Over-The-Counter Cash Payments, ATM or Online Banking in the Philippines. Because credit card and banking penetration is too low ; Dragonpay makes accessible payment options to the masses! This plugin require 1/ Webhosting: VPS or dedicated server are OK (IMPORTANT: shared server or WP managed service are NOT SUPPORTED)  2/ a Dragonpay Account to order at ; <a href="https://dragonpay.ph" target="_blank">dragonpay.ph</a>
	Version: 1.2.2
	Author: Serge Frankin SiteShop.ph (Netpublica.com Corp)
	Author URI: https://siteshop.ph/
*/ 








//Load the function
add_action( 'plugins_loaded', 'woocommerce_dragonpay_init', 0 );

/**
 * Load Dragonpay gateway plugin function
 * 
 * @return mixed
 */
function woocommerce_dragonpay_init() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
         return;
    }













error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);


     // to prevent such notice when wp debug mode is enabled
          /*

          Notice: Constant CRYPT_AES_MODE_MCRYPT already defined in /home/demo-coinsph-woocommerce/public_html/wp-content/plugins/dragonpay-for-woocommerce/lib/Crypt/AES.php on line 123

          Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; Crypt_Hash has a deprecated constructor in /home/demo-coinsph-woocommerce/public_html/wp-content/plugins/dragonpay-for-woocommerce/lib/Crypt/Hash.php on line 94

         */


  




    




    /**
     * Define the Dragonpay gateway
     * 
     */
    class WC_Controller_Dragonpay extends WC_Payment_Gateway {

        /**
         * Construct the Dragonay gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {

            global $woocommerce;

            $this->id = 'dragonpay';
            $this->icon = plugins_url( 'assets/dragonpay.png', __FILE__ );
            $this->has_fields = false;
          
            $this->method_title = __( 'Dragonpay.ph Standard', 'woocommerce_dragonpay' );


            // Load the form fields.
            $this->init_form_fields();


            // Load the settings.
            $this->init_settings();





            // Define user setting variables.
            $this->enabled = $this->settings['enabled'];
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];


                       
   


            // Actions.
            add_action( 'woocommerce_receipt_dragonpay', array( &$this, 'receipt_page' ) );



            // Active logs.
		if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}

            


          //save setting configuration
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
               
         // Payment API hook
         add_action( 'woocommerce_api_wc_controller_dragonpay', array( $this, 'dragonpay_response' ) );

        

        }



        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options() {
            ?>
            <h3><?php _e( 'Dragonpay.ph Standard', 'woocommerce_dragonpay' ); ?></h3>
            <p><?php _e( 'Dragonpay is most popular payment gateway in the Philippines, its makes possible to buy online and pay cash OR pay online.', 'woocommerce_dragonpay' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }





        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce_dragonpay' ),
                    'type' => 'checkbox',
                    'label' => __( '  Enable/Disable Plugin' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce_dragonpay' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_dragonpay' ),
                    'default' => __( 'Dragonpay | Secure Payments in the Philippines', 'woocommerce_dragonpay' )
                ),
                'description' => array(
                    'title' => __( 'Description', 'woocommerce_dragonpay' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce_dragonpay' ),
                    'default' => __( 'Pay over-the-counter with Cash Payments (you will receive email instruction from Dragonpay) OR Pay Online', 'woocommerce_dragonpay' )
                ),
         		'merchant_id' => array(
                    'title' => __( 'Dragonpay Merchant ID', 'woocommerce_dragonpay' ),
                    'type' => 'text',
                    'description' => __( 'Enter your Merchant ID for Dragonpay.', 'woocommerce_dragonpay' ),
                    'default' => 'my-dragonpay-merchant-id'
                ),
                'dragonpay_api_password' => array(
                    'title' => __( 'Dragonpay API Password', 'woocommerce_dragonpay' ),
                    'type' => 'text',
                    'description' => __( 'Enter your Dragonpay API Password.', 'woocommerce_dragonpay' ),
                    'default' => 'my-dragonpay-api-password'
                ),
                'test_mode' => array(
                    'title' => __( 'Gateway Test Mode', 'woocommerce_dragonpay' ),
                    'type' => 'checkbox',
                    'description' => __( 'Enable this if you want to use your Dragonpay Test Account with no real money transaction, when disabled you will be using your Dragonpay Production Account', 'woocommerce_dragonpay' ),
                    'default' => 'yes'
	        ), 
	        	'debug' => array(
		            'title' => __( 'Debug Log', 'woocommerce-dragonpay' ),
	                'type' => 'checkbox',
	                'label' => __( 'Enable Debug log', 'woocommerce-dragonpay' ),
		            'default' => 'yes',
                    'description' => sprintf( __( 'Log Dragonpay events, such as Web Redirection, Notification, Daily Txns Synchronization, inside: log file %s', 'woocommerce-dragonpay' ), '<code>wp-content/uploads/wc-logs/dragonpay-' . sanitize_file_name( wp_hash( 'dragonpay' ) ) . '.log</code>&nbsp;&nbsp;&nbsp;<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs">See log file content</a>' )
		), 
		        'last_ran_cron_synchronization' => array(
		            'title' => __( 'Daily Order Status Synchronization', 'woocommerce-dragonpay' ),
	                'type' => 'text',
	                'label' => __( 'Last Sync timestamp', 'woocommerce-dragonpay' ),
		            'default' => '',
                    'disabled' => true,
	                'description' => __( 'Last Successful Sync - Timestamp<br><br><u>Explanation:</u><br>Dragonpay Gateway send one single notification by transaction (Txns) status update without resending if the communication was not received. Short Internet connection interruptions are possible, and having missing update status on orders in your WooCommerce was not an option. That why we integrated Dragonpay GetMerchantTxns - Web Service Method with WooCommerce - WP-Cron, it s an auto scheduled task run every 24 hours to synchronize your last 7 days transactions at Dragonpay to never missing transactions and order status update in WooCommerce. 
', 'woocommerce_dragonpay' )
		),
                'shopping_cart_id' => array(
		            'title' => __( 'Shopping Cart ID (optional)', 'woocommerce-dragonpay' ),
	                'type' => 'text',
	                'label' => __( 'Shopping Cart ID', 'woocommerce-dragonpay' ),
		            'default' => '',
	                'description' => __( 'Shopping Cart ID (optional), only required when using <a href="https://siteshop.ph/multi-cart-redirector-for-dragonpay" target="_blank" >Multi-Cart Redirector for Dragonpay</a>', 'woocommerce_dragonpay' )
		),		
         'enable_proc_id' => array(
                    'title' => __( 'Enable Proc Id', 'woocommerce_dragonpay' ),
                    'type' => 'checkbox',
                    'description' => __( 'Enable only this if you want shoppers to go directly to a given payment channel without having to select it from the dropdown list', 'woocommerce_dragonpay' ),
                    'default' => 'no'
	        ),	
		'proc_id' => array(
		     'title' => __( 'Proc Id (optional)', 'woocommerce-dragonpay' ),
	             'type' => 'text',
	             'label' => __( 'Proc Id', 'woocommerce-dragonpay' ),
		     'default' => '',
	             'description' => __( 'processor id’s (optional) Dragonpay has very basic support to allow merchant to go directly to a payment channel without having to select it from the dropdown list', 'woocommerce_dragonpay' )
		),		
		'display_return_url' => array(
		     'title' => 'Return URL',
                     'type' => 'title',
		     'description' => 'This URL should be communicated to Dragonpay Support:<br><font color="red"><code>'.WC()->api_request_url('WC_Controller_Dragonpay').'</code></font>',
		     'desc_tip' => false,
                     'default' => ''
		),
		'display_postback_url' => array(
		     'title' => 'Postback URL',
                     'type' => 'title',
		     'description' => 'This URL should be communicated to Dragonpay Support:<br><font color="red"><code>'.WC()->api_request_url('WC_Controller_Dragonpay').'</code></font>',
		     'desc_tip' => false,
                     'default' => ''                     
		)          
            );
        }



















        /**
         * Generate the form.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_form( $order_id ) {

	    $order = new WC_Order( $order_id ); 
            
   
 
          





           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {

                   // take CUSTOM order id string from WooCommerce Plugin "Custom Order Numbers" (there can be prefix, etc)
	           $merchantTxnId = $order->custom_order_number; 

           }else{

                   // just use regular woocommerce order id as txnid for dragonpay
                        // $merchantTxnId = $order->id;      // old way
                        $merchantTxnId = $order->get_id();   // new way
 
           }






















$merchantId = get_option('woocommerce_dragonpay_settings')['merchant_id'];

$dragonpay_api_password = html_entity_decode(get_option('woocommerce_dragonpay_settings')['dragonpay_api_password']);

$dragonpay_param2 = get_option('woocommerce_dragonpay_settings')['shopping_cart_id'];  

$proc_id = get_option('woocommerce_dragonpay_settings')['proc_id'];  




//for test     you have also to disable this line below:  header("Location: $url_request_params");
    //echo "merchantid:  ". $merchantId;
    //echo "dragonpay_api_password:  ". $dragonpay_api_password;     // IMPORTANT MAKE IT FALSE BEFORE TO ECHO
    //echo "dragonpay_param2:  ". $dragonpay_param2;














//////////////////////////////  START :   Send billing info to Dragonpay (Via SOAP/xml) , needed for credit card transaction    ///////////////////////////////////////////////////////////////////////



     // Use the sandbox if you're testing. (Required: Sandbox Account with Dragonpay)
	 if(get_option('woocommerce_dragonpay_settings')['test_mode'] == 'yes'){
		 // TEST.
		$urlWebService = 'https://test.dragonpay.ph/DragonpayWebService/MerchantService.asmx';   // httpS needed if not API do not answer                         

	}else{
		 // LIVE
		$urlWebService = 'https://gw.dragonpay.ph/DragonPayWebService/MerchantService.asmx';                
	}





       // for hard coded test
              //$urlWebService = "https://test.dragonpay.ph/DragonpayWebService/MerchantService.asmx";



        
     
 








  // get String for :
        // $firstName = $order->billing_first_name;        // old way
        $firstName = $order->get_billing_first_name();     // new way
        
        // $lastName = $order->billing_last_name;          // old way
        $lastName = $order->get_billing_last_name();       // new way
        
        // $address1 = $order->billing_address_1;          // old way
        $address1 = $order->get_billing_address_1();       // new way
        
        // $address2 = $order->billing_address_2;          // old way
        $address2 = $order->get_billing_address_2();       // new way
        
        // $city = $order->billing_city;                   // old way
        $city = $order->get_billing_city();                // new way
        
        // $state = $order->billing_state;                 // old way
        $state = $order->get_billing_state();              // new way
        
        // $country = $order->billing_country;             // old way
        $country = $order->get_billing_country();          // new way
        
        // $zipCode = $order->billing_postcode;            // old way
        $zipCode = $order->get_billing_postcode();         // new way
        
        // $telNo = $order->billing_phone;                 // old way
        $telNo = $order->get_billing_phone();              // new way
        
        //$email = $order->billing_email;                  // old way
        $email = $order->get_billing_email();              // new way
        



  // For check purpose:     you have also to disable this line below:  header("Location: $url_request_params");

 /*
          echo "merchantId:  " . $merchantId . "<br><br>";
          echo "merchantTxnId:  " . $merchantTxnId . "<br><br>";
          echo "firstName:  " . $firstName . "<br><br>";
          echo "lastName:  " . $lastName . "<br><br>";
          echo "address1:  " . $address1 . "<br><br>";                   
          echo "address2:  " . $address2 . "<br><br>";
          echo "city:  " . $city . "<br><br>";
          echo "state:  " . $state . "<br><br>";
          echo "country:  " . $country . "<br><br>";
          echo "zipCode:  " . $zipCode . "<br><br>";
          echo "telNo:  " . $telNo . "<br><br>";
          echo "email:  " . $email . "<br><br>";

*/











        // xml post structure

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
                               <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                                  <soap12:Body>
                                     <SendBillingInfo xmlns="http://api.dragonpay.ph/">
                                         <merchantId>' . $merchantId . '</merchantId>
                                         <merchantTxnId>' . $merchantTxnId . '</merchantTxnId>
                                         <firstName>' . $firstName . '</firstName>
                                         <lastName>' . $lastName . '</lastName>
                                         <address1>' . $address1 . '</address1>
                                         <address2>' . $address1 . '</address2>
                                         <city>' . $city . '</city>
                                         <state>' . $state . '</state>
                                         <country>' . $country . '</country>
                                         <zipCode>' . $zipCode . '</zipCode>
                                         <telNo>' . $telNo . '</telNo>
                                         <email>' . $email . '</email>
                                     </SendBillingInfo>
                                   </soap12:Body>
                                </soap12:Envelope>';


 

           $headers = array(
                        "Content-type: application/soap+xml;charset=\"utf-8\"",
                        //"Accept: application/soap+xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        //"SOAPAction: https://test.dragonpay.ph/DragonpayWebService/MerchantService.asmx", 
                        "Content-length: ".strlen($xml_post_string),
                    ); 

     


            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $urlWebService);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);




            // purge old values if there are
            $response = "";
            $response1 = "";
            $response2 = "";
            $parser = "";
            $SendBillingInfoResult = "" ;



            // send the request to dragonpay
            $response = curl_exec($ch); 
            
            // close connection
            curl_close($ch);




            // remove <soap:Body> tags
            $response1 = str_replace("<soap:Body>","",$response);
            $response2 = str_replace("</soap:Body>","",$response1);



            // converting to XML
            $parser = simplexml_load_string($response2);
            // user $parser to get your data out of XML response and to display it.


           // SendBillingInfoResult            if  result is "0" it's OK          if result is "-1"             it's WRONG                (we can only submit once with same $merchantTxnId)  if "-1000" it's unknow merchantID
           $SendBillingInfoResult = $parser->SendBillingInfoResponse[0]->SendBillingInfoResult ;
              // this can work as well
                //$SendBillingInfoResult = $parser->SendBillingInfoResponse->SendBillingInfoResult ;
            
          
    

              // For check purpose:      you have also to disable this line below:  header("Location: $url_request_params");
                    //    echo "SendBillingInfoResult:  " . $SendBillingInfoResult . "<br><br>"; 
                    //    echo "Full Answer:  " . $response . "<br><br>"; 
                      
                   

           



//////////////////////////////  END :   Send billing info to Dragonpay (Via SOAP/xml) , needed for credit card transaction    ///////////////////////////////////////////////////////////////////////

 












 
            ## Hostname of woocommerce install
            $hostname = $_SERVER['HTTP_HOST']; 




	    //$amount = $order->order_total;
          //$amount = number_format ($order->order_total, 2, '.' , $thousands_sep = '');   // old way
          $amount = number_format ($order->get_total(), 2, '.' , $thousands_sep = '');     // new way
	    $ccy = get_woocommerce_currency();
	    $description = 'Your order Ref. '.$merchantTxnId.' on '.$hostname;
	      // $email = $order->get_billing_email();    // ever set before
	     // $dragonpay_api_password = html_entity_decode(get_option('woocommerce_dragonpay_settings')['dragonpay_api_password']);     // ever set before
	    
	  



             ## purge old values if there are
             $digest_str = "";
             $digest = "";
             $param1 = "";
             $param2 = "";


     
            ## create the digest for Dragonpay
            $digest_str = $merchantId.':'.$merchantTxnId.':'.$amount.':'.$ccy.':'.$description.':'.$email.':'.$dragonpay_api_password ;  


            ## to create 40 Char sha1
            $digest = sha1($digest_str, $raw_output = false);


             $param1 = $amount;  
             $param2 = $dragonpay_param2;



             
             
             

              if ( 'no' == get_option('woocommerce_dragonpay_settings')['enable_proc_id'] ) {
                   
              	  // Case Proc ID is NOT enabled:
              	  
              	      // submitting procid parameter with empty value give error at dragonpay, so we remove the parameter when proc_id is NOT used/enabled
              	      
              	              $dragonpay_args = array(
                                'merchantid' => $merchantId,
                                'txnid' => $merchantTxnId,
                                'amount' => $amount,
                                'ccy' => $ccy,
                                'description' => $description,
                                'email' => $email,
                                'digest' => $digest,
                                'param1' => $param1,
                                'param2' => $param2,
    
                             );
                              
 
            }else{ 
            	
            	
            	// Case Proc ID    ENABLED:
              	  
                           $dragonpay_args = array(
                                'merchantid' => $merchantId,
                                'txnid' => $merchantTxnId,
                                'amount' => $amount,
                                'ccy' => $ccy,
                                'description' => $description,
                                'email' => $email,
                                'digest' => $digest,
                                'param1' => $param1,
                                'param2' => $param2,
                                'procid' => $proc_id,
                
                              );
 
                           
               }       
                           
                           
                           
   
               
               
              	  
              	  
              	  

            $dragonpay_args_array = array();






        if(get_option('woocommerce_dragonpay_settings')['test_mode'] == 'yes'){
		            $url = 'https://test.dragonpay.ph/Pay.aspx?'; // httpS version must be used, if not API may not answer
	   }else{
		           $url = 'https://gw.dragonpay.ph/Pay.aspx?';                
	}




			foreach ($dragonpay_args as $key => $value) {
			     	$dragonpay_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}
















          if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                   if(get_option('woocommerce_dragonpay_settings')['test_mode'] == 'yes'){

			   $this->log->add( 'dragonpay', 'Dragonpay Transaction Initiated - Dragonpay Test Account Used - Order #' . $order->get_order_number() );
	           
                   }else{

                           $this->log->add( 'dragonpay', 'Dragonpay Transaction Initiated - Dragonpay Production Account Used - Order #' . $order->get_order_number() );

                   }

          }













// for test
  //echo $merchantId;
  // echo get_option('woocommerce_dragonpay_settings')['test_mode'];









// START:    THIS BLOCK CAN BE DISABLED TO    STOP REDIRECTION TO DRAGONPAY


			wc_enqueue_js( '
				jQuery.blockUI({
					message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Dragonpay Site.', 'woocommerce-dragonpay' ) ) . '",
					baseZ: 99999,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:        "20px",
						zindex:         "9999999",
						textAlign:      "center",
						color:          "#555",
						border:         "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:         "wait",
						lineHeight:		"24px",
					}
				});
				jQuery("#submit-payment-form").click();
			' );


// END:    THIS BLOCK CAN BE DISABLED TO    STOP REDIRECTION TO DRAGONPAY










		return '<form action="' . esc_url( $url ) . '" method="get" id="payment-form" target="_top">
				' . implode( '', $dragonpay_args_array ) . '
				<input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Pay via Dragonpay', 'woocommerce-dragonpay' ) . '" /> 
			</form>';


        }









        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function dragonpay_order_error( $order ) {
            $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce_dragonpay' ) . '</p>';
            $html .='<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'woocommerce_dragonpay' ) . '</a>';
            return $html;
        }










        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */

    
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

			return array(
				'result'   => 'success',
			   'redirect' => $order->get_checkout_payment_url( true )
			);

      }

   











        /**
         * Output for the order received page.
         * 
         */
        public function receipt_page( $order ) {
            echo $this->generate_form( $order );
            
        }




















function dragonpay_response($merchantTxnId_to_use){


// Example of url response from Dragonpay:
//     http://demo-woocommerce.siteshop.ph/?wc-api=WC_Controller_Dragonpay&txnid=221&refno=EMTMF7Y1&status=F&message=%5b000%5d+BOG+Reference+No%3a+20150408212056+%23EMTMF7Y1+%7b4%2f8%2f15+21%3a20%7d&digest=9711fde2f630be386831fe429ead2761484b4697&param1=47.00&param2=woocommerce


// Example of url after woocommerce internal redirection (url ending point):
//      http://demo-woocommerce.siteshop.ph/checkout/order-received?order=224&key=wc_order_552669a6f1e08
	
   


      global $woocommerce;


        ## Purge old values
        $dragonpay_response_txnid = "";
        $dragonpay_response_refno = "";
        $dragonpay_response_status = "";
        $dragonpay_response_message = "";
        $dragonpay_response_digest = "";
        $dragonpay_response_param1 = "";
        $dragonpay_response_param2 = "";



        ## Get data response (GET & POST) from Dragonpay
        $dragonpay_response_txnid = $_REQUEST['txnid'];        
        $dragonpay_response_refno = $_REQUEST['refno'];
        $dragonpay_response_status = $_REQUEST['status'];
        $dragonpay_response_message = $_REQUEST['message'];
        $dragonpay_response_digest = $_REQUEST['digest'];
        $dragonpay_response_param1 = $_REQUEST['param1'];
        $dragonpay_response_param2 = $_REQUEST['param2'];




        





        ## check the digest  

        ## Purge old values
        $response_digest_str = "";
        $true_digest = "";


        $dragonpay_api_password = html_entity_decode(get_option('woocommerce_dragonpay_settings')['dragonpay_api_password']);

        ## As per Dragonpay requirement, param1 & param2 are not used to create the below digest 
        ## N.B.: strings bellow to calculate the digest are string in an URL DECODE format.  
 

       $response_digest_str = $dragonpay_response_txnid.':'.$dragonpay_response_refno.':'.$dragonpay_response_status.':'.$dragonpay_response_message.':'.$dragonpay_api_password ;
	     
       $true_digest = sha1($response_digest_str, $raw_output = false);   ## to create 40 Char sha1









           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {


                 global $wpdb;

                 $wpdb->postmeta = $wpdb->base_prefix . 'postmeta';

		$retrieved_order_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_custom_order_number' AND meta_value = '$dragonpay_response_txnid'" );		
		  
                 $merchantTxnId_to_use = $retrieved_order_id;

                  //for test
                  //echo 'case custom order number plugin used';
                  //echo $merchantTxnId_to_use;



           }else{

                  // just use regular woocommerce order (real woocommerce order = txnid used with dragonpay)
                  $merchantTxnId_to_use = $dragonpay_response_txnid;
 

                  // for test
                  // echo 'case no custom order number plugin used';
                  //echo $merchantTxnId_to_use;

           }














// Dragonpay related data received (they can be false or true)
// This is for not having log writting for non-related to dragonpay data received
if ( isset( $dragonpay_response_digest ) ) {





$order = new WC_Order( $merchantTxnId_to_use );  // important this must be located here for being also able to get log for very bellow case when digest is wrong 




 
         // Disable this line when testing all gateway type of response : S, P, K, A, U, R, V
    if( $dragonpay_response_digest == $true_digest ) {     










   /// check if order status exist in woocommerce


   //////////////////////////////////////////////


   // IMPORTANT  NONE OF THIS OTHER WAY WAS WORKING:

//if(!is_null($merchantTxnId_to_use)) {                 // ok with custom_order_numbers plugin used       NO without plugin
//if(isset($merchantTxnId_to_use)) {                    // ok with custom_order_numbers plugin used     NO without plugin
//if(!is_null($merchantTxnId_to_use) AND isset($merchantTxnId_to_use) ) {
//$order = if(new WC_Order( $merchantTxnId_to_use )){   // ;
// only continue if 
// if (!is_null(new WC_Order( $merchantTxnId_to_use))) {
// only continue with order existing in woocommerce
//$status = $order->status;
//if(isset($tatus)) {
//if(is_bool($status)) {

   ///////////////////////////////////////////










   /////////  check if order status exist in woocommerce

   $post_status = "";

   global $wpdb;  
   $wpdb->posts = $wpdb->base_prefix . 'posts';      
   $post_status = $wpdb->get_var( "SELECT post_status FROM $wpdb->posts WHERE post_type = 'shop_order' AND ID = '$merchantTxnId_to_use'");

   // for test
   //echo "post_status:  ".$post_status;
   //echo "strlen:  " .strlen($post_status);



   // only continue with existing order in woocommerce (that have an existing order status)
   // for info: when custom_order_numbers plugin used and if no order is found, this custom_orders_numbers plugin set post_id (real woocomerce order id) to zero "0"
   // if status have at least 2 characters long it's exist 
   if(strlen($post_status) > 2 ) {

   ////////////////////













///////////////// Available WooCommerce order status   //////////////////////////////
////////////////////////////////////////////////////////////////////////////////////
////    Pending     – Order received (unpaid)
////    Failed      – Payment failed or was declined (unpaid)
////    Processing  – Payment received and stock has been reduced- the order is awaiting fulfilment
////    Completed   – Order fulfilled and complete – requires no further action
////    On-Hold     – Awaiting payment  
////    Cancelled   – Cancelled by an admin or the customer – no further action required
////    Refunded    – Refunded by an admin – no further action required
/////////////////////////////////////////////////////////////////////////////////////





                                       
     	


	      switch ( $dragonpay_response_status ) {
			




                      
                          #################### CASE:  transaction is "S" (SUCCESS) ####################
                          case 'S':
						

				   if($order->status == 'processing' OR $order->status == 'completed' OR $order->status == 'cancelled'){
                                   				         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)


                                        
                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         
                                    
                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34

                                    

                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - SUCCESSFUL Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	



                                      
				    }else{



                                         // update order status (an admin note will be also created)
                                         $order->update_status('processing'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: payment SUCCESSFUL<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to PROCESSING<br/> -> We will be shipping your order to you soon', 1); 

                                         // reduce stock
				         $order->reduce_order_stock(); // if physical product vs downloadable product

                                         //empty cart
                                         $woocommerce->cart->empty_cart();



                                         ///////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         
                                      
                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34
 
                                     


                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - SUCCESSFUL Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to PROCESSING - SUCCESSFUL Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }


                                         exit;	

				    }  


                                   break;


	             













                   #################### CASE transaction is "F" (FAILURE) ####################
                   case 'F':                    
                                  

				   if($order->status == 'failed' OR $order->status == 'cancelled'){
                                  				         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////




                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - FAILED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('failed'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction FAILED<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to FAILED<br/> -> IMPORTANT: Please go in "My Account" section and retry to pay order', 1);   

                                         // no reduce order stock needed


	                                 //empty cart
                                         $woocommerce->cart->empty_cart();



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////




                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - FAILED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to FAILED - FAILED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }



                                         exit;	

				    }  


                                    break;

















                   #################### Case transaction is "P" (PENDING) waiting deposit for OTC ####################
                   case 'P':                    
                                  

				   if($order->status == 'on-hold' OR $order->status == 'cancelled'){
                                  				         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)

                                        


                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - PENDING Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	

                                      

				    }else{

	         
                                         // update order status (an admin note will be also created)
                                         $order->update_status('on-hold'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction PENDING<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to ON-HOLD<br/> -> IMPORTANT: Please follow deposit instructions emailed by Dragonpay', 1);   
	         
                                         // no reduce order stock needed


                                         //empty cart
                                         $woocommerce->cart->empty_cart();




                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - PENDING Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                           $this->log->add( 'dragonpay', 'Order updated to ON-HOLD - PENDING Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    
	                                            }


                                         exit;	

				    }  


                                    break;























                   #################### Case transaction is "U" (UNKNOWN  STATUS) ####################
                   case 'U':                   
                                  


				   // Nothing to do:




                                          // This case should even not happen as Dragonpay do not send notify for the "U" status




                                          /* 
                                          N.B.:
                                          At woocommerce by design all created order start/have "pending payment" order status,

                                          So we preffer to do nothing for received "U" status as we ever use "on-hold" woocommerce order
                                          status upate when we receive "P" notification, and for "P" case, customer & admin ever have note/
                                          instruction in their dashboard regarding an expected OTC cash payment. 


                                          So, Best to ignore the "U" status, for it's do not confuse merchants/clients
                                          */




                                    break;

                   















                   #################### Case  transaction is "R" (REFUND) ####################
                   case 'R':                   
                                  
				   if($order->status == 'refunded' OR $order->status == 'cancelled'){
                                  			         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - REFUNDED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('refunded'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: payment REFUNDED<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to REFUNDED', 1);   

                                         // no reduce order stock needed


	                                 //empty cart
                                         $woocommerce->cart->empty_cart();



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - REFUNDED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to REFUNDED - REFUNDED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }



                                         exit;	

				    }  


                                    break;














                   #################### Case  transaction is "K" (CHARGEBACK) ####################
                   case 'K':                   
                                  
				   if($order->status == 'refunded' OR $order->status == 'cancelled'){
                                  			         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)

                                        

                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - CHARGEBACK Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('refunded'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction CHARGEBACK<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to REFUNDED', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         $woocommerce->cart->empty_cart();



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - CHARGEBACK Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to REFUNDED - CHARGEBACK Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }


                                         exit;	

				    }  


                                    break;














                   #################### Case transaction is "V" (VOID  STATUS) ####################
                   case 'V':                   
                                  
				   if($order->status == 'cancelled'){
                                  			         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - VOID Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }


                                         exit;	


                                      
				    }else{
                                       

                                         // update order status (an admin note will be also created)
                                         $order->update_status('cancelled'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction VOID<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to CANCELLED', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         $woocommerce->cart->empty_cart();




                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - VOID Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to CANCELLED - VOID Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }


                                         exit;	

				    }  


                                    break;
















                   #################### Case  transaction is "A" (AUTHORIZED) ####################
                   case 'A':                   
                                  
				   if($order->status == 'on-hold' OR $order->status == 'cancelled'){
                                  			         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////



                                                   if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                                           $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - AUTHORIZED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );
                                                    }

                                         exit;	


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('on-hold'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction AUTHORIZED<br/> -> Dragonpay transaction #'.$dragonpay_response_refno.'<br/> -> Order status updated to ON-HOLD<br/> -> We are waiting to receive fund', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         $woocommerce->cart->empty_cart();



                                         ///////////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $merchantTxnId_to_use, $this->get_return_url($this->order)));
                                         

                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );
                                       
                                         $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $merchantTxnId_to_use, $this->get_return_url($this->order)));


                                        // Example of retrived url  ($redirect value)
                                        // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                        // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34



                                        wp_redirect($redirect); //do the redirect
                                        /////////////////




                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {

                                                          $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - AUTHORIZED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

                                                          $this->log->add( 'dragonpay', 'Order updated to ON-HOLD - AUTHORIZED Transaction #'.$dragonpay_response_refno.' - For Order #' . $order->get_order_number() );

	                                            }



                                         exit;	

				    }  


                                    break;



















                   #################### Case  transaction is  NO STATUS CODE GIVEN IN BACK ####################
                   default :                                                    
 
                                    // Do the redirection

                                    wp_redirect(home_url('/')); //redirect to homepage


                                    exit;

                                    break;


















}     //END:      Switch           

   












}      // END:        if order exist in woocommerce:    if(strlen($post_status) > 2 )














//            /*           // Enable this line when testing all gateway type of response : S, P, K, A, U, R, V









}else{               // end if digest is true       



             // Case  digest was false


                       // Do the redirection
                       wp_redirect(home_url('/')); //redirect to homepage


                                  if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug'] ) {
                                  $this->log->add( 'dragonpay', 'Dragonpay: Communication Received - WRONG DIGEST - Transaction #'.$dragonpay_response_refno.' - For order/txnid #' .$dragonpay_response_txnid.' - *** IMPORTANT ***: If you are testing the Gateway, you should double check Dragonpay Merchant ID and Dragonpay API password you filled at: WooCommerce -> Settings -> Checkout -> Dragonpay.ph Standard' );
	                           }



                        exit;




}






//            */             // Here enable this line when testing all gateway type of response : S, P, K, A, U, R, V
















}else{  


             // case there was no digest parameters (no related to Dragonpay data received)



                       // Do the redirection
                       wp_redirect(home_url('/')); //redirect to homepage


                      
                       // no log writting needed



                       exit;
                       




}

}

}
















	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/	
		
        
		function dragonpay_plugin_action_links($links, $file) {
		    static $this_plugin;

		    if (!$this_plugin) {
		        $this_plugin = plugin_basename(__FILE__);
		    }

		    if ($file == $this_plugin) {
		        $settings_link = '<a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_controller_dragonpay">Settings</a>';

		        array_unshift($links, $settings_link);
		    }
		    return $links;
		}
	

               add_filter('plugin_action_links', 'dragonpay_plugin_action_links', 10, 2);

       

















////////////////////  START   SYNCRONISATION  WITH DRAGONPAY  ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "synchronization.php"; 



////////////////////  END   SYNCRONISATION  WITH DRAGONPAY  ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


















////////////////////  START   SYNCRONISATION 2  WITH DRAGONPAY  ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

       // for void transaction at dragonpay account for woo order having cancelled status

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "synchronization2.php";    



////////////////////  END   SYNCRONISATION 2 WITH DRAGONPAY  ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


















	/**
 	* Add Dragonpay Gateway to WC
 	**/
    function woocommerce_dragonpay_add_gateway( $methods ) {
        $methods[] = 'WC_Controller_Dragonpay';
        return $methods;
    }


    add_filter( 'woocommerce_payment_gateways', 'woocommerce_dragonpay_add_gateway' );























}











?>
