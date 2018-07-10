<?php
/*
	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Nochex APC Plugin v0.1.1
	Copyright © Entrepreneuria Limited 2006
	Released under the GNU General Public License
*/
	chdir('../../../../');
	require('includes/application_top.php');

// if the customer is not logged on, redirect them to the login page
	if(!tep_session_is_registered('customer_id')){
		$navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
		tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
	}

	if(!tep_session_is_registered('sendto')){
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
	}

	if(tep_not_null(MODULE_PAYMENT_INSTALLED)&&!tep_session_is_registered('payment')){
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
	}

// avoid hack attempts during the checkout procedure by checking the internal cartID
	if(isset($cart->cartID)&&tep_session_is_registered('cartID')){
		if($cart->cartID!=$cartID){
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
		}
	}

	include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

// load selected payment module
	require(DIR_WS_CLASSES . 'payment.php');
	$payment_modules = new payment($payment);

// load the selected shipping module
	require(DIR_WS_CLASSES . 'shipping.php');
	$shipping_modules = new shipping($shipping);

	require(DIR_WS_CLASSES . 'order.php');
	$order = new order;
	
// load the before_process function from the payment modules
	$payment_modules->before_process();

	require(DIR_WS_CLASSES . 'order_total.php');
	$order_total_modules = new order_total;

	$order_totals = $order_total_modules->process();

	$sql_data_array = array('customers_id' => $customer_id,
													'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
													'customers_company' => $order->customer['company'],
													'customers_street_address' => $order->customer['street_address'],
													'customers_suburb' => $order->customer['suburb'],
													'customers_city' => $order->customer['city'],
													'customers_postcode' => $order->customer['postcode'], 
													'customers_state' => $order->customer['state'], 
													'customers_country' => $order->customer['country']['title'], 
													'customers_telephone' => $order->customer['telephone'], 
													'customers_email_address' => $order->customer['email_address'],
													'customers_address_format_id' => $order->customer['format_id'], 
													'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
													'delivery_company' => $order->delivery['company'],
													'delivery_street_address' => $order->delivery['street_address'], 
													'delivery_suburb' => $order->delivery['suburb'], 
													'delivery_city' => $order->delivery['city'], 
													'delivery_postcode' => $order->delivery['postcode'], 
													'delivery_state' => $order->delivery['state'], 
													'delivery_country' => $order->delivery['country']['title'], 
													'delivery_address_format_id' => $order->delivery['format_id'], 
													'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
													'billing_company' => $order->billing['company'],
													'billing_street_address' => $order->billing['street_address'], 
													'billing_suburb' => $order->billing['suburb'], 
													'billing_city' => $order->billing['city'], 
													'billing_postcode' => $order->billing['postcode'], 
													'billing_state' => $order->billing['state'], 
													'billing_country' => $order->billing['country']['title'], 
													'billing_address_format_id' => $order->billing['format_id'], 
													'payment_method' => $order->info['payment_method'], 
													'cc_type' => $order->info['cc_type'], 
													'cc_owner' => $order->info['cc_owner'], 
													'cc_number' => $order->info['cc_number'], 
													'cc_expires' => $order->info['cc_expires'], 
													'date_purchased' => 'now()', 
													'orders_status' => 50000, 
													'currency' => $order->info['currency'], 
													'currency_value' => $order->info['currency_value']);
	tep_db_perform(TABLE_ORDERS, $sql_data_array);
	$insert_id = tep_db_insert_id();
	for($i=0, $n=sizeof($order_totals); $i<$n; $i++){
		$sql_data_array = array('orders_id' => $insert_id,
														'title' => $order_totals[$i]['title'],
														'text' => $order_totals[$i]['text'],
														'value' => $order_totals[$i]['value'], 
														'class' => $order_totals[$i]['code'], 
														'sort_order' => $order_totals[$i]['sort_order']);
		tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
	}

	$customer_notification = (SEND_EMAILS=='true') ? '1' : '0';
	$sql_data_array = array('orders_id' => $insert_id, 
													'orders_status_id' => '50000', 
													'date_added' => 'now()', 
													'customer_notified' => $customer_notification,
													'comments' => $order->info['comments']);
	tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// start of the xml tags to collect the product details. 
$xml_collect = "<items>";
$prodDetails = "";
	for($i=0, $n=sizeof($order->products); $i<$n; $i++){
// Stock Update - Joao Correia
		if(STOCK_LIMITED=='true'&&MODULE_PAYMENT_NOCHEX_APC_UPDATE_STOCK_BEFORE_PAYMENT=='True'){
			if(DOWNLOAD_ENABLED=='true'){
				$stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename 
														FROM " . TABLE_PRODUCTS . " p
														LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
														 ON p.products_id=pa.products_id
														LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
														 ON pa.products_attributes_id=pad.products_attributes_id
														WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
				$products_attributes = $order->products[$i]['attributes'];
				if(is_array($products_attributes)){
					$stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
				}
				$stock_query = tep_db_query($stock_query_raw);
			}else{
				$stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
			}
			if(tep_db_num_rows($stock_query)>0){
				$stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
				if((DOWNLOAD_ENABLED!='true')||(!$stock_values['products_attributes_filename'])){
					$stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
				}else{
					$stock_left = $stock_values['products_quantity'];
				}
				tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
				if($stock_left<1&&STOCK_ALLOW_CHECKOUT=='false'){
					tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
				}
			}
		}

// Update products_ordered (for bestsellers list)	
		tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

		$sql_data_array = array('orders_id' => $insert_id, 
														'products_id' => tep_get_prid($order->products[$i]['id']), 
														'products_model' => $order->products[$i]['model'], 
														'products_name' => $order->products[$i]['name'], 
														'products_price' => $order->products[$i]['price'], 
														'final_price' => $order->products[$i]['final_price'], 
														'products_tax' => $order->products[$i]['tax'], 
														'products_quantity' => $order->products[$i]['qty']);
		tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
		$order_products_id = tep_db_insert_id();

//------insert customer choosen option to order--------
		$attributes_exist = '0';
		$products_ordered_attributes = '';
		if(isset($order->products[$i]['attributes'])){
			$attributes_exist = '1';
			for($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++){
				if(DOWNLOAD_ENABLED=='true'){
					$attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
															 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
															 left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
																on pa.products_attributes_id=pad.products_attributes_id
															 where pa.products_id = '" . $order->products[$i]['id'] . "' 
																and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
																and pa.options_id = popt.products_options_id 
																and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
																and pa.options_values_id = poval.products_options_values_id 
																and popt.language_id = '" . $languages_id . "' 
																and poval.language_id = '" . $languages_id . "'";
					$attributes = tep_db_query($attributes_query);
				}else{
					$attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
				}
				$attributes_values = tep_db_fetch_array($attributes);

				$sql_data_array = array('orders_id' => $insert_id, 
																'orders_products_id' => $order_products_id, 
																'products_options' => $attributes_values['products_options_name'],
																'products_options_values' => $attributes_values['products_options_values_name'], 
																'options_values_price' => $attributes_values['options_values_price'], 
																'price_prefix' => $attributes_values['price_prefix']);
				tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
	
				if((DOWNLOAD_ENABLED=='true')&&isset($attributes_values['products_attributes_filename'])&&tep_not_null($attributes_values['products_attributes_filename'])){
					$sql_data_array = array('orders_id' => $insert_id, 
																	'orders_products_id' => $order_products_id, 
																	'orders_products_filename' => $attributes_values['products_attributes_filename'], 
																	'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
																	'download_count' => $attributes_values['products_attributes_maxcount']);
					tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
				}
				$products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
			}
		}
		//------insert customer choosen option eof ---- //
		$total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
		$total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
		$total_cost += $total_products_price;
		// Product details.
		$products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
		
		$prodDetails .= "Product Name: ".$order->products[$i]['name'].", Description: ".$order->products[$i]['model'].", Quantity: ".$order->products[$i]['qty'].", Price: ".$currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']).".";
	
	
		// The xml item tags, which loop through each product in an order and attach the attributes into the related fields
		$xml_collect .= "<item><id></id><name>".$order->products[$i]['name']."</name><description>".$order->products[$i]['model']."</description><quantity>".$order->products[$i]['qty']."</quantity><price>".$currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty'])."</price></item>";
	}
		// Out xml tag which closes the xml.
		$xml_collect .= "</items>";
	//'MODULE_PAYMENT_NOCHEX_APC_XML',
	//'',	
	
	if(MODULE_PAYMENT_NOCHEX_APC_XML == 'On'){
	

	$description = 'Order made from OscommerceTest';
	}else{
		$xml_collect = '';
	$description = $prodDetails;
	
	}
	
	// Attaches the products and xml details to a variable.
		$details = "Product details before loop:". $products_ordered ." Product details after loop and put in xml: ". $xml_collect;
	// Writes the details to the nochex_debug.txt file
		writeDebug($details);
	// load the after_process function from the payment modules
		$payment_modules->after_process();

	// unregister session variables used during checkout
	tep_session_unregister('sendto');
	tep_session_unregister('billto');
	tep_session_unregister('shipping');
	tep_session_unregister('payment');
	tep_session_unregister('comments'); 

	$nochex_currency = $order->info['currency']; 
	$nochex_order_amount = $order->info['total'];

	$nochex_order_amount = number_format($nochex_order_amount * $currencies->get_value($nochex_currency), 2);
	
	
	// Show postage amount on Nochex paymeent page, if enabled in the admin section
	if(MODULE_PAYMENT_NOCHEX_APC_POSTAGE == 'On'){
	
		// Amount
		$amount = number_format(($order->info['total'] - $order->info['shipping_cost']) * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);
		// Delivery Costs
		$postage = number_format($order->info['shipping_cost'] * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);
	
	}else{
	
		$amount = number_format(($order->info['total']) * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);	
		$postage = '';
	
	}
	
			// Compile the query string to send to Nochex payments page 
			if(MODULE_PAYMENT_NOCHEX_APC_BILLING == 'On'){
				$hideBilling = 1;
			}else{
				$hideBilling = 0;
			}
			
			// Delivery Name and Address
			$delivery_fullname = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
			$delivery_address = array();
			if(strlen($order->delivery['street_address'])>0) $delivery_address[] = $order->delivery['street_address'];
			if(strlen($order->delivery['suburb'])>0) $delivery_address[] = $order->delivery['suburb'];		
			if(strlen($order->delivery['city'])>0) $delivery_city = $order->delivery['city'];
			$delivery_country = $order->delivery["country"]["iso_code_2"];
			$delivery_postcode = $order->delivery['postcode'];
			
			$merchant_id = MODULE_PAYMENT_NOCHEX_APC_EMAIL;
			
	// variables about the order are saved into a variable called $details.
	$details = "Variables being attached to the form variables: Merchant ID: " . $merchant_id . ", \n Amount: ". $amount . ", \n Postage: " . $postage . ", \n Order_ID: " . $insert_id . ", \n Xml Item Collection: " . $xml_collect . ", \n Billing Fullname: " . 
	$billing_fullname . ", \n Billing Address: " . $billing_address . ", \n Billing Postcode: " . $billing_postcode . ", \n Delivery Fullname: " . $delivery_fullname . ", \n Delivery Address: " . $delivery_address . ", \n Delivery Postcode: " . $delivery_postcode . ", \n Customer Email Address: " . $order->customer['email_address'] . ", \n Billing Phone Number: ". $billing_phoneNumber . ", \n Test Mode: " . MODULE_PAYMENT_NOCHEX_APC_TESTMODE . ".";
	//Calls a function writeDebug, which passes the $details variable to be written to nochex_debug.txt
	writeDebug($details);
	// variables about the configuration are saved into a variable
	$configurationDetails = "Nochex APC Email: ". MODULE_PAYMENT_NOCHEX_APC_EMAIL .", Test Mode: ". MODULE_PAYMENT_NOCHEX_APC_TESTMODE .", Hide Billing Details: ". MODULE_PAYMENT_NOCHEX_APC_BILLING .", Debugging Mode: ". MODULE_PAYMENT_NOCHEX_APC_DEBUGGING .". ";
	//Calls a function writeDebug, which passes the configuration details variable to be written to nochex_debug.txt
	writeDebug($configurationDetails);
	//Calls a function writeDebug, which passes the $params variable to be written to nochex_debug.txt
	writeDebug($params);
	// Parameters to be sent to Nochex.

	///*--- Function, write to a text file ---*/
	//// Function that will be called when particular information needs to be written to a nochex_debug file.
	function writeDebug($DebugData){
	// Receives and stores the Date and Time
	$debug_TimeDate = date("m/d/Y h:i:s a", time());
	// Puts together, Date and Time, as well as information in regards to information that has been received.
	$stringData = "\n\n Time and Date: " . $debug_TimeDate . "... " . $DebugData ."... ";
	// Try - Catch in case any errors occur when writing to nochex_debug file.
		try
		{
			// Variable with the name of the debug file.
			$debugging = "nochex_debug.txt";
			// variable which will open the nochex_debug file, or if it cannot open then an error message will be made.
			$f = fopen($debugging, 'a') or die("File can't open");
			// Open and write data to the nochex_debug file.
			$ret = fwrite($f, $stringData);
			// Incase there is no data being shown or written then an error will be produced.
			
		if ($ret === false)die("Fwrite failed");
			// Closes the open file.
			fclose($f)or die("File not close");	
		} 
		//If a problem or something doesn't work, then catch will produce an email which will send an error message.
		catch(Exception $e)
		{
			mail($this->email, "Debug Check Error Message", $e->getMessage());
		}
	} 

			// If Test mode is activated 
			if(MODULE_PAYMENT_NOCHEX_APC_TESTMODE=="Test"){
				$testTran = "100";
			}	else {
				$testTran = "0";
			}

	        $billing_phoneNumber= $order->customer['telephone'];
			$billing_fullname = $order->billing['firstname'] . ' ' . $order->billing['lastname'];			
			$billing_address = array();

			if(strlen($order->billing['street_address'])>0) $billing_address[] = $order->billing['street_address'];
			if(strlen($order->billing['suburb'])>0) $billing_address[] = $order->billing['suburb'];
			if(strlen($order->billing['city'])>0) $billing_city = $order->billing['city'];
			
			$billing_country = $order->billing["country"]["iso_code_2"];
			$billing_postcode = $order->billing['postcode'];	
			
			$cart->reset(true);
			
	echo '<noscript><p>Please enable JavaScript in your browser, and press the button below to continue.</p></noscript>
	<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form" name="nochex_payment_form">				
	<input type="hidden" name="merchant_id" value="'.$merchant_id.'" />				
	<input type="hidden" name="amount" value="'.$amount.'" />				
	<input type="hidden" name="Postage" value="'.$postage.'" />			
	<input type="hidden" name="xml_item_collection" value="'.$xml_collect.'" />				
	<input type="hidden" name="description" value="'.$description .'" />				
	<input type="hidden" name="order_id" value="'.$insert_id.'" />							
	<input type="hidden" name="billing_fullname" value="'.$billing_fullname.'" />				
	<input type="hidden" name="billing_address" value="'.implode("\n", $billing_address).'" />				
	<input type="hidden" name="billing_city" value="'.$billing_city.'" />				
	<input type="hidden" name="billing_postcode" value="'.$billing_postcode.'" />				
	<input type="hidden" name="delivery_fullname" value="'.$delivery_fullname.'" />				
	<input type="hidden" name="delivery_address" value="'.implode("\n", $delivery_address).'" />				
	<input type="hidden" name="delivery_city" value="'.$delivery_city.'" />				
	<input type="hidden" name="delivery_postcode" value="'.$delivery_postcode.'" />				
	<input type="hidden" name="email_address" value="'.$order->customer['email_address'].'" />				
	<input type="hidden" name="customer_phone_number" value="'.$billing_phoneNumber.'" />				
	<input type="hidden" name="success_url" value="'.tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL').'" />				
	<input type="hidden" name="hide_billing_details" value="'.$hideBilling.'" />				
	<input type="hidden" name="callback_url" value="'.tep_href_link('ext/modules/payment/nochex/nochex_apc_handler.php', '', 'SSL').'" />				
	<input type="hidden" name="cancel_url" value="'.tep_href_link('checkout_shipping.php', '', 'SSL').'" />				
	<input type="hidden" name="test_success_url" value="'.tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL').'" />				
	<input type="hidden" name="test_transaction" value="'.$testTran.'" />				
	<input type="submit" class="button-alt" id="submit_nochex_payment_form" value="Pay via Nochex" /> 				
	</form> 
	<script type="text/javascript">
	window.onload=function(){			
	document.nochex_payment_form.submit();
	}
	</script>';
		
?>
