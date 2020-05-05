<?php







////////////// create wp cron to run syncronization  /////////////
if ( ! wp_next_scheduled( 'woocommerce_dragonpay_synchronization' ) ) {
  wp_schedule_event( time(), 'daily', 'woocommerce_dragonpay_synchronization' );
}

     // cron tasks are stored in wp_options table option_name=cron

add_action( 'woocommerce_dragonpay_synchronization', 'synchronization' );
///////////////////////////////////////////////////////////////












   function synchronization() {       
                         // for test ECHO:  Disable  1/ here   and  2/ at closing of this function  3/enable debug mode in wp config file





$new_instance = new WC_Controller_Dragonpay();

// call this function within the class
$new_instance->__construct();  





      
          echo PHP_EOL . 'START CRON' . PHP_EOL . PHP_EOL;

        

                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                             $new_instance->log->add( 'dragonpay', 'START CRON: Synchronization' );  
                  
                     }















$ws_merchantId = get_option('woocommerce_dragonpay_settings')['merchant_id'];




$ws_password = html_entity_decode(get_option('woocommerce_dragonpay_settings')['dragonpay_api_password']);









// Create DateStart variable
function DateStart() {
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $date->modify('-7 day');           // to get DateStart set at 7 days from now, this for check status of transactions from OTC
    echo $date->format('Y-m-d\TH:i:s'); // to get DateTime in format requested by Dragonpay 2014-09-03T00:00:00
}
ob_start();
DateStart();
$DateStart = ob_get_clean();

// For test purpose
//echo $DateStart;  // to check that the DateTime match to Manila Time









// Create DateEnd variable
function DateEnd() {
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    echo $date->format('Y-m-d\TH:i:s');  // to get DateTime in format requested by Dragonpay 2014-09-03T00:00:00
   // echo $date->format('Y-m-d');      //  this can work also with Dragonpay, but it's less precise
}
ob_start();
DateEnd();
$DateEnd = ob_get_clean();

// For test purpose
//echo $DateEnd;  // to check that the DateTime match to Manila Time












// Create the data that will serve to send the request to Dragonpay
 //file content, taken from here: https://test.dragonpay.ph/DragonpayWebService/MerchantService.asmx?op=GetMerchantTxns 
   //  SOAP 1.2 version & starting from first line with <?xml  and where for escaping this character "  ,    " is remplaced by \"  
$content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap12:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap12=\"http://www.w3.org/2003/05/soap-envelope\">
  <soap12:Body>
    <GetMerchantTxns xmlns=\"http://api.dragonpay.ph/\">
      <merchantId>".$ws_merchantId."</merchantId>
      <password>".$ws_password."</password>
      <dStart>".$DateStart."</dStart>
      <dEnd>".$DateEnd."</dEnd>
    </GetMerchantTxns>
  </soap12:Body>
</soap12:Envelope>";
 




   
    // update the data
    $options = get_option('woocommerce_dragonpay_settings');
    // update it
    $options['ws_xml_request_GetMerchantTxns'] = $content; // only update this array
    // store updated data
    update_option('woocommerce_dragonpay_settings',$options);














 echo PHP_EOL . 'CRON: Prepare request to Dragonpay: last 7 days transactions' . PHP_EOL . PHP_EOL . PHP_EOL;








                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {
 
                              $new_instance->log->add( 'dragonpay', 'CRON: Prepare request to Dragonpay: last 7 days transactions' ); 
                                     
                      }







 $file_xml_request_GetMerchantTxns = get_option('woocommerce_dragonpay_settings')['ws_xml_request_GetMerchantTxns'];










// DRAGONPAY TEST ACCOUNT CASE
if ( 'yes' == get_option('woocommerce_dragonpay_settings')['test_mode']) {
	 ## We're in a testing environment.




                                          echo "CRON: Dragonpay TEST ACCOUNT used" . PHP_EOL . PHP_EOL;



                                       if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                         $new_instance->log->add('dragonpay', 'CRON: Dragonpay TEST ACCOUNT used');                                      

                                      }







// test account URL
$url = 'https://test.dragonpay.ph/DragonpayWebService/MerchantService.asmx';   // httpS needed if not API do not answer                         



$response = wp_remote_post( 
    $url, 
    array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'headers' => array(
            'Content-Type' => 'application/soap+xml'
        ),
        'body' => $file_xml_request_GetMerchantTxns,
        'sslverify' => false
    )
);






if ( is_wp_error( $response ) ) {

   $error_message = $response->get_error_message();
   echo "CRON: Something went wrong: $error_message";


} else {


 //for test
  /*
   echo 'Response:<pre>';
   print_r( $response );
   echo '</pre>';
  */

}











                         // store the answer
                         $options = get_option('woocommerce_dragonpay_settings');
                         // update it
                         
                           // both work
                           //$options['ws_xml_answer_GetMerchantTxns'] = (array) $response[ 'body' ];
                           $options['ws_xml_answer_GetMerchantTxns'] = $response['body'];
                                                    
     
                         // store updated data     //N.B.:  if there is php error bellow, no update will be done
                         update_option('woocommerce_dragonpay_settings',$options);




// work fine
// wp_remote_retrieve_response_code( $response );
// wp_remote_retrieve_response_message( $response);


                        $content_length = $response['headers']['content-length'];



                        


                        
                         echo "CRON: Answer - content-length: ". $content_length . PHP_EOL . PHP_EOL;





                                      if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                             $new_instance->log->add( 'dragonpay', 'CRON: Answer - content-length: ' . $content_length);                                          
                                         
                                      }






          

         // if http answer is not ok    or body-content lenght is like no answer content     
         if (wp_remote_retrieve_response_message($response) != 'OK' OR $content_length < 314 ) { 


               $HTTP_status_code = wp_remote_retrieve_response_code($response);


               echo "CRON: ERROR at connecting to Dragonpay Account - HTTP Status Code: " .$HTTP_status_code. PHP_EOL . PHP_EOL;

               echo "EXITING CRON" . PHP_EOL . PHP_EOL;






                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                             $new_instance->log->add( 'dragonpay', 'CRON: ERROR at connecting to Dragonpay Account - HTTP Status Code: '.$HTTP_status_code );                      
                                             
                             $new_instance->log->add( 'dragonpay', 'CRON: *** IMPORTANT *** you should double check Dragonpay Merchant ID and Dragonpay API password you filled at: WooCommerce -> Settings -> Checkout -> Dragonpay.ph Standard' );

                             $new_instance->log->add( 'dragonpay', 'EXITING CRON: Synchronization' ); 
                                  
                      }



                  exit();   // quitt the script

               }











                       
        // LIVE DRAGONPAY ACCOUNT CASE
	}else{








                         echo "CRON: Dragonpay LIVE ACCOUNT used" . PHP_EOL . PHP_EOL;



                                      if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                           $new_instance->log->add( 'dragonpay', 'CRON: Dragonpay LIVE ACCOUNT used' );                                   
                                        
                                      }









	


// live account URL
$url = 'https://gw.dragonpay.ph/DragonPayWebService/MerchantService.asmx';



$response = wp_remote_post( 
    $url, 
    array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'headers' => array(
            'Content-Type' => 'application/soap+xml'
        ),
        'body' => $file_xml_request_GetMerchantTxns,
        'sslverify' => true
    )
);






if ( is_wp_error( $response ) ) {
   $error_message = $response->get_error_message();
   echo "CRON: Something went wrong: $error_message";
} else {
   echo 'Response:<pre>';
   print_r( $response );
   echo '</pre>';
}

                      


                          // store the answer
                         $options = get_option('woocommerce_dragonpay_settings');
                         // update it
                         $options['ws_xml_answer_GetMerchantTxns'] = $response['body'];
                         // store updated data       //N.B.:  if there is php error bellow, no update will be done
                         update_option('woocommerce_dragonpay_settings',$options);






                          $content_length = $response['headers']['content-length'];




                          echo "CRON: Answer - content-length: ". $content_length . PHP_EOL . PHP_EOL;





                                      if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                             $new_instance->log->add( 'dragonpay', 'CRON: Answer - content-length: ' . $content_length);                                          
                                         
                                      }








                 
         // if http answer is not ok    or body-content lenght is like no answer content     
         if (wp_remote_retrieve_response_message($response) != 'OK' OR $content_length < 314 ) { 


               $HTTP_status_code = wp_remote_retrieve_response_code($response);


               echo "CRON: ERROR at connecting to Dragonpay Account - HTTP Status Code: " .$HTTP_status_code. PHP_EOL . PHP_EOL;

               echo "EXITING CRON" . PHP_EOL . PHP_EOL;






                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                         $new_instance->log->add( 'dragonpay', 'CRON: ERROR at connecting to Dragonpay Account - HTTP Status Code: '.$HTTP_status_code );                      
                         
                         $new_instance->log->add( 'dragonpay', 'CRON: *** IMPORTANT *** you should double check Dragonpay Merchant ID and Dragonpay API password you filled at: WooCommerce -> Settings -> Checkout -> Dragonpay.ph Standard' );

                         $new_instance->log->add( 'dragonpay', 'EXITING CRON: Synchronization' ); 

                     } 



                     exit();   // quitt the script

              }


               
   	}












 










 echo PHP_EOL . 'CRON: Got transactions data from Dragonpay' . PHP_EOL . PHP_EOL;









                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                          $new_instance->log->add( 'dragonpay', 'CRON: Got transactions data from Dragonpay' );     
                                 
                      }













// retrieve the value from he database at options table
$file_xml_answer_GetMerchantTxns = get_option('woocommerce_dragonpay_settings')['ws_xml_answer_GetMerchantTxns'];



// clean the Dragonpay answer
  // Remove all ";" character, as it will be used as csv separator later, and could maybe be present in the "description" field 
$input_string = $file_xml_answer_GetMerchantTxns;
$output_string = str_replace(';', '', $input_string);


//update
$options = get_option('woocommerce_dragonpay_settings');
// update it
$options['ws_xml_answer_GetMerchantTxns'] = $output_string;
// store updated data  
update_option('woocommerce_dragonpay_settings',$options);









$file_xml_answer_GetMerchantTxns = get_option('woocommerce_dragonpay_settings')['ws_xml_answer_GetMerchantTxns'];








// convert xml to csv for usability of data

   // for XmlToCsv convertion
   require_once(dirname(__FILE__).'/class2.php'); 

   $x = new XmlToCsv();

   // in the XML answer from Dragonpay, the transactions are actually on the 5th level
   $x->item('/*/*/*/*/*'); 

   $x->delimiter = ";";

   $x->output = "string"; 

   $x->xml = $file_xml_answer_GetMerchantTxns;

   // xml to csv convertion
   $csvString = $x->autoConvert();

   // store the csv content answer from Dragonpay
   $options = get_option('woocommerce_dragonpay_settings');
   // update it
   $options['ws_csv_answer_GetMerchantTxns'] = $csvString; // only update this array
   // store updated data
   update_option('woocommerce_dragonpay_settings',$options);







 






//////////////








    



   //Process with the CSV file content
   $csvData = get_option('woocommerce_dragonpay_settings')['ws_csv_answer_GetMerchantTxns'];
  
    // Ref:   http://www.neidl.net/technik/php-doku/function.str-getcsv.html

    $delimiter = ';';
    $enclosure = '"';
    $escape = '\\';
    $terminator = "\n";
    $r = array();

    $rows = explode($terminator,trim($csvData));
    $names = array_shift($rows);
    $names = str_getcsv($names,$delimiter,$enclosure,$escape);
    $nc = count($names);





    foreach ($rows as $row) {




        if (trim($row)) {



            $values = str_getcsv($row,$delimiter,$enclosure,$escape);
            if (!$values) $values = array_fill(0,$nc,null);
            $r[$row] = array_combine($names,$values);

        



          //for test
          
          /*
          echo 'Response:<pre>';
          print_r( $r );
          echo '</pre>';
          */

         // echo "merchantTxnId: ".$r[$row]['merchantTxnId']. " / ";


   


    
	

          

            $dragonpay_ws_refNo =  $r[$row]['refNo'];
            $dragonpay_ws_refDate = $r[$row]['refDate'];
            $dragonpay_ws_merchantId = $r[$row]['merchantId'];
            $dragonpay_ws_merchantTxnId = $r[$row]['merchantTxnId'];
            $dragonpay_ws_amount = $r[$row]['amount'];
            $dragonpay_ws_currency = $r[$row]['currency'];
            $dragonpay_ws_description = $r[$row]['description'];
            $dragonpay_ws_email = $r[$row]['email'];
            $dragonpay_ws_status = $r[$row]['status'];
            $dragonpay_ws_procId = $r[$row]['procId'];
            $dragonpay_ws_procMsg = $r[$row]['procMsg'];
            $dragonpay_ws_billerId = $r[$row]['billerId'];
            $dragonpay_ws_settleDate  = $r[$row]['settleDate'];




            
            
            



           // Protect database imput
            $dragonpay_ws_refNo =  filter_var( $dragonpay_ws_refNo, FILTER_SANITIZE_STRING);
            $dragonpay_ws_refDate = filter_var( $dragonpay_ws_refDate, FILTER_SANITIZE_STRING);
            $dragonpay_ws_merchantId = filter_var( $dragonpay_ws_merchantId, FILTER_SANITIZE_STRING);
            $dragonpay_ws_merchantTxnId = filter_var( $dragonpay_ws_merchantTxnId, FILTER_SANITIZE_STRING);
            $dragonpay_ws_amount = filter_var( $dragonpay_ws_amount, FILTER_SANITIZE_STRING);
            $dragonpay_ws_currency = filter_var( $dragonpay_ws_currency, FILTER_SANITIZE_STRING);
            $dragonpay_ws_description = filter_var( $dragonpay_ws_description, FILTER_SANITIZE_STRING);
            $dragonpay_ws_email = filter_var( $dragonpay_ws_email, FILTER_SANITIZE_EMAIL);
            $dragonpay_ws_status = filter_var( $dragonpay_ws_status, FILTER_SANITIZE_STRING);
            $dragonpay_ws_procId = filter_var( $dragonpay_ws_procId, FILTER_SANITIZE_STRING);
            $dragonpay_ws_procMsg = filter_var( $dragonpay_ws_procMsg, FILTER_SANITIZE_STRING);
            $dragonpay_ws_billerId = filter_var( $dragonpay_ws_billerId, FILTER_SANITIZE_STRING);
            $dragonpay_ws_settleDate  = filter_var( $dragonpay_ws_settleDate, FILTER_SANITIZE_STRING);


















global $woocommerce;









           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {

          
                 // for test
                 //echo "case custom_order_numbers plugin used";


                 // Retrieve real order from postmeta database table"
                 global $wpdb;

                 $wpdb->postmeta = $wpdb->base_prefix . 'postmeta';

		 $retrieved_order_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_custom_order_number' AND meta_value = '$dragonpay_ws_merchantTxnId'" );
		
		  
                 $merchantTxnId_to_use = $retrieved_order_id;

                 //for test
                // echo 'case custom order number plugin used';
                //echo $merchantTxnId_to_use;

                  

           }else{

                  // just use regular woocommerce order (real woocommerce order = txnid used with dragonpay)
                  $merchantTxnId_to_use = $dragonpay_ws_merchantTxnId;


                  // for test
                  // echo 'case no custom order number plugin used';
                  //echo $merchantTxnId_to_use;
 
           }




















  

   /// check if order status exist in woocommerce


   //////////////////////////////////////////////


   // IMPORTANT  NONE OF THIS OTHER WY WAS WORKING:

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

   //////////////////














 // START:     process for each transaction






$order = new WC_Order( $merchantTxnId_to_use);



 //  echo "hello";






             switch ( $dragonpay_ws_status ) {


                                            


                      #################### CASE:  transaction is "S" (SUCCESS) ####################
                          case 'S':
             
                        

                                   if($order->status == 'processing' OR $order->status == 'completed' OR $order->status == 'cancelled'){
                                   				         
                                         //No update needed

                                      
				                      }else{


                                         // update order status (an admin note will be also created)
                                         $order->update_status('processing'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: payment SUCCESSFUL<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to PROCESSING<br/> -> We will be shipping your order to you soon', 1); 

                                         // reduce stock
				                      $order->reduce_order_stock(); // if physical product vs downloadable product

                                         //empty cart
                                         //not needed

                                         // no redirection needed


                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                        $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - SUCCESSFUL Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                        $new_instance->log->add( 'dragonpay', 'CRON: Order updated to PROCESSING - SUCCESSFUL Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );


	                                            }

                                         
                                         // no exit needed as it's will stop other order process "for each")

                                 }



                           break;




       


 
                   #################### CASE transaction is "F" (FAILURE) ####################
                   case 'F':                    
                                  

				   if($order->status == 'failed' OR $order->status == 'cancelled'){
                                  				         
                                         //No update needed


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('failed'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction FAILED<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to FAILED<br/> -> IMPORTANT: Please go in "My Account" section and retry to pay order', 1);   

                                         // no reduce order stock needed

	                                 //empty cart
                                         //not needed

                                         // no redirection needed
                                         
                                        
                                                  if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                    $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - FAILED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                    $new_instance->log->add( 'dragonpay', 'CRON: Order updated to FAILED - FAILED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

	                                          }


                                         // no exit needed as it's will stop other order process "for each")
                                        

				    }  


                                    break;

















                   #################### Case transaction is "P" (PENDING) waiting deposit for OTC ####################
                   case 'P':                    
                                  

				   if($order->status == 'on-hold' OR $order->status == 'cancelled'){
                                  				         
                       
                                         //No update needed

                                      

				    }else{

	         
                                         // update order status (an admin note will be also created)
                                         $order->update_status('on-hold'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction PENDING<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to ON-HOLD<br/> -> IMPORTANT: Please follow deposit instructions emailed by Dragonpay', 1);   
	         
                                         // no reduce order stock needed


                                         //empty cart
                                         // not needed

                                         // Do the redirection
                                         // not needed
                                         

                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                          $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - PENDING Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                          $new_instance->log->add( 'dragonpay', 'CRON: Order updated to ON-HOLD - PENDING Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );


	                                            }


                                         // no exit needed as it's will stop other order process "for each")

                                         
				    }  


                                    break;























                   #################### Case transaction is "U" (UNKNOWN  STATUS) ####################
                   case 'U':                   
                                  



                                  // Nothing to do:




                                         // Dragonpay do not send notify for the "U" status, but from the cron syncronization all transactions status are collected, so just better to ignore the "U" status.





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
                                  			         
                                
                                        //No update needed
         

                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('refunded'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: payment REFUNDED<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to REFUNDED', 1);   

                                         // no reduce order stock needed


	                                 //empty cart
                                         // not needed

                                         // Do the redirection
                                         // not needed
                                         

                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                         $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - REFUNDED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                         $new_instance->log->add( 'dragonpay', 'CRON: Order updated to REFUNDED - REFUNDED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

	                                            }



                                         // no exit needed as it's will stop other order process "for each")


				    }  


                                    break;














                   #################### Case  transaction is "K" (CHARGEBACK) ####################
                   case 'K':                   
                                  
				   if($order->status == 'refunded' OR $order->status == 'cancelled'){
                                  			         
                   
                                         //No update needed 

                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('refunded'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction CHARGEBACK<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to REFUNDED', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         //not needed

                                         // Do the redirection
                                         // not needed

                                         
                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                          $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - CHARGEBACK Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                          $new_instance->log->add( 'dragonpay', 'CRON: Order updated to REFUNDED - CHARGEBACK Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

	                                            }



                                          // no exit needed as it's will stop other order process "for each")
                                        

				    }  


                                    break;














                   #################### Case transaction is "V" (VOID  STATUS) ####################
                   case 'V':                   
                                  
				   if($order->status == 'cancelled'){
                                  			         
                                       
                                        //No update needed  


                                      
				    }else{
                                       

                                         // update order status (an admin note will be also created)
                                         $order->update_status('cancelled'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction VOID<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to CANCELLED', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         // not needed

                                         // Do the redirection
                                         //not needed


                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                           $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - VOID Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                           $new_instance->log->add( 'dragonpay', 'CRON: Order updated to CANCELLED - VOID Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

	                                            }


                                         // no exit needed as it's will stop other order process "for each")


				    }  


                                    break;
















                   #################### Case  transaction is "A" (AUTHORIZED) ####################
                   case 'A':                   
                                  
				   if($order->status == 'on-hold' OR $order->status == 'cancelled'){
                                  			         
                                         
                                        //No update needed


                                      
				    }else{

                                       
                                         // update order status (an admin note will be also created)
                                         $order->update_status('on-hold'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> Dragonpay: transaction AUTHORIZED<br/> -> Dragonpay transaction #'.$dragonpay_ws_refNo.'<br/> -> Order status updated to ON-HOLD<br/> -> We are waiting to receive fund', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         //not needed

                                         // Do the redirection
                                         //not needed


                                                    if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {

                                                             $new_instance->log->add( 'dragonpay', 'CRON: Find one new Transaction status - AUTHORIZED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

                                                             $new_instance->log->add( 'dragonpay', 'CRON: Order updated to ON-HOLD - AUTHORIZED Transaction #'.$dragonpay_ws_refNo.' - For Order #' . $order->get_order_number() );

	                                            }


                                         // no exit needed as it's will stop other order process "for each")


				    }  


                                    break;



















                   #################### Case  transaction is  NO STATUS CODE GIVEN IN BACK ####################
                   default :                                                    
 
                                    // no redirection needed


                                    break;


















        }   //END:     Switch           

		




// END:       process for each transaction
     









    }     //  END:       only continue with existing order in woocommerce:  if(strlen($post_status) > 2 )








         } // END:     foreach ($rows as $row)






   }  // END:    if (trim($row))





///////////////////////////










 echo PHP_EOL . 'CRON: Synchronization DONE' . PHP_EOL . PHP_EOL . PHP_EOL;


 echo PHP_EOL . 'END CRON' . PHP_EOL . PHP_EOL . PHP_EOL;







 // store last time ran for this cron
 $options = get_option('woocommerce_dragonpay_settings');
 // update it
 $options['last_ran_cron_synchronization'] = date('Y-m-d\TH:i:s');
 // store updated data     
 update_option('woocommerce_dragonpay_settings',$options);










                     if ( 'yes' == get_option('woocommerce_dragonpay_settings')['debug']) {
                            
                              $new_instance->log->add( 'dragonpay', 'CRON: Synchronization DONE');

                              $new_instance->log->add( 'dragonpay', 'END CRON: Synchronization');
 
                        }
















/////////////////////////////










      }; // END   function synchronization          // Disable here for test ECHO








?>
