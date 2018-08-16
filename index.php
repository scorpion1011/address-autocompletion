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

	$sender  = empty( $_GET['sender'] ) ? '' : esc_attr( $_GET['sender'] );
	$zip     = empty( $_GET['zip'] ) ? '' : esc_attr( $_GET['zip'] );
	$city    = empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );
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

function address_autocomlpete() {
	add_menu_page('Autocomplete Plugin', 'Address autocomlpete', 'manage_options', 'address_autocomlpete', 'autocomplete_page', 'dashicons-carrot', 10);
}

function autocomplete_page() {
	require_once __DIR__.'/config.php';
}


add_filter( 'woocommerce_settings_tabs_array', 'add_settings_tab', 50 );

function add_settings_tab( $settings_tabs ) {
	$settings_tabs['settings_tab_demo'] = __( 'Autocomplete config', 'autocomplete-config' );
	return $settings_tabs;
}

add_action( 'woocommerce_settings_tabs_settings_tab_demo', 'settings_tab' );
function settings_tab() {
    woocommerce_admin_fields( get_setting() );
}
function get_setting() {
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

add_action( 'woocommerce_update_options_settings_tab_demo', 'update_settings' );
function update_settings() {
    woocommerce_update_options( get_setting() );
	
}

/*add_filter("woocommerce_checkout_fields", "custom_order_fields");

function custom_order_fields($fields) {
    
    $order = array(
        "billing_first_name", 
        "billing_last_name", 
        "billing_company", 
        "billing_country", 
		"billing_city",
        "billing_postcode",
        "billing_address_1", 
        "billing_address_2", 
        "billing_email", 
        "billing_phone"
    );
    foreach($order as $field)
    {
        $ordered_fields[$field] = $fields["billing"][$field];
		//echo get_option('mandator');
    }

    $fields["billing"] = $ordered_fields;
	
    $fields['billing']['billing_first_name']['priority'] = 10;
    $fields['billing']['billing_last_name']['priority'] = 20;
    $fields['billing']['billing_company']['priority'] = 30;
    $fields['billing']['billing_country']['priority'] = 40;
    $fields['billing']['billing_city']['priority'] = 50;
    $fields['billing']['billing_postcode']['priority'] = 60;
    $fields['billing']['billing_address_1']['priority'] = 70;
    $fields['billing']['billing_address_2']['priority'] = 80;
    $fields['billing']['billing_phone']['priority'] = 90;
    $fields['billing']['billing_email']['priority'] = 100;

    return $fields;

}

function vnmTheme_addressFieldsOverride() {
    if (is_wc_endpoint_url('edit-address') || is_checkout()) {
        ?>

        <script>
            jQuery(document).ready(function(jQuery) {

                jQuery(document.body).on('country_to_state_changing', function(event, country, wrapper) {

                    var $postcodeField = wrapper.find('#billing_postcode_field, #shipping_postcode_field');
                    var $housenoField = wrapper.find('#billing_house_number_field, #shipping_house_number_field' );

                    var fieldTimeout = setTimeout(function() {
                        $postcodeField.insertBefore($housenoField);
                    }, 50);
                });

            });
        </script>

        <?php
    }
}

add_action('wp_footer', 'vnmTheme_addressFieldsOverride', 999);
*/
