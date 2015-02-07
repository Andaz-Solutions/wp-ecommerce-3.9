<?php

$nzshpcrt_gateways[$num] = array(
    'name' => __('Andaz', 'wpsc'),
    'api_version' => 2.0,
    'class_name' => 'wpsc_merchant_andaz',
    'has_recurring_billing' => true,
    'display_name' => __('Andaz', 'wpsc'),
    'image' => WPSC_URL . '/images/cc.gif',
    'requirements' => array(
        'php_version' => 4.3,
        'extra_modules' => array()
    ),
    'form' => 'form_andaz',
    'submit_function' => 'submit_andaz',
    'internalname' => 'wpsc_merchant_andaz',
    'payment_type' => 'credit_card',
    'supported_currencies' => array(
        'currency_list' => array('MRO','EUR','EUR','USD','XOF','MVR','MYR','MWK','MGA','MOP','MKD','EUR','LTL','CHF','LYD','LRD','LSL','LBP','LVL','LAK','KGS','KWD','KRW','KPW','AUD','KES','KZT','JOD','GBP','JPY','JMD','XOF','EUR','GBP','ILS','EUR','IQD','IDR','IRR','INR','ISK','HUF','HKD','HNL','AUD','HTG','GYD','XAF','GNF','GBP','GTQ','USD','XCD','EUR','DKK','EUR','GIP','GHS','EUR','GEL','GMD','XAF','EUR','EUR','EUR','FJD','DKK','FKP','ETB','EUR','ERN','XAF','USD','EGP','ECS','USD','DOP','XCD','DJF','DKK','CDF','CZK','EUR','CUP','HRK','CRC','NZD','XAF','KMF','COP','AUD','AUD','CLP','CNY','XAF','XAF','KYD','CVE','XAF','CAD','KHR','BIF','XOF','BGL','BND','USD','BRL','NOK','BWP','BAM','BOB','BTN','BMD','XOF','BZD','EUR','BYR','BBD','BDT','BHD','BSD','AZN','EUR','AWG','AMD','ARS','XCD','ATA','XCD','AOA','EUR','USD','DZD','ALL','AFA','USD','AUD','MUR','EUR','MXN','USD','MDL','EUR','MNT','XCD','MAD','MZN','MMK','NAD','AUD','NPR','EUR','ANG','XPF','NZD','NIO','XOF','NGN','NZD','AUD','USD','NOK','OMR','PKR','USD','PAB','PGK','PYG','PEN','PHP','NZD'),
        'option_name' => 'andaz_curcode'
    )
);

/**
 * WP eCommerce Andaz Standard Merchant Class
 *
 * This is an Andaz standard merchant class, it extends the base merchant class
 *
 * @package wp-e-commerce
 * @since 3.7.6
 * @subpackage wpsc-merchants
 */
class wpsc_merchant_andaz extends wpsc_merchant {

    var $name = '';

    function __construct($purchase_id = null, $is_receiving = false) {
        $this->name = __('Andaz', 'wpsc');
        parent::__construct($purchase_id, $is_receiving);
    }

    function get_local_currency_code() {
        if (empty($this->local_currency_code)) {
            global $wpdb;
            $this->local_currency_code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`= %d LIMIT 1", get_option('currency_type')));
        }
        
        return $this->local_currency_code;
    }

    function get_andaz_currency_code() {
        if (empty($this->andaz_currency_code)) {
            global $wpsc_gateways;
            $this->andaz_currency_code = $this->get_local_currency_code();

            if (!in_array($this->andaz_currency_code, $wpsc_gateways['wpsc_merchant_andaz']['supported_currencies']['currency_list']))
                $this->andaz_currency_code = get_option('andaz_curcode', 'USD');
        }

        return $this->andaz_currency_code;
    }

    /**
     * construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
     * @access public
     */
    function construct_value_array() {
        //$collected_gateway_data
        $andaz_vars = array();
        // Store settings to be sent to paypal

        $data = array();
        $data['client_id'] = get_option('andaz_client_id');
        $data['client_username'] = get_option('andaz_client_username');
        $data['client_password'] = get_option('andaz_client_password');
        $data['client_token'] = get_option('andaz_client_token');
        
        $data['mode'] = 'undefined';
        $data['processing_type'] = 'debit';
        $data['currency'] = $this->get_andaz_currency_code();
        
        $data['remote_address'] = $_SERVER['REMOTE_ADDR'];
        $data['billing_first_name'] = $this->cart_data['billing_address']['first_name'];
        $data['billing_last_name'] = $this->cart_data['billing_address']['last_name'];
        $data['billing_email_address'] = $this->cart_data['email_address'];
        $data['billing_address_line_1'] = $this->cart_data['billing_address']['address'];
        $data['billing_city'] = $this->cart_data['billing_address']['city'];
        $data['billing_state'] = $this->cart_data['billing_address']['state'];
        $data['billing_country'] = $this->cart_data['billing_address']['country'];
        $data['billing_postal_code'] = $this->cart_data['billing_address']['post_code'];
        
        $data['shipping_first_name'] = $this->cart_data['shipping_address']['first_name'];
        $data['shipping_last_name'] = $this->cart_data['shipping_address']['last_name'];
        $data['shipping_address_line_1'] = $this->cart_data['shipping_address']['address'];
        $data['shipping_city'] = $this->cart_data['shipping_address']['city'];
        $data['shipping_state'] = $this->cart_data['shipping_address']['state'];
        $data['shipping_country'] = $this->cart_data['shipping_address']['country'];
        $data['shipping_postal_code'] = $this->cart_data['shipping_address']['post_code'];
        
        $data['account_number'] = $_POST['card_number'];
        $data['expiration_month'] = $_POST['expiry']['month'];
        $data['expiration_year'] = $_POST['expiry']['year'];
        $data['cvv2'] = $_POST['card_code'];

        // Ordered Items
        // Cart Item Data
        $i = $item_total = 0;
        $tax_total = wpsc_tax_isincluded() ? 0 : $this->cart_data['cart_tax'];

        $shipping_total = $this->convert($this->cart_data['base_shipping']);

        $elements = array();
        foreach ($this->cart_items as $cart_row) {
            $i++;
            $elements[] = "item_$i:".str_replace(':', '+', str_replace(',', ' ', apply_filters( 'the_title', $cart_row['name'] ))). " | Qty({$cart_row['quantity']}) | Price(".$this->convert( $cart_row['price'] ).")";
            
            $shipping_total += $this->convert($cart_row['shipping']);
            $item_total += $this->convert($cart_row['price']) * $cart_row['quantity'];
        }
        
        if ($this->cart_data['has_discounts']) {
            $discount_value = $this->convert($this->cart_data['cart_discount_value']);

            $coupon = new wpsc_coupons($this->cart_data['cart_discount_data']);

            // free shipping
            if ($coupon->is_percentage == 2) {
                $shipping_total = 0;
                $discount_value = 0;
            } elseif ($discount_value >= $item_total) {
                $discount_value = $item_total - 0.01;
                $shipping_total -= 0.01;
            }

            $elements[] = "item_$i: Coupon/Discount | Qty(1) | Price($discount_value)";
        
            $item_total -= $discount_value;
        }

        if (!empty($elements)) {
            $data['pass_through'] = implode(',', $elements);
        }
        
        // Cart totals
        $cart_item_amount = $this->format_price($item_total);
        $cart_shipping_amount = $this->format_price($shipping_total);
        $cart_tax_amount = $this->convert($tax_total);
        $data['amount'] = $cart_item_amount + $cart_shipping_amount + $cart_tax_amount;
        $this->collected_gateway_data = apply_filters('wpsc_andaz_gateway_data_array', $data, $this->cart_items);
    }

    /**
     * submit method, sends the received data to the payment gateway
     * @access public
     */
    function submit() {
        $andaz_url = "https://www.andazsolutions.com/post-web-service/process"; // Live

        $options = array(
            'timeout' => 20,
            'body' => $this->collected_gateway_data,
            'user-agent' => $this->cart_data['software_name'] . " " . get_bloginfo('url'),
            'sslverify' => false,
        );
        $response = wp_remote_post($andaz_url, $options);

        // parse the response body

        $error_data = array();
        if (is_wp_error($response)) {
            $error_data[0]['error_code'] = null;
            $error_data[0]['error_message'] = __('There was a problem connecting to the payment gateway.', 'wpsc');
        } else {
            $parsed_response = json_decode($response['body']);
        }

        // Extract the error messages from the response object
        if ($parsed_response->status == 'error') {
            $error_data[1]['error_code'] = 'err';
            $error_data[1]['error_message'] = $parsed_response->message;
        }

        switch ($parsed_response->status) {
            case 'approved':
                $this->set_transaction_details($parsed_response->transaction_id, 3);
                $this->go_to_transaction_results($this->cart_data['session_id']);
                break;
            case 'declined':
                $this->set_error_message('Transaction was declined, please try a different card.');
            default:
                foreach ((array) $error_data as $error_row) {
                    $this->set_error_message($error_row['error_message']);
                }
                $this->return_to_checkout();
                exit();
                break;
        }
    }

    function format_price($price) {
        $andaz_currency_code = get_option('andaz_curcode');

        switch ($andaz_currency_code) {
            case "JPY":
                $decimal_places = 0;
                break;
            case "HUF":
                $decimal_places = 0;
            default:
                $decimal_places = 2;
                break;
        }

        $price = number_format(sprintf("%01.2f", $price), $decimal_places, '.', '');

        return $price;
    }

    function convert($amt) {
        if (empty($this->rate)) {
            $this->rate = 1;
            $andaz_currency_code = $this->get_andaz_currency_code();
            $local_currency_code = $this->get_local_currency_code();
            if ($local_currency_code != $andaz_currency_code) {
                $curr = new CURRENCYCONVERTER();
                $this->rate = $curr->convert(1, $andaz_currency_code, $local_currency_code);
            }
        }

        return $this->format_price($amt * $this->rate);
    }

}

function submit_andaz() {
    if (isset($_POST['Andaz']['client_id']))
        update_option('andaz_client_id', $_POST['Andaz']['client_id']);
    
    if (isset($_POST['Andaz']['client_username']))
        update_option('andaz_client_username', $_POST['Andaz']['client_username']);

    if (isset($_POST['Andaz']['client_password']))
        update_option('andaz_client_password', $_POST['Andaz']['client_password']);

    if (isset($_POST['andaz_curcode']))
        update_option('andaz_curcode', $_POST['andaz_curcode']);

    if (isset($_POST['Andaz']['client_token']))
        update_option('andaz_client_token', $_POST['Andaz']['client_token']);

    return true;
}

function form_andaz() {
    global $wpsc_gateways, $wpdb;
    
    $output = '
	<tr>
		<td>
			<label for="andaz_client_id">' . __('Andaz Client ID:', 'wpsc') . '</label>
		</td>
		<td>
			<input type="text" name="Andaz[client_id]" id="andaz_client_id" value="' . get_option("andaz_client_id") . '" size="30" />
		</td>
	</tr>
	<tr>
		<td>
			<label for="andaz_username">' . __('Andaz Client Username:', 'wpsc') . '</label>
		</td>
		<td>
			<input type="text" name="Andaz[client_username]" id="andaz_username" value="' . get_option("andaz_client_username") . '" size="30" />
		</td>
	</tr>

	<tr>
		<td>
			<label for="andaz_password">' . __('Andaz Password:', 'wpsc') . '</label>
		</td>
		<td>
			<input type="password" name="Andaz[client_password]" id="andaz_client_password" value="' . get_option('andaz_client_password') . '" size="16" />
		</td>
	</tr>
	<tr>
		<td>
			<label for="andaz_client_token">' . __('Andaz Token:', 'wpsc') . '</label>
		</td>
		<td>
			<input type="text" name="Andaz[client_token]" id="andaz_token" value="' . get_option('andaz_client_token') . '" size="48" />
		</td>
	</tr>';

    $store_currency_code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` IN (%d)", get_option('currency_type')));
    $current_currency = get_option('andaz_curcode');

    if (($current_currency == '') && in_array($store_currency_code, $wpsc_gateways['wpsc_merchant_andaz']['supported_currencies']['currency_list'])) {
        update_option('andaz_curcode', $store_currency_code);
        $current_currency = $store_currency_code;
    }
    if ($current_currency != $store_currency_code) {
        $output .= "<tr> <td colspan='2'><strong class='form_group'>" . __('Currency Converter', 'wpsc') . "</td> </tr>
		<tr>
			<td colspan='2'>" . __('Your website is using a currency not accepted by Andaz, select an accepted currency using the drop down menu below. Buyers on your site will still pay in your local currency however we will convert the currency and send the order through to Andaz using the currency you choose below.', 'wpsc') . "</td>
		</tr>

		<tr>
			<td>" . __('Convert to', 'wpsc') . " </td>
			<td>
				<select name='andaz_curcode'>\n";

        if (!isset($wpsc_gateways['wpsc_merchant_andaz']['supported_currencies']['currency_list']))
            $wpsc_gateways['wpsc_merchant_andaz']['supported_currencies']['currency_list'] = array();

        $andaz_currency_list = array_map('esc_sql', $wpsc_gateways['wpsc_merchant_andaz']['supported_currencies']['currency_list']);

        $currency_list = $wpdb->get_results("SELECT DISTINCT `code`, `currency` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `code` IN ('" . implode("','", $andaz_currency_list) . "')", ARRAY_A);
        foreach ($currency_list as $currency_item) {
            $selected_currency = '';
            if ($current_currency == $currency_item['code']) {
                $selected_currency = "selected='selected'";
            }
            $output .= "<option " . $selected_currency . " value='{$currency_item['code']}'>{$currency_item['currency']}</option>";
        }
        $output .= "
				</select>
			</td>
		</tr>\n";
    }

    $output .="
	<tr>
		<td colspan='2'>
			<p class='description'>
				" . sprintf(__("For more help configuring Andaz, please email <a href='%s'>Support</a>", 'wpsc'), esc_url('mailto:support@andaz.com')) . "
				</p>
		</td>
	</tr>";
    return $output;
}

$years = $months = '';

if (in_array('wpsc_merchant_andaz', (array) get_option('custom_gateway_options'))) {

    $curryear = date('Y');

    //generate year options
    for ($i = 0; $i < 10; $i++) {
        $years .= "<option value='$curryear'>$curryear</option>\r\n";
        $curryear++;
    }

    $output = "
	<tr>
		<td class='wpsc_CC_details'>" . __('Credit Card Number *', 'wpsc') . "</td>
		<td>
			<input type='text' value='' name='card_number' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>" . __('Credit Card Expiry *', 'wpsc') . "</td>
		<td>
			<select class='wpsc_ccBox' name='expiry[month]'>";
    foreach (range(1,12) as $month) {
        $month = sprintf('%02d', $month);
        $selected = (date('m') == $month) ? 'selected=selected' : '';
        $output .= "            <option value='$month' $selected>$month</option>\n";
    }
    
    $output .= "        </select>
			<select class='wpsc_ccBox' name='expiry[year]'>
			" . $years . "
			</select>
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>" . __('CVV *', 'wpsc') . "</td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>" . __('Card Type *', 'wpsc') . "</td>
		<td>
		<select class='wpsc_ccBox' name='cctype'>";

    $card_types = array(
        'Visa' => __('Visa', 'wpsc'),
        'Mastercard' => __('MasterCard', 'wpsc'),
        'Discover' => __('Discover', 'wpsc'),
        'Amex' => __('Amex', 'wpsc'),
    );
    $card_types = apply_filters('wpsc_andaz_accepted_card_types', $card_types);
    foreach ($card_types as $type => $title) {
        $output .= sprintf('<option value="%1$s">%2$s</option>', $type, esc_html($title));
    }
    $output .= "</select>
		</td>
	</tr>
";

    $gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = $output;
}
?>

