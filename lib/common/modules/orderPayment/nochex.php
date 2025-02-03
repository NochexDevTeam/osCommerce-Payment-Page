<?php

/**
*  @author Nochex
*  @copyright 2007-2019 Nochex
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  Plugin Name: Nochex Payment Gateway for Oscommerce v4
*  Description: Accept Nochex Payments, orders are updated using APC or Callback.
*  Version: 3.0
*  License: GPL2
*/

/**
 * namespace
 */
namespace common\modules\orderPayment;

/**
 * used classes
 */
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

use common\helpers\OrderPayment as OrderPaymentHelper;
use common\helpers\Html;


/**
 * class declaration
 */
class nochex extends ModulePayment {

    /**
     * variables
     */
    var $code, $title, $description, $enabled, $merchantid;

    /**
     * default values for translation
     */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_NOCHEX_TEXT_TITLE' => 'Nochex',
        'MODULE_PAYMENT_NOCHEX_TEXT_DESCRIPTION' => 'Pay by credit / debit card',
        'MODULE_PAYMENT_NOCHEX_ERROR' => 'There has been an error processing your payment',
    ];

    /**
     * class constructor
     */
    function __construct() {
        parent::__construct();

        $this->code = 'nochex';
        $this->title = MODULE_PAYMENT_NOCHEX_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_NOCHEX_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_NOCHEX_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_PAYMENT_NOCHEX_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_NOCHEX_STATUS == 'True') ? true : false);
        $this->online = true;
		
		$this->merchantid = MODULE_PAYMENT_NOCHEX_MERCHANTID;
		
		$this->api_url = "https://secure.nochex.com";
		
        if ((int) MODULE_PAYMENT_NOCHEX_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_NOCHEX_ORDER_STATUS_ID;
        }

        $this->update_status();
    }

    function update_status() {

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOCHEX_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOCHEX_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function selection() {
        return array('id' => $this->code,
            'module' => $this->title);
    }

    function process_button() {
		return false;
    }

    function before_process() {
            $this->_save_order();
            tep_redirect($this->_start_transaction());
    }

	function _start_transaction() {

            /** @var \common\classes\Currencies $currencies */
            $currencies = \Yii::$container->get('currencies');
            /** @var OrderAbstract $order */
            $order = $this->manager->getOrderInstance(); 

            $amount = number_format(($order->info['total']) * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);	
			$postage = number_format($order->info['shipping_cost'] * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);
		
			$billing_phoneNumber= $order->customer['telephone'];
			$billing_fullname = $order->billing['firstname'] . ' ' . $order->billing['lastname'];			
			$billing_address = array();

			if(strlen($order->billing['street_address'])>0) $billing_address[] = $order->billing['street_address'];
			if(strlen($order->billing['suburb'])>0) $billing_address[] = $order->billing['suburb'];
			if(strlen($order->billing['city'])>0) $billing_city = $order->billing['city'];
			
			$billing_country = $order->billing["country"]["iso_code_2"];
			$billing_postcode = $order->billing['postcode'];
			
			 $delivery_fullname = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
			$delivery_address = array();
			if(strlen($order->delivery['street_address'])>0) $delivery_address[] = $order->delivery['street_address'];
			if(strlen($order->delivery['suburb'])>0) $delivery_address[] = $order->delivery['suburb'];		
			if(strlen($order->delivery['city'])>0) $delivery_city = $order->delivery['city'];
			$delivery_country = $order->delivery["country"]["iso_code_2"];
			$delivery_postcode = $order->delivery['postcode'];
			
			foreach ($order->products as $value) { 
				
				$price = number_format(($value['final_price']) * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);
				$description .= "Product Name: ".$value['name'].", Description: ".$value['model'].", Quantity: ".$value['qty'].", Price: ".$price ." ";
	
			}
			if (isset($postage) > 0){
				$description .= ", postage: " . $postage;
			}
			
			echo '<p>Please enable JavaScript in your browser, and press the button below to continue.</p>
				<form action="https://secure.nochex.com/default.aspx" method="post" id="nochex_payment_form" name="nochex_payment_form">
				<input type="hidden" name="merchant_id" value="'.$this->merchantid.'" />				
				<input type="hidden" name="amount" value="'.$amount.'" />						
				<input type="hidden" name="description" value="'.$description .'" />				
				<input type="hidden" name="order_id" value="'.$this->order_id.'" />							
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
				<input type="hidden" name="optional_2" value="Enabled" />
				<input type="hidden" name="success_url" value="'.tep_href_link('callback/webhooks.payment.' . $this->code, 'action=success&orders_id=' . $this->order_id, 'SSL').'" />
				<input type="hidden" name="callback_url" value="'.tep_href_link('callback/webhooks.payment.' . $this->code, 'action=callback&orders_id=' . $this->order_id, 'SSL').'" />	
				<input type="submit" class="button-alt" id="submit_nochex_payment_form" value="Pay via Nochex" /> 				
				</form>
				<script type="text/javascript">
				window.onload=function(){			
				document.nochex_payment_form.submit();
				}
				</script>'; 
			
		}
		
    public function call_webhooks() {
	
		$postvars = http_build_query($_POST);
		ini_set("SMTP","mail.nochex.com" ); 
		$header = "From: apc@nochex.com";
				
        $action = \Yii::$app->request->get('action', '');
        $orders_id = \Yii::$app->request->get('orders_id', 0);  
		
        /** @var \common\classes\Currencies $currencies */
        $currencies = \Yii::$container->get('currencies');		
		
        $order = $this->manager->getOrderInstanceWithId('\common\classes\Order', $session->metadata->order_id);
		
		
		if ($action == 'success') {
		
                $this->manager->clearAfterProcess();
                tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'orders_id=' . $orders_id, 'SSL'));
				
        } else if ($action == 'callback') {							
			
			$orderDets = $this->manager->getOrderInstanceWithId('\common\classes\Order', $_POST["order_id"]);
			
			$amount = number_format(($orderDets->info['total_inc_tax']) * $currencies->currencies['GBP']['value'], $currencies->currencies['GBP']['decimal_places']);	
		
				
			if ( !empty($_POST['optional_2']) and $_POST['optional_2'] == "Enabled") {
			
				if ($amount <> $_POST['gross_amount']) {
								
						\common\helpers\Order::setStatus($_POST['order_id'], (int)5, [
                            'comments' => "Amount Mismatch! Paid amount " . $_POST['gross_amount'] . "is not the same as the Ordered amount " . $amount,
                            'customer_notified' => 0,
                        ]);	
				
				} else {

				$url = "https://secure.nochex.com/callback/callback.aspx";
				$ch = curl_init ();
				curl_setopt ($ch, CURLOPT_URL, $url);
				curl_setopt ($ch, CURLOPT_POST, true);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $postvars);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$response = curl_exec($ch);
				curl_close($ch);

				if($_POST["transaction_status"] == "100"){
					$testStatus = "Test";
				}else{
					$testStatus = "Live";
				}
					
				if (!strstr($response, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed
					$Msg = "Callback: " . $response . "\r\n";			
					$Msg .= "Transaction Status: " . $testStatus . "\r\n";			
					$Msg .= "Transaction ID: ".$_POST["transaction_id"] . "\r\n";
					$Msg .= "Payment Received From: ".$_POST["email_address"] . "\r\n";			
					$Msg .= "Total Paid: ".$_POST["gross_amount"] . "\r\n";	
					
					$status = "";
					$order_status = $this->order_status;
                        \common\helpers\Order::setStatus($_POST['order_id'], (int)$order_status, [
                            'comments' => $Msg,
                            'customer_notified' => 0,
                        ]);	
					
				} 
				else { 
					
					$Msg = "Callback: " . $response . "\r\n";			
					$Msg .= "Transaction Status: " . $testStatus . "\r\n";			
					$Msg .= "Transaction ID: ".$_POST["transaction_id"] . "\r\n";
					$Msg .= "Payment Received From: ".$_POST["email_address"] . "\r\n";			
					$Msg .= "Total Paid: ".$_POST["gross_amount"] . "\r\n";	
					
					$order_status = $this->order_status;
                        \common\helpers\Order::setStatus($_POST['order_id'], (int)$order_status, [
                            'comments' => $Msg,
                            'customer_notified' => 1,
                        ]);	 
				}
				}
		
				
			} else {
				
				if ($amount <> $_POST['amount']) {
				
						\common\helpers\Order::setStatus($_POST['order_id'], (int)5, [
                            'comments' => "Amount Mismatch! Paid amount " . $_POST['amount'] . "is not the same as the Ordered amount " . $amount,
                            'customer_notified' => 0,
                        ]);	
				
				
				} else {

				// Set parameters for the email
				$url = "https://secure.nochex.com/apc/apc.aspx";

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
				$debug = "\r\n"; 
				foreach($_POST as $Index => $Value) 
				$debug .= "$Index -> $Value\r\n"; 
				$debug .= "\r\nRESPONSE:\r\n$output";
				 
				//If statement
				if (!strstr($output, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed
					$msg = "APC was not AUTHORISED.\r\n\r\n$debug";  // displays debug message
					$status = "";
					$order_status = $this->order_status;
                        \common\helpers\Order::setStatus($_POST['order_id'], (int)$order_status, [
                            'comments' => $msg,
                            'customer_notified' => 0,
                        ]);		
					
				} 
				else { 
					$msg = "APC was AUTHORISED.\r\n\r\n$debug"; // if AUTHORISED was found in the response then it was successful
					// whatever else you want to do 
					$order_status = $this->order_status;
                        \common\helpers\Order::setStatus($_POST['order_id'], (int)$order_status, [
                            'comments' => $msg,
                            'customer_notified' => 1,
                        ]);	 
					
				}
				
				}
			}
			}
		}
		
    function _save_order() {
        global $languages_id;

        if (!empty($this->order_id) && $this->order_id > 0) {
            return;
        }

        $order = $this->manager->getOrderInstance();
        $order->save_order();
        $order->save_details();
        $order->save_products(false);
        $stock_updated = false;
        $this->order_id = $order->order_id;
    }

    function after_process() {
		return false;
	}
		 

    function formatCurrencyRaw($total, $currency_code = null, $currency_value = null) {

        if (!isset($currency_code)) {
            $currency_code = DEFAULT_CURRENCY;
        }

        if (!isset($currency_value) || !is_numeric($currency_value)) {
            $currencies = \Yii::$container->get('currencies');
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(self::round($total * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    function isOnline() {
        return true;
    }

    public function configure_keys() {
        return array(
            'MODULE_PAYMENT_NOCHEX_STATUS' => array(
                'title' => 'Enable Nochex',
                'value' => 'True',
                'description' => 'Do you want to accept Credit / Debit card payments?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_NOCHEX_MERCHANTID' => array(
                'title' => 'Merchant ID / Email Address',
                'value' => '',
                'description' => 'Nochex registered Merchant ID / Email Address.',
                'sort_order' => '2',
            ),
            'MODULE_PAYMENT_NOCHEX_TESTMODE' => array(
                'title' => 'Enable Test Mode',
                'value' => 'False',
                'description' => 'Enable this feature to allow test transactions. Note: Ensure this option is disabled to accept live payments',
                'sort_order' => '3',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_NOCHEX_ZONE' => array(
                'title' => 'Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '4',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_NOCHEX_ORDER_STATUS_ID' => array(
                'title' => 'Set Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value',
                'sort_order' => '5',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_NOCHEX_SORT_ORDER' => array(
                'title' => 'Sort order of  display.',
                'value' => '0',
                'description' => 'Sort order of Custom payment display. Lowest is displayed first.',
                'sort_order' => '6',
            ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_NOCHEX_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_NOCHEX_SORT_ORDER');
    }

}
