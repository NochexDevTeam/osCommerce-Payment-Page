<?php

chdir('../../../../');
include('includes/application_top.php');
include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

require('includes/modules/payment/nochex_apc.php');

// Get the POST information from Nochex server
$postvars = http_build_query($_POST);

ini_set("SMTP","mail.nochex.com" ); 
$header = "From: apc@nochex.com";
// Set parameters for the email
$url = "https://www.nochex.com/apcnet/apc.aspx";
// Curl code to post variables back
$ch = curl_init(); // Initialise the curl tranfer
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); // Set POST fields
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // set connection time out variable - 60 seconds	
$output = curl_exec($ch); // Post back
curl_close($ch);
// Put the variables in a printable format for the email
$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
foreach($_POST as $Index => $Value) 
$debug .= "$Index -> $Value\r\n"; 
$debug .= "\r\nRESPONSE:\r\n$output";
 
//If statement
if (!strstr($output, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed
    $msg = "APC was not AUTHORISED.\r\n\r\n$debug";  // displays debug message
} 
else { 
    $msg = "APC was AUTHORISED.\r\n\r\n$debug"; // if AUTHORISED was found in the response then it was successful
}

	$sql_data_array = array(
		'nc_transaction_id' => $_POST["transaction_id"],
		'nc_to_email' => $_POST["to_email"],
		'nc_from_email' => $_POST["from_email"],
		'nc_transaction_date' => $_POST["transaction_date"],
		'nc_order_id' => $_POST["order_id"],
		'nc_amount' => $_POST["amount"],
		'nc_security_key' => $_POST["security_key"],
		'nc_status' => $_POST["status"],
		'nochex_response' => $output
	);
	tep_db_perform("nochex_apc_transactions", $sql_data_array);
	
	$customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';

	$apcResults = "APC has been " . $output . " for transaction " . $_POST["transaction_id"] . ", and this was a " . $_POST["status"] . " transaction. ";
	
	$sql_data_array = array('orders_id' => $_POST["order_id"], 
			'orders_status_id' => MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID, 
			'date_added' => 'now()', 	
			'customer_notified' => $customer_notification,
			'comments' => $apcResults);
	tep_db_perform('orders_status_history', $sql_data_array);
		
	$data = $_POST["order_id"];
	list($_POST["order_id"], $temporderdate) = split('-',$data);
	$sql_data_array = array('orders_status' => MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID);
	tep_db_perform('orders',$sql_data_array,'update','orders_id='.$_POST["order_id"]);
	
	
	
	/*tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);*/
	
	
?>