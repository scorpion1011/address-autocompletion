<?php
/**
 * Plugin Name: Adress Autocompletion
 * Description: Set up Endereco Address WebServices to the checkout page
 */

add_action( 'wp_enqueue_scripts', 'link_script' );
function link_script()
{
	wp_enqueue_script( 'custom-script', plugins_url() . '/adress_autocompletion/js/adress_correction.js', array('jquery') );

	wp_enqueue_style( 'autocomplete.css', plugins_url() . '/adress_autocompletion/js/jquery.auto-complete.css' );
	wp_enqueue_script( 'autocomplete', plugins_url() . '/adress_autocompletion/js/jquery.auto-complete.min.js' );

	wp_enqueue_style( 'bootstrap.min.css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );
	wp_enqueue_script( 'bootstrap.min.js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');

	$delay = get_option('self_timeout');
	wp_localize_script( 'custom-script', 'myPlugin',
		array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'isConsoleResponseNeeded' => get_option('logging') == 'yes' ? true : false,
			'delay' => $delay == '' ? 150 : $delay
		)
	);
}

add_action('wp_ajax_action', 'my_action_callback');
add_action('wp_ajax_nopriv_action', 'my_action_callback');
add_action('wp_footer', 'add_bootstrap_modale_template');

function add_bootstrap_modale_template()
{
	require_once __DIR__.'/modal.php';
}

function my_action_callback()
{
	require_once __DIR__.'/sdk/autoload.php';

	// 	$firstname = empty( $_GET['firstname'] ) ? '' : esc_attr( $_GET['firstname'] );

	$data = [];

	$sender  = empty( $_GET['sender']  ) ? '' : esc_attr( $_GET['sender']  );
	$zip     = empty( $_GET['zip']     ) ? '' : esc_attr( $_GET['zip']     );
	$city    = empty( $_GET['city']    ) ? '' : esc_attr( $_GET['city']    );
	$address = empty( $_GET['address'] ) ? '' : esc_attr( $_GET['address'] );

	switch($sender)
	{
		case 'city':
			if(!empty($city))
			{
				$cityExpansionRequest = new EnderecoCityExpansionRequest();
				$cityExpansionRequest->setCity($city);

				$data = getEndercoData($cityExpansionRequest);
			}
			break;
		case 'zip':
			if(!empty($zip))
			{
				$postCodeExpansionRequest = new EnderecoPostCodeExpansionRequest();
				$postCodeExpansionRequest->setPostcode($zip);

				$data = getEndercoData($postCodeExpansionRequest);
// 				$data = json_decode('[{"postcode":"30159","city":"Ha\"n\'n&o<v>erA"},{"postcode":"30161","city":"Hanno</div>verS"}]');
			}
			break;
		case 'address':
			if(!empty($zip) && !empty($city) && !empty($address))
			{
				$streetExpansionRequest = new EnderecoStreetExpansionRequest();
				$streetExpansionRequest->setPostcode($zip);
				$streetExpansionRequest->setCity($city);
				$streetExpansionRequest->setStreet($address);

				$data = getEndercoData($streetExpansionRequest);
			}
			break;
		case 'submit':
			$addressCheckRequest = new EnderecoAddressCheckRequest();
			$addressCheckRequest->setPostcode($zip);
			$addressCheckRequest->setCity($city);
			$addressCheckRequest->setStreet($address);

			$data = getEndercoData($addressCheckRequest);
			break;
	}
	//$data = json_decode( '[{"postcode":"50127","city":"Bergheim","street":"\'<dds>\'\"fdsd"}]' );

	echo json_encode($data);

	wp_die();
}

/**
 * Request for Endereco data
 * @param EnderecoAbstractRequest $expansionRequest
 * @return Array
 */
function getEndercoData($expansionRequest)
{
	$data = [];

	try
	{
		$client = EnderecoClient::getInstance( get_option('mandator'), get_option('user'), get_option('password') );

		foreach($client->executeRequest($expansionRequest)->getElements() as $expansion)
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
			else //OrwellInputAssistantStreetExpansionResultElement || EnderecoAddressCheckRequest
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

add_action('admin_menu', 'address_autocomlpete');

function address_autocomlpete()
{
	add_menu_page('Autocomplete Plugin', 'Address autocomlpete', 'manage_options', 'address_autocomlpete', 'autocomplete_page', 'dashicons-carrot', 10);
}

function autocomplete_page()
{
	require_once __DIR__.'/config.php';
}

add_filter( 'woocommerce_settings_tabs_array', 'add_settings_tab', 50 );

function add_settings_tab( $settings_tabs )
{
	$settings_tabs['settings_tab_demo'] = __( 'Autocomplete config', 'autocomplete-config' );
	return $settings_tabs;
}

add_action( 'woocommerce_settings_tabs_settings_tab_demo', 'settings_tab' );
function settings_tab()
{
	woocommerce_admin_fields( get_setting() );
}

function get_setting()
{
	$settings = array(
		'title' => array(
			'name'     => __( '', 'autocomplete-config' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'section_title'
		),
		'mandator' => array(
			'name' => __( 'Mandator', 'autocomplete-config' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'mandator'
		),
		'user' => array(
			'name' => __( 'User', 'autocomplete-config' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'user'
		),
		'password' => array(
			'name' => __( 'Password', 'autocomplete-config' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'password'
		),
		'timeout' => array(
			'name' => __( 'Timeout', 'autocomplete-config' ),
			'type' => 'text',
			'desc' => '',
			'id'   => 'self_timeout'
		),
		'logging' => array(
			'name' => __( 'Logging', 'autocomplete-config' ),
			'type' => 'checkbox',
			'desc' => __( 'Check this box if you want to be notified of sent queries in the Developer Console' ),
			'id'   => 'logging'
		),
		'end' => array(
			 'type' => 'sectionend',
			 'id' => 'section_end'
		)
	);
	return apply_filters( 'wc_settings_tab_demo_settings', $settings );
}

add_action( 'woocommerce_update_options_settings_tab_demo', function () {
	woocommerce_update_options( get_setting() );
});

add_filter('woocommerce_default_address_fields', function($fields) {
	$fields_order = [
		'company', 'first_name', 'last_name', 'country',
		'state', 'city', 'postcode', 'address_1', 'address_2'
	];

	// Set fields priority
	$priority = 10;

	foreach ($fields_order as $key)
	{
		if (!isset($fields[$key]))
		{
			continue;
		}

		$fields[$key]['priority'] = $priority;
		$priority += 10;
	}

	// Change fields order
	$fields_ordered = array();

	foreach ($fields_order as $key)
	{
		if (isset($fields[$key]))
		{
			$fields_ordered[$key] = $fields[$key];
		}
	}

	return $fields_ordered;
});

