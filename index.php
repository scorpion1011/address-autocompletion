<?php
/**
 * Plugin Name: WooCommerce Address Autocompletion
 * Description: Set up Endereco Address WebServices for billling and shipping address forms. Requires WooCommerce installed.
 */

if(!defined('ABSPATH'))
{
	exit(); // Exit if accessed directly
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
	array_unshift($links, '<a href="/wp-admin/admin.php?page=wc-settings&tab=address-autocompletion">' . __('Settings') . '</a>');
	return $links;
});

add_action('wp_enqueue_scripts', function () {
	wp_enqueue_script( 'address-correction',    plugins_url() . '/address-autocompletion/js/address-correction.js', ['jquery'] );
	wp_enqueue_script( 'autocomplete',     plugins_url() . '/address-autocompletion/js/jquery.auto-complete.min.js' );
	wp_enqueue_script( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');

	wp_enqueue_style( 'autocomplete',  plugins_url() . '/address-autocompletion/js/jquery.auto-complete.css' );
	wp_enqueue_style( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );

	wp_add_inline_style( 'autocomplete', ' .wrong-gender { border: 1px solid yellow; background-color: #ffff0026 !important; } ' );

	wp_localize_script( 'address-correction', 'addressAutocompletion', [
			'ajaxurl'                 => admin_url('admin-ajax.php'),
			'isConsoleResponseNeeded' => get_option('logging') == 'yes' ? true : false,
			'delay'                   => intval(get_option('self_timeout', 150)),
			'billing'                 => __( 'Is the billing address correct?', 'address-autocompletion' ),
			'shipping'                => __( 'Is the shipping address correct?', 'address-autocompletion' )
		]
	);
});

add_action('plugins_loaded', function () {
	load_plugin_textdomain( 'address-autocompletion', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
});

add_action('wp_ajax_action', 'address_correction_get_ajax_json');
add_action('wp_ajax_nopriv_action', 'address_correction_get_ajax_json');

add_action('wp_footer', function () {
	require_once __DIR__.'/modal.php';
});

function address_correction_get_ajax_json()
{
	require_once __DIR__.'/sdk/autoload.php';

	$data = [];

	$sender  = empty( $_GET['sender']  ) ? '' : esc_attr( $_GET['sender']  );
	$zip     = empty( $_GET['zip']     ) ? '' : esc_attr( $_GET['zip']     );
	$city    = empty( $_GET['city']    ) ? '' : esc_attr( $_GET['city']    );
	$address = empty( $_GET['address'] ) ? '' : esc_attr( $_GET['address'] );

	$name    = empty( $_GET['name']    ) ? '' : esc_attr( $_GET['name']    );

	switch($sender)
	{
		case 'name':
			if(!empty($name))
			{
				$firstNameCheckRequest = new EnderecoFirstNameCheckRequest();
				$firstNameCheckRequest->setFirstName($name);
				$data = address_correction_get_enderco_data($firstNameCheckRequest);
			}
			break;
		case 'city':
			if(!empty($city))
			{
				$cityExpansionRequest = new EnderecoCityExpansionRequest();
				$cityExpansionRequest->setCity($city);
				$data = address_correction_get_enderco_data($cityExpansionRequest);
			}
			break;
		case 'zip':
			if(!empty($zip))
			{
				$postCodeExpansionRequest = new EnderecoPostCodeExpansionRequest();
				$postCodeExpansionRequest->setPostcode($zip);
				$data = address_correction_get_enderco_data($postCodeExpansionRequest);
			}
			break;
		case 'address':
			if(!empty($zip) && !empty($city) && !empty($address))
			{
				$streetExpansionRequest = new EnderecoStreetExpansionRequest();
				$streetExpansionRequest->setPostcode($zip);
				$streetExpansionRequest->setCity($city);
				$streetExpansionRequest->setStreet($address);
				$data = address_correction_get_enderco_data($streetExpansionRequest);
			}
			break;
		case 'submit':
			$addressCheckRequest = new EnderecoAddressCheckRequest();
			$addressCheckRequest->setPostcode($zip);
			$addressCheckRequest->setCity($city);
			$addressCheckRequest->setStreet($address);
			$data = address_correction_get_enderco_data($addressCheckRequest);
			break;
	}
	echo json_encode($data);
	wp_die();
}

function address_correction_get_enderco_data($expansionRequest)
{
	$data = [];

	try
	{
		$client = EnderecoClient::getInstance( get_option('mandator'), get_option('user'), get_option('password') );

		$results = $client->executeRequest($expansionRequest);
		foreach($results->getElements() as $expansion)
		{
			if($expansion instanceof OrwellInputAssistantPostCodeCityExpansionResultElement)
			{
				$postCode = $expansion->getPostCode();
				$city     = $expansion->getCity();
				if(!empty($postCode) || !empty($city))
				{
					$data[] = [
						'postcode' => $postCode,
						'city'     => $city,
					];
				}
			}
			elseif($expansion instanceof OrwellInputAssistantFirstNameCheckResultElement)
			{
				$gender = 'both';
				if(-1 !== $results->hasStatus(EnderecoFirstNameCheckStatusEnum::FIRST_NAME_NOT_FOUND))
				{
					$gender = 'undefined';
				}
				elseif(-1 !== $results->hasStatus(EnderecoFirstNameCheckStatusEnum::FIRST_NAME_IS_FEMALE))
				{
					$gender = 'female';
				}
				elseif(-1 !== $results->hasStatus(EnderecoFirstNameCheckStatusEnum::FIRST_NAME_IS_MALE))
				{
					$gender = 'male';
				}
				if($expansion->getFirstName())
				{
					$data[] = [
						'name'    => $expansion->getFirstName(),
						'gender'  => $gender,
					];
				}
				else
				{
					$data[] = [
						'name'    => $expansionRequest->getFirstName(),
						'gender'  => 'undefined',
					];
				}
			}
			else
			{
				$postCode = $expansion->getPostCode();
				$city     = $expansion->getCity();
				$street = $expansion->getStreet();
				if(!empty($postCode) || !empty($city) || !empty($street))
				{
					$data[] = [
						'postcode' => $postCode,
						'city'     => $city,
						'street'   => $street
					];
				}
			}
		}
	}
	catch(Exception $exception)
	{
		// ignore it
	}
	return $data;
}

add_filter('woocommerce_settings_tabs_array', function ($settings_tabs) {
	$settings_tabs['address-autocompletion'] = __( 'Autocomplete config', 'address-autocompletion' );
	return $settings_tabs;
}, 50 );

add_action('woocommerce_settings_tabs_address-autocompletion', function () {
	woocommerce_admin_fields( address_correction_get_config_content() );
});

add_action('woocommerce_update_options_address-autocompletion', function () {
	woocommerce_update_options( address_correction_get_config_content() );
});

function address_correction_get_config_content()
{
	return [
		'title' => [
			'name'     => '',
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'section_title'
		],
		'mandator' => [
			'name' => __( 'Mandator', 'address-autocompletion' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'mandator'
		],
		'user' => [
			'name' => __( 'User', 'address-autocompletion' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'user'
		],
		'password' => [
			'name' => __( 'Password', 'address-autocompletion' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'password'
		],
		'timeout' => [
			'name' => __( 'Timeout', 'address-autocompletion' ),
			'type' => 'text',
			'desc' => __( '150ms on empty', 'address-autocompletion' ),
			'desc_tip' => __( 'The delay in milliseconds between when a keystroke occurs and when a search is performed', 'address-autocompletion' ),
			'id'   => 'self_timeout'
		],
		'logging' => [
			'name' => __( 'Logging', 'address-autocompletion' ),
			'type' => 'checkbox',
			'desc' => __( 'Check this box if you want to be notified of sent queries in the Developer Console', 'address-autocompletion' ),
			'id'   => 'logging'
		],
		'end' => [
			'type' => 'sectionend',
			'id' => 'section_end'
		]
	];
}

add_filter('woocommerce_admin_settings_sanitize_option_self_timeout', function ($value) {
	if(ctype_digit(strval($value)))
	{
		return max(intval($value), 0);
	}
	return 150;
});

add_filter('woocommerce_get_country_locale', function($fields) {
	unset($fields['DE']['postcode']['priority']);
	return $fields;
});

add_filter('woocommerce_get_country_locale_default', function($fields) {
	$fields_order = [
		'company', 'gender', 'first_name', 'last_name', 'country',
		'state', 'city', 'postcode', 'address_1', 'address_2'
	];

	$priority_shift = 10 * count($fields_order) + 10;
	$priority = 10 * count($fields);
	foreach($fields as &$value)
	{
		if(!isset($value['priority']))
		{
			$value['priority'] = $priority;
			$priority += 10;
		}
		$value['priority'] = $value['priority'] + $priority_shift;
	}

	$priority = 10;
	foreach($fields_order as $key)
	{
		if(isset($fields[$key]))
		{
			$fields[$key]['priority'] = $priority;
			$priority += 10;
		}
	}

	return $fields;
});

add_filter('woocommerce_default_address_fields', function($fields) {
	return array_merge(['gender' => [
		'type'       => 'select',
		'label'      => __( 'Gender', 'address-autocompletion' ),
		'options'    => [
			'male'   => __( 'Male', 'address-autocompletion' ),
			'female' => __( 'Female', 'address-autocompletion' )
		]
	]], $fields);
});
