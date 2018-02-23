<?php
/*
	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Nochex APC v0.1.1
	Copyright © Nochex
	Released under the GNU General Public License
*/

	class nochex_apc {
		var $code, $title, $description, $enabled, $notify_url, $update_stock_before_payment, $allowed_currencies, $default_currency, $test_mode, $billing;
		
// class constructor
		function nochex_apc() {
			global $order;
			$this->code = 'nochex_apc';
			$this->title = MODULE_PAYMENT_NOCHEX_APC_TEXT_TITLE;
			$this->description = "";
			$this->sort_order = MODULE_PAYMENT_NOCHEX_APC_SORT_ORDER;
			$this->enabled = ((MODULE_PAYMENT_NOCHEX_APC_STATUS == 'True') ? true : false);
			$this->responder = MODULE_PAYMENT_NOCHEX_APC_RESPONDER;
			$this->logo = MODULE_PAYMENT_NOCHEX_APC_LOGO;
			$this->update_stock_before_payment = ((MODULE_PAYMENT_NOCHEX_APC_UPDATE_STOCK_BEFORE_PAYMENT == 'True') ? true : false);
			$this->allowed_currencies = MODULE_PAYMENT_NOCHEX_APC_ALLOWED_CURRENCIES;
			$this->default_currency = MODULE_PAYMENT_NOCHEX_APC_DEFAULT_CURRENCY;
			$this->test_mode = ((MODULE_PAYMENT_NOCHEX_APC_TEST_MODE == 'True') ? true : false);
			$this->billing = ((MODULE_PAYMENT_NOCHEX_APC_BILLING == 'ON') ? true : false);
			$billing = ((MODULE_PAYMENT_NOCHEX_APC_BILLING == 'ON') ? true : false);			
			$this->xmlcoll = ((MODULE_PAYMENT_NOCHEX_APC_XML == 'ON') ? true : false);
			$this->postage = ((MODULE_PAYMENT_NOCHEX_APC_POSTAGE == 'ON') ? true : false);
			$this->debugging = ((MODULE_PAYMENT_NOCHEX_APC_DEBUGGING == 'OFF') ? true : false);						
			$this->callback = ((MODULE_PAYMENT_NOCHEX_APC_CALLBACK == 'OFF') ? true : false);
			
			if ((int)MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID > 0) {
				$this->order_status = MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID;
			}

			if (is_object($order)) $this->update_status();

			$this->form_action_url = tep_href_link("checkout_nochex.php",'','SSL');
		}

// class methods
		function update_status() {
			global $order;

			if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_NOCHEX_APC_ZONE > 0) ) {
				$check_flag = false;
				$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOCHEX_APC_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
				while ($check = tep_db_fetch_array($check_query)) {
					if ($check['zone_id'] < 1) {
						$check_flag = true;
						break;
					} elseif ($check['zone_id'] == $order->billing['zone_id']) {
						$check_flag = true;
						break;
					}
				}

				if ($check_flag == false) {
					$this->enabled = false;
				}
			}
		}

		function javascript_validation() {
			return false;
		}

		function selection() {
      /*$img_visa = DIR_WS_IMAGES .'nochex/visa.gif';
      $img_mc = DIR_WS_IMAGES .'nochex/mastercard.gif';
      $img_maestro = DIR_WS_IMAGES .'nochex/maestro.gif';
      $img_electron = DIR_WS_IMAGES .'nochex/electron.gif';
      $img_switch= DIR_WS_IMAGES .'nochex/switch.gif';
      $img_solo = DIR_WS_IMAGES .'nochex/solo.gif';
      $img_nochex = DIR_WS_IMAGES .'nochex/nochex.gif';
      $nochex_cc_txt = implode("&nbsp;", array(tep_image($img_visa,' Visa ','','','align="absmiddle"'),
                              tep_image($img_mc,' MasterCard ','','','align="absmiddle"'),
                              tep_image($img_maestro,' Maestro ','','','align="absmiddle"'),
                              tep_image($img_electron,' Visa Debit/Electron ','','','align="absmiddle"'),
                              tep_image($img_switch,' Switch ','','','align="absmiddle"'),
                              tep_image($img_solo,' Solo ','','','align="absmiddle"'),
                              tep_image($img_nochex,' Nochex ','','','align="absmiddle"')
                             ));*/
		if (MODULE_PAYMENT_NOCHEX_APC_BILLING == "On"){
		$hideBillingEnabled = "<span style=\"font-weight:bold;color:red;\">Please check your billing address details match the details on your card that you are going to use.</span>";
		}
		 
		
							 
      $fields[] = array('title' => '',
                        'field' => '<div>' . $hideBillingEnabled . '</div>');
      return array('id' => $this->code,
                   'module' => $this->title,
                   'fields' => $fields);
		}

		function pre_confirmation_check() {
			return false;
		}

		function confirmation() {
			return false;
		}

		function process_button() {

		}

		function before_process() {
			return false;
		}

		function after_process() {
			return false;
		}

		function output_error() {
			return false;
		}

		function check() {
			if (!isset($this->_check)) {
				$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOCHEX_APC_STATUS'");
				$this->_check = tep_db_num_rows($check_query);
			}
			return $this->_check;
		}

		function install() {
			global $language;

			$nochex_supported_currencies = "'GBP'";

			$available_currencies_query = tep_db_query("select title,code,symbol_left,symbol_right from " . TABLE_CURRENCIES . " where code IN($nochex_supported_currencies) order by currencies_id");
			if (tep_db_num_rows($available_currencies_query)) {
				while ($available_currencies = tep_db_fetch_array($available_currencies_query)) {
					$osc_allowed_currencies .= $available_currencies[code].',';
				};
				$osc_allowed_currencies = substr($osc_allowed_currencies,0,strlen($osc_allowed_currencies)-1);
			} else {
				$osc_allowed_currencies = 'GBP';
			};
			
// New Code
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Nochex + APC Module', 'MODULE_PAYMENT_NOCHEX_APC_STATUS', 'True', 'Do you want to accept Nochex payments?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_NOCHEX_APC_EMAIL', 'you@yourbusiness.com', 'Your Nochex Merchant ID, for example mywebsite@test.com or Test_ID', '6', '4', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_NOCHEX_APC_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_NOCHEX_APC_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Checkout Mode', 'MODULE_PAYMENT_NOCHEX_APC_TESTMODE', 'Live', 'Testing Mode, Used to test that your shopping cart is working. Leave disabled for live transactions.', '6', '6', 'tep_cfg_select_option(array(\'Live\', \'Test\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID', '50001', 'Status of orders when a customer has made a payment', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Hide Billing Details', 'MODULE_PAYMENT_NOCHEX_APC_BILLING', 'OFF', 'Hide Billing Details Option, Used to hide the billing details.', '6', '6', 'tep_cfg_select_option(array(\'On\', \'Off\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Detailed Product Information', 'MODULE_PAYMENT_NOCHEX_APC_XML', 'OFF', 'Display your product details in a structured format on your Nochex Payment Page.', '6', '6', 'tep_cfg_select_option(array(\'On\', \'Off\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Show Postage', 'MODULE_PAYMENT_NOCHEX_APC_POSTAGE', 'OFF', 'Postage Option is to separate the postage from the total amount', '6', '6', 'tep_cfg_select_option(array(\'On\', \'Off\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Debugging Mode', 'MODULE_PAYMENT_NOCHEX_APC_DEBUGGING', 'OFF', 'Debug mode is to test and make sure the module is working correctly, and if there is any faults being caused in your Nochex module.', '6', '6', 'tep_cfg_select_option(array(\'On\', \'Off\'), ', now())");						tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Callback', 'MODULE_PAYMENT_NOCHEX_APC_CALLBACK', 'OFF', 'To use the callback functionality, please contact Nochex Support to enable this functionality on your merchant account otherwise this function wont work.', '6', '6', 'tep_cfg_select_option(array(\'On\', \'Off\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Declined Status', 'MODULE_PAYMENT_NOCHEX_APC_ORDER_CANCEL_STATUS_ID', '50002', 'APC sets the status of declined orders to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Update Stock Before Payment', 'MODULE_PAYMENT_NOCHEX_APC_UPDATE_STOCK_BEFORE_PAYMENT','False', 'Should products stock be updated even when the payment is not yet COMPLETED?', '6', '6', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		}

		function remove() {
			tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		}

		function keys() {
			$module_keys = array(
				'MODULE_PAYMENT_NOCHEX_APC_STATUS',
				'MODULE_PAYMENT_NOCHEX_APC_EMAIL',
				'MODULE_PAYMENT_NOCHEX_APC_TESTMODE',
				'MODULE_PAYMENT_NOCHEX_APC_UPDATE_STOCK_BEFORE_PAYMENT',
				'MODULE_PAYMENT_NOCHEX_APC_ZONE',
				'MODULE_PAYMENT_NOCHEX_APC_ORDER_STATUS_ID',
				'MODULE_PAYMENT_NOCHEX_APC_ORDER_CANCEL_STATUS_ID',
				'MODULE_PAYMENT_NOCHEX_APC_SORT_ORDER',
				'MODULE_PAYMENT_NOCHEX_APC_BILLING',
				'MODULE_PAYMENT_NOCHEX_APC_XML',
				'MODULE_PAYMENT_NOCHEX_APC_POSTAGE',
				'MODULE_PAYMENT_NOCHEX_APC_DEBUGGING',
				'MODULE_PAYMENT_NOCHEX_APC_CALLBACK'
			);
			return $module_keys;
		}
	}
?>