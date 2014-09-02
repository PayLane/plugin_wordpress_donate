<?php
/*
Plugin Name: PayLane Payment Plugin
Plugin URI: http://paylane.com
Description: This plugin gives you opportunity to integrate PayLane payment service with your Wordpress site. 
Version: 1.0
Author: PayLane Sp. z o.o.
Author URI: http://paylane.com
License: freeware
*/

register_activation_hook(__FILE__, 'wp_paylane_plugin_install');
add_action('admin_menu', 'wp_paylane_plugin_menu');
add_action('admin_init', 'wp_paylane_plugin_register_settings');
add_shortcode('paylane_plugin', 'wp_paylane_plugin_shortcodes');
add_action('init', 'wp_paylane_plugin_check_postback');

/*
 * Check postback from PayLane and redirect user to proper page
 */
function wp_paylane_plugin_check_postback()
{
	$was_redirected = false;
	$is_successful = false;
	
	if (wp_paylane_plugin_is_parameter_set('correct') && wp_paylane_plugin_is_parameter_set('merchant_transaction_id') 
		&& wp_paylane_plugin_is_parameter_set('amount') && wp_paylane_plugin_is_parameter_set('currency_code'))
	{
		// postback from paylane
		$was_redirected = true;
		$is_successful = wp_paylane_plugin_check_postback_status();
	}
	
	if (true == $was_redirected)
	{
		if (true == $is_successful)
		{
			$message = "You've received new payment from your site.";
			
			if (wp_paylane_plugin_is_parameter_set('id_sale'))
			{
				$id_sale = wp_paylane_plugin_get_parameter('id_sale');
					
				$message .= "\nid_sale = " . $id_sale;
			}
			
			$message .= "\nWe always encourage verifying each sale manually in the PayLane Merchant Panel.";
			
			wp_mail(get_option('wp_paylane_plugin_notification_email'), 'New payment', $message);
			
			if (strlen(get_option('wp_paylane_plugin_success_url')) > 0)
			{
				header('Location: ' . get_option('wp_paylane_plugin_success_url'));
				die;
			}
		}
		else
		{
			if (strlen(get_option('wp_paylane_plugin_error_url')) > 0)
			{
				header('Location: ' . get_option('wp_paylane_plugin_error_url'));
				die;
			}
		}
	}
}

/**
 * Return array of available currencies
 * 
 * @return array [code] => [name]
 */
function wp_paylane_plugin_get_currencies()
{
	$currencies = array("AED" => "UAE Dirham", "AFN" => "Afghani", "ALL" => "Lek",
		"AMD" => "Armenian Dram", "ANG" => "Netherlands Antillian Guikder", "AOA" => "Kwanza", 
		"ARS" => "Argentine Peso", "AUD" => "Australian Dollar", "AWG" => "Aruban Guilder", 
		"AZN" => "Azerbaijanian Manat", "BAM" => "Convertible Marks", "BBD" => "Barbados Dollar", 
		"BDT" => "Taka", "BGN" => "Bulgarian Lev", "BHD" => "Bahraini Dinar", "BIF" => "Burundi Franc", 
		"BMD" => "Bermudian Dollar", "BND" => "Brunei Dollar", "BOB" => "Boliviano", "BOV" => "Mvdol", 
		"BRL" => "Brazilian Real", "BSD" => "Bahamian Dollar", "BTN" => "Ngultrum", "BWP" => "Pula", 
		"BYR" => "Belarussian Ruble", "BZD" => "Belize Dollar", "CAD" => "Canadian Dollar", 
		"CDF" => "Franc Congolais", "CHE" => "WIR Euro", "CHF" => "Swiss Franc", "CHW" => "WIR Franc", 
		"CLF" => "Unidades de formento", "CLP" => "Chilean Peso", "CNY" => "Yuan Renminbi", 
		"COP" => "Colombian Peso", "COU" => "Unidad de Valor Real", "CRC" => "Costa Rican Colon", 
		"CUP" => "Cuban Peso", "CVE" => "Cape Verde Escudo", "CZK" => "Czech Koruna", "DJF" => "Djibouti Franc", 
		"DKK" => "Danish Krone", "DOP" => "Dominican Peso", "DZD" => "Algerian Dinar", "EEK" => "Kroon", 
		"EGP" => "Egyptian Pound", "ERN" => "Nakfa", "ETB" => "Ethiopian Birr", "EUR" => "Euro", "FJD" => "Fiji Dollar", 
		"FKP" => "Falkland Islands Pound", "GBP" => "Pound Sterling", "GEL" => "Lari", "GHS" => "Ghana Cedi", 
		"GIP" => "Gibraltar Pound", "GMD" => "Dalasi", "GNF" => "Guinea Franc", "GTQ" => "Quetzal", 
		"GWP" => "Guinea-Bissau Peso", "GYD" => "Guyana Dollar", "HKD" => "Hong Kong Dollar", "HNL" => "Lempira", 
		"HRK" => "Croatian Kuna", "HTG" => "Gourde", "HUF" => "Forint", "IDR" => "Rupiah", "ILS" => "New Israeli Sheqel", 
		"INR" => "Indian Rupee", "IQD" => "Iraqi Dinar", "IRR" => "Iranian Rial", "ISK" => "Iceland Krona", 
		"JMD" => "Jamaican Dollar", "JOD" => "Jordanian Dinar", "JPY" => "Yen", "KES" => "Kenyan Shilling", 
		"KGS" => "Som", "KHR" => "Riel", "KMF" => "Comoro Franc", "KPW" => "North Korean Won", "KRW" => "Won", 
		"KWD" => "Kuwaiti Dinar", "KYD" => "Cayman Islands Dollar", "KZT" => "Tenge", "LAK" => "Kip", 
		"LBP" => "Lebanese Pound", "LKR" => "Sri Lanka Rupee", "LRD" => "Liberian Dollar", "LSL" => "Loti", 
		"LTL" => "Lithuanian Litas", "LVL" => "Latvian Lats", "LYD" => "Libyan Dinar", "MAD" => "Moroccan Dirham", 
		"MDL" => "Moldovan Leu", "MGA" => "Malagascy Ariary", "MKD" => "Denar", "MMK" => "Kyat", "MNT" => "Tugrik", 
		"MOP" => "Pataca", "MRO" => "Ouguiya", "MUR" => "Mauritius Rupee", "MVR" => "Rufiyaa", "MWK" => "Kwacha", 
		"MXN" => "Mexican Peso", "MXV" => "Mexican Unidad de Inversion UID", "MYR" => "Malaysian Ringgit", 
		"MZN" => "Metical", "NAD" => "Namibian Dollar", "NGN" => "Naira", "NIO" => "Cordoba Oro", 
		"NOK" => "Norwegian Krone", "NPR" => "Nepalese Rupee", "NZD" => "New Zealand Dollar", "OMR" => "Rial Omani", 
		"PAB" => "Balboa", "PEN" => "Nuevo Sol", "PGK" => "Kina", "PHP" => "Philippine Peso", "PKR" => "Pakistan Rupee", 
		"PLN" => "Polish New Zloty", "PYG" => "Guarani", "QAR" => "Qatari Rial", "RON" => "New Leu", "RSD" => "Serbian Dinar", 
		"RUB" => "Russian Ruble", "RWF" => "Rwanda Franc", "SAR" => "Saudi Riyal", "SBD" => "Solomon Islands Dollar", 
		"SCR" => "Seychelles Rupee", "SDG" => "Sudanese Pound", "SEK" => "Swedish Krona", "SGD" => "Singapore Dollar", 
		"SHP" => "Saint Helena Pound", "SKK" => "Slovak Koruna", "SLL" => "Leone", "SOS" => "Somali Shilling", 
		"SRD" => "Surinam Dollar", "STD" => "Dobra", "SVC" => "El Salvador Colon", "SYP" => "Syrian Pound", 
		"SZL" => "Lilangeni", "THB" => "Baht", "TJS" => "Somoni", "TMM" => "Manat", "TND" => "Tunisian Dinar", 
		"TOP" => "Pa'anga", "TRY" => "New Turkish Lira", "TTD" => "Trinidad and Tobago Dollar", "TWD" => "New Taiwan Dollar", 
		"TZS" => "Tanzanian Shilling", "UAH" => "Hryvnia", "UGX" => "Uganda Shilling", "USD" => "US Dollar", 
		"USN" => "US Dollar (Next day)", "USS" => "US Dollar (Same day)", "UYI" => "Uruguay Peso en Unidades Indexad", 
		"UYU" => "Peso Uruguayo", "UZS" => "Uzbekistan Sum", "VEF" => "Bolivar Fuerte", "VND" => "Dong", 
		"VUV" => "Vatu", "WST" => "Tala", "XAF" => "CFA Franc BEAC", "XCD" => "East Caribbean Dollar", "XDR" => "SDR", 
		"XOF" => "CFA Franc BCEAO", "XPF" => "CFP Franc", "YER" => "Yemeni Rial", "ZAR" => "Rand", "ZMK" => "Kwacha", 
		"ZWD" => "Zimbabwe Dollar");
	
	return $currencies;
}

/**
 * Register each option for plugin settings
 */
function wp_paylane_plugin_register_settings()
{
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_currency', 'wp_paylane_plugin_options_validate_currency');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_notification_email', 'wp_paylane_plugin_options_validate_notification_email');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_salt');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_amount', 'wp_paylane_plugin_options_validate_amount');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_button_text');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_transaction_description');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_merchant_id');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_redirect_type', 'wp_paylane_plugin_options_validate_redirect_type');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_success_url', 'wp_paylane_plugin_options_validate_url');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_error_url', 'wp_paylane_plugin_options_validate_url');
	register_setting('wp_paylane_plugin_options_group', 'wp_paylane_plugin_merchant_transaction_id', 'wp_paylane_plugin_options_validate_merchant_transaction_id');
}

/**
 * Validate given url address
 *
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_url($input)
{
	if (strlen($input) > 0)
	{
		if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $input))
		{
			$input = home_url();
		}
	}

	return $input;
}

/**
 * Validate redirect type
 *
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_redirect_type($input)
{
	if (($input !== 'POST') && ($input !== 'GET'))
	{
		$input = 'POST';
	}

	return $input;
}

/**
 * Validate amount given by user
 * 
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_amount($input)
{	
	$input - str_replace(",", ".", $input);
	
	if (!preg_match("/^[0-9]*\.?[0-9]+$/", $input))
	{
		$input = floatval($input);
	}
	
	return $input;
}

/**
 * Validate merchant_transaction_id
 *
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_merchant_transaction_id($input)
{
	if (strlen($input) < 2)
	{
		$input = "transactionid";
	}
	else
	{
		$input = preg_replace('/[^A-Za-z0-9]/', '', $input);
		$input = substr($input, 0, 20);
	}
	
	return $input;
}

/**
 * Validate currency code chosen by user
 *
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_currency($input)
{
	$isCorrect = false;
	
	foreach(wp_paylane_plugin_get_currencies() as $key => $value)
	{
		if ($input == $key)
		{
			$isCorrect = true;
			break;
		}
	}

	return ($isCorrect ? $input : "USD");
}

/**
 * Validate notification e-mail given by user
 *
 * @param string $input
 * @return string
 */
function wp_paylane_plugin_options_validate_notification_email($input)
{
	if (!is_email($input))
	{
		$input = get_option('admin_email');
	}

	return $input;
}

/**
 * Register plugin's shortcode
 * 
 * @param array $attributes
 */
function wp_paylane_plugin_shortcodes($attributes)
{
	return wp_paylane_plugin_display();
}

/**
 * Register options page for plugin
 */
function wp_paylane_plugin_menu()
{
	add_options_page('PayLane Payment Plugin Options', 'PayLane Payment Plugin', 'manage_options', 'wp_paylane_plugin', 'wp_paylane_plugin_options');
}

/**
 * Show options page for plugin in Settings section
 */
function wp_paylane_plugin_options()
{
	if (!current_user_can('manage_options'))
	{
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	
	echo '<div class="wrap">';
	echo '<h2>PayLane Payment Plugin Options</h2>';
	echo '<form method="post" action="options.php">';
	settings_fields('wp_paylane_plugin_options_group');
	do_settings_sections('wp_paylane_plugin');
	echo '<table class="form-table">';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Notification e-mail</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_notification_email" size="40" value="' . get_option('wp_paylane_plugin_notification_email') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Success URL</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_success_url" size="40" value="' . get_option('wp_paylane_plugin_success_url') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Error URL</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_error_url" size="40" value="' . get_option('wp_paylane_plugin_error_url') . '"/></td>';
	echo '</tr>';
	
	
	echo '<tr valign="top">';
	echo '<th scope="row">Redirect type</th>';
	echo '<td><select name="wp_paylane_plugin_redirect_type">';
	if (get_option('wp_paylane_plugin_redirect_type') === 'POST')
	{
		echo '<option value="POST" selected>POST</option>';
		echo '<option value="GET">GET</option>';
	}
	else
	{
		echo '<option value="POST">POST</option>';
		echo '<option value="GET" selected>GET</option>';
	}
	echo '</select></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Hash salt</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_salt" value="' . get_option('wp_paylane_plugin_salt') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Merchant ID</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_merchant_id" value="' . get_option('wp_paylane_plugin_merchant_id') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Amount</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_amount" value="' . get_option('wp_paylane_plugin_amount') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Currency code</th>';
	echo '<td><select name="wp_paylane_plugin_currency">';
	foreach (wp_paylane_plugin_get_currencies() as $currency_code => $currency_name)
	{
		if ($currency_code == get_option('wp_paylane_plugin_currency'))
		{
			echo '<option value="' . $currency_code . '" selected>' . $currency_name . '</option>';
		}
		else
		{
			echo '<option value="' . $currency_code . '">' . $currency_name . '</option>';
		}
	}
	echo '</select></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Merchant transaction ID <small>(will appear in PayLane merchant panel in Sale Details tab)</small></th>';
	echo '<td><input type="text" name="wp_paylane_plugin_merchant_transaction_id" value="' . get_option('wp_paylane_plugin_merchant_transaction_id') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">PAY Button text</th>';
	echo '<td><input type="text" name="wp_paylane_plugin_button_text" value="' . get_option('wp_paylane_plugin_button_text') . '"/></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Transaction description <small>(will appear in PayLane Secure Form transaction description section)</small></th>';
	//echo '<td><input type="text" name="wp_paylane_plugin_transaction_description" value="' . get_option('wp_paylane_plugin_transaction_description') . '"/></td>';
	echo '<td>';
	wp_editor(get_option('wp_paylane_plugin_transaction_description'), 'wp_paylane_plugin_transaction_description');
	echo '</td>';
	echo '</tr>';
	
	echo '</table>';
	echo '<p class="submit">';
	echo '<input type="submit" class="button-primary" value="Save Changes"/>';
	echo '</p>';
	echo '</form>';
	echo '</div>';
}

/**
 * Add options for plugin
 */
function wp_paylane_plugin_install()
{
	add_option('wp_paylane_plugin_currency', 'EUR');
	add_option('wp_paylane_plugin_notification_email', get_option('admin_email'));
	add_option('wp_paylane_plugin_salt', 'YOUR HASH SALT');
	add_option('wp_paylane_plugin_amount', '1.00');
	add_option('wp_paylane_plugin_button_text', 'Pay with PayLane');
	add_option('wp_paylane_plugin_transaction_description', 'donation');
	add_option('wp_paylane_plugin_merchant_id', 'YOUR MERCHANT ID');
	add_option('wp_paylane_plugin_merchant_transaction_id', 'YOUR MERCHANT TRANSACTION ID');
	add_option('wp_paylane_plugin_redirect_type', 'POST');
	add_option('wp_paylane_plugin_success_url', '');
	add_option('wp_paylane_plugin_error_url', '');
}

/**
 * Check if specified POST/GET parameter is set
 * 
 * @param string $param
 * @return boolean
 */
function wp_paylane_plugin_is_parameter_set($param)
{
	if (get_option('wp_paylane_plugin_redirect_type') === 'POST')
	{
		return isset($_POST[$param]);
	}
	else
	{
		return isset($_GET[$param]);
	}
}

/**
 * Return given POST/GET parameter 
 * 
 * @param string $param
 * @return string
 */
function wp_paylane_plugin_get_parameter($param)
{
	if (get_option('wp_paylane_plugin_redirect_type') === 'POST')
	{
		return $_POST[$param];
	}
	else
	{
		return $_GET[$param];
	}
}

/**
 * Check postback from PayLane
 * 
 * @return boolean true if transaction successful, otherwise return false
 */
function wp_paylane_plugin_check_postback_status()
{
	$my_merchant_transaction_id = get_option('wp_paylane_plugin_merchant_transaction_id');
	$my_amount = get_option('wp_paylane_plugin_amount');
	$my_currency_code = get_option('wp_paylane_plugin_currency');
	$hash_salt = get_option('wp_paylane_plugin_salt');
	
	// required fields
	if (wp_paylane_plugin_is_parameter_set('correct') && wp_paylane_plugin_is_parameter_set('merchant_transaction_id') 
		&& wp_paylane_plugin_is_parameter_set('amount') && wp_paylane_plugin_is_parameter_set('currency_code'))
	{
		$correct = wp_paylane_plugin_get_parameter('correct');
		$merchant_transaction_id = wp_paylane_plugin_get_parameter('merchant_transaction_id');
		$amount = wp_paylane_plugin_get_parameter('amount');
		$currency_code = wp_paylane_plugin_get_parameter('currency_code');
			
			
		if (($correct === "1") && ($merchant_transaction_id === $my_merchant_transaction_id) &&
			($amount === $my_amount) && ($currency_code === $my_currency_code))
		{
			$ghash = wp_paylane_plugin_get_parameter('hash');
			
			if ((strlen($ghash) > 0))
			{					
				if (wp_paylane_plugin_is_parameter_set('hash'))
				{
					$my_hash = sha1($hash_salt . "|1|" . $my_merchant_transaction_id
					. "|" . $my_amount . "|" . $my_currency_code);
						
						
					// everything is ok
					if ($my_hash === $ghash)
					{	
						return true;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

/**
 * Display payment button or process PayLane feedback
 * 
 * @return string html code
 */
function wp_paylane_plugin_display()
{
	$output = "";
	
	$hash_salt = get_option('wp_paylane_plugin_salt');
	$merchant_id = get_option('wp_paylane_plugin_merchant_id');
	$my_merchant_transaction_id = get_option('wp_paylane_plugin_merchant_transaction_id');
	$my_amount = get_option('wp_paylane_plugin_amount');
	$my_currency_code = get_option('wp_paylane_plugin_currency');
	
	$back_url = "";
	if ($_SERVER['SERVER_PORT'] == 443)
	{
		$back_url = "https://";
	}
	else
	{
		$back_url = "http://";
	}
	$back_url .= $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];
	
	$transaction_description = get_option('wp_paylane_plugin_transaction_description');
	$button_text = get_option('wp_paylane_plugin_button_text');
	$shash = sha1($hash_salt . "|" . $my_merchant_transaction_id . "|" . $my_amount . "|" . $my_currency_code . "|S");
	
	$was_redirected = false;
	$is_successful = false;
	
	// postback from paylane
	if (wp_paylane_plugin_is_parameter_set('correct') && wp_paylane_plugin_is_parameter_set('merchant_transaction_id') 
		&& wp_paylane_plugin_is_parameter_set('amount') && wp_paylane_plugin_is_parameter_set('currency_code'))
	{
		$was_redirected = true;
		$is_successful = wp_paylane_plugin_check_postback_status();
	}
	
	$output .= "<div>";
	
	if (true == $was_redirected)
	{
		if (true == $is_successful)
		{
			$output .= "<span style=\"font-size:16px; color:#69BF00;\">Payment completed!</span>";
		}
		else
		{
			$output .= "<span style=\"font-size:16px; color:#FF0000;\">Error has occured!</span>";
		}
	}
	
	// show payment button	
	$output .= "
		<form action=\"https://secure.paylane.com/order/cart.html\" method=\"post\">
			<input type=\"hidden\" name=\"merchant_id\" value=\"$merchant_id\"/>
			<input type=\"hidden\" name=\"merchant_transaction_id\" value=\"$my_merchant_transaction_id\"/>
			<input type=\"hidden\" name=\"amount\" value=\"$my_amount\"/>
			<input type=\"hidden\" name=\"currency_code\" value=\"$my_currency_code\"/>
			<input type=\"hidden\" name=\"transaction_type\" value=\"S\"/>
			<input type=\"hidden\" name=\"back_url\" value=\"$back_url\"/>
			<input type=\"hidden\" name=\"transaction_description\" value=\"" . htmlspecialchars($transaction_description) . "\"/>
			<input type=\"hidden\" name=\"language\" value=\"en\"/>
			<input type=\"hidden\" name=\"hash\" value=\"$shash\"/>
			<input type=\"submit\" value=\"$button_text\"/>
		</form></div><br/>";
	
	return $output;
}
?>