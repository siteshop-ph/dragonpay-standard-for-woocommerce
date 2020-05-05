<?php







////////////// create wp cron to run syncronization  /////////////
if ( ! wp_next_scheduled( 'woocommerce_dragonpay_void_transactions_for_cancelled_orders' ) ) {
  wp_schedule_event( time(), 'daily', 'woocommerce_dragonpay_void_transactions_for_cancelled_orders' );
}

     // cron tasks are stored in wp_options table option_name=cron

add_action( 'woocommerce_dragonpay_void_transactions_for_cancelled_orders', 'void_transactions_for_cancelled_orders' );
///////////////////////////////////////////////////////////////


















function void_transactions_for_cancelled_orders() {





$new_instance = new WC_Controller_Dragonpay();

// call this function within the class
$new_instance->__construct();  








                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                             $new_instance->log->add( 'dragonpay', 'START CRON: void transactions for cancelled orders' );  
                  
                     }










  






// Create date_from variable
function DateStart() {
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $date->modify('-7 day');           // to get DateStart set at 7 days from now, this for check status of transactions from OTC
    echo $date->format('Y-m-d'); // to get DateTime in format requested here 2014-09-03
}
ob_start();
DateStart();
$date_from = ob_get_clean();

// For test purpose
//echo $date_from ;  // to check that the DateTime match to Manila Time












// Create $date_to variable
function DateEnd() {
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    echo $date->format('Y-m-d');  // to get DateTime in format requested here 2014-09-03
   // echo $date->format('Y-m-d');      //  this can work also with Dragonpay, but it's less precise
}
ob_start();
DateEnd();
$date_to = ob_get_clean();

// For test purpose
//echo $date_to;  // to check that the DateTime match to Manila Time














               if (file_exists(dirname(__FILE__).'/date_from.php')) {
    
                       require_once (dirname(__FILE__).'/date_from.php') ;  // for allow update with an earlier that 7 days old date

               }


















    global $wpdb;




    $cancelled_orders = $wpdb->get_results( "SELECT * FROM $wpdb->posts 
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-cancelled')
            AND post_date BETWEEN '{$date_from}  00:00:00' AND '{$date_to} 23:59:59'"
        );











    if ( $cancelled_orders ) {





        foreach ( $cancelled_orders as $cancelled_order ) {



            $order = new WC_Order( $cancelled_order );






 
          $merchantTxnId_to_use = trim( str_replace( '#', '', $order->get_order_number() ) );



                   





/////////////  START:  Cancelled orders at woo that must past dragonpay transaction as V Voided by merchant at Dragonpay account ///////////////////

                  





                if(get_option('woocommerce_dragonpay_settings')['test_mode'] == 'yes'){

		            $dragonpay_url = 'https://test.dragonpay.ph/MerchantRequest.aspx';     // httpS needed if not API do not answer                         

	               }else{

		           $dragonpay_url = 'https://gw.dragonpay.ph/MerchantRequest.aspx';       
         
                }
















            // prepare variables for dragonpay cancellation request:
            $dragonpay_merchantid = get_option('woocommerce_dragonpay_settings')['merchant_id'];
            $dragonpay_merchantpwd = html_entity_decode(get_option('woocommerce_dragonpay_settings')['dragonpay_api_password']);
            $dragonpay_txnid = $merchantTxnId_to_use;  // woo order ref
            
         









            // is url encode NEEDED HERE ???
                  // Let's prepare the send to Dragonpay:   using urlencode to get URL format
                           $dragonpay_request_params = "op=" . urlencode("VOID") .
		             "&merchantid=" .  urlencode($dragonpay_merchantid) . 
		             "&merchantpwd=" . urlencode($dragonpay_merchantpwd) .
		             "&txnid=" . urlencode($dragonpay_txnid)
		              ;








           // example https://gw.dragonpay.ph/MerchantRequest.aspx?op=VOID&merchantid=ABC&merchantpwd=MySecret&txnid=12345678





           $dragonpay_url_request_params = $dragonpay_url .'?'. $dragonpay_request_params;





              //$dragonpay_response_cancel_transaction = file_get_contents($dragonpay_url_request_params); // PHP native way, DO NOT work from WP
            $dragonpay_response_cancel_transaction = wp_remote_get($dragonpay_url_request_params); // WP way




            // result in dragonpay account & transaction detail/log:     Status changed to V with msg: Voided by merchant





     


/////////////  END:  Cancelled orders at woo that must past dragonpay transaction as "V" Voided by merchant at Dragonpay account ///////////////////







        }  // END:    foreach ( $cancelled_orders as $cancelled_order ) {







 
    }  // END:   if ( $cancelled_orders ) {


















///////////////////////////









 // store last time ran for this cron
 $options = get_option('woocommerce_dragonpay_settings');
 // update it
 $options['last_ran_cron_synchronization'] = date('Y-m-d\TH:i:s');
 // store updated data     
 update_option('woocommerce_dragonpay_settings',$options);










                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {
                            
                              $new_instance->log->add( 'dragonpay', 'CRON: void transactions for cancelled orders DONE');

                              $new_instance->log->add( 'dragonpay', 'END CRON: void transactions for cancelled orders');
 
                        }

















/////////////////////////////










      } // END   function void_transactions_for_cancelled_orders() {          // Disable here for test ECHO








?>
