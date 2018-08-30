<?php
/**
 * Plugin Name: WooCommerce Address Autocompletion
 * Description: Set up Endereco Address WebServices for billling and shipping address forms. Requires WooCommerce installed.
 */

if(!defined('ABSPATH'))
{
    exit; // Exit if accessed directly
}

add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    array_unshift($links, '<a href="/wp-admin/admin.php?page=wc-settings&tab=settings_tab_demo">' . __( 'Settings' ) . '</a>');
    return $links;
});

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_script( 'custom-script', plugins_url() . '/address-autocompletion/js/address-correction.js', array('jquery') );
    wp_enqueue_style( 'autocomplete.css', plugins_url() . '/address-autocompletion/js/jquery.auto-complete.css' );
    wp_enqueue_script( 'autocomplete', plugins_url() . '/address-autocompletion/js/jquery.auto-complete.min.js' );
    wp_enqueue_style( 'bootstrap.min.css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );
    wp_enqueue_script( 'bootstrap.min.js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');

    $delay = get_option('self_timeout');
    wp_localize_script( 'custom-script', 'addressAutocompletion',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'isConsoleResponseNeeded' => get_option('logging') == 'yes' ? true : false,
            'delay' => $delay == '' ? 150 : $delay,
            'billing' => __( 'Is the billing address correct?', 'address-autocompletion'  ),
            'shipping' => __( 'Is the shipping address correct?', 'address-autocompletion'  )
        )
    );
});

add_action('plugins_loaded', function () {
    load_plugin_textdomain( 'address-autocompletion', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
});

add_action('wp_ajax_action', 'address_correction_getAjaxJson');
add_action('wp_ajax_nopriv_action', 'address_correction_getAjaxJson');

add_action('wp_footer', function () {
    require_once __DIR__.'/modal.php';
});

function address_correction_getAjaxJson()
{
    require_once __DIR__.'/sdk/autoload.php';

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
                $data = address_correction_getEndercoData($cityExpansionRequest);
            }
            break;
        case 'zip':
            if(!empty($zip))
            {
                $postCodeExpansionRequest = new EnderecoPostCodeExpansionRequest();
                $postCodeExpansionRequest->setPostcode($zip);
                $data = address_correction_getEndercoData($postCodeExpansionRequest);
            }
            break;
        case 'address':
            if(!empty($zip) && !empty($city) && !empty($address))
            {
                $streetExpansionRequest = new EnderecoStreetExpansionRequest();
                $streetExpansionRequest->setPostcode($zip);
                $streetExpansionRequest->setCity($city);
                $streetExpansionRequest->setStreet($address);
                $data = address_correction_getEndercoData($streetExpansionRequest);
            }
            break;
        case 'submit':
            $addressCheckRequest = new EnderecoAddressCheckRequest();
            $addressCheckRequest->setPostcode($zip);
            $addressCheckRequest->setCity($city);
            $addressCheckRequest->setStreet($address);
            $data = address_correction_getEndercoData($addressCheckRequest);
            break;
    }
    echo json_encode($data);
    wp_die();
}

function address_correction_getEndercoData($expansionRequest)
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

add_filter( 'woocommerce_settings_tabs_array', function ($settings_tabs) {
    $settings_tabs['settings_tab_demo'] = __( 'Autocomplete config', 'address-autocompletion' );
    return $settings_tabs;
}, 50 );

add_action( 'woocommerce_settings_tabs_settings_tab_demo', function () {
    woocommerce_admin_fields( address_correction_getContent() );
});

add_action( 'woocommerce_update_options_settings_tab_demo', function () {
    woocommerce_update_options( address_correction_getContent() );
});

function address_correction_getContent()
{
    $settings = array(
        'title' => array(
            'name'     => '',
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'section_title'
        ),
        'mandator' => array(
            'name' => __( 'Mandator', 'address-autocompletion' ),
            'type' => 'text',
            'desc' => '',
            'id'   => 'mandator'
        ),
        'user' => array(
            'name' => __( 'User', 'address-autocompletion' ),
            'type' => 'text',
            'desc' => '',
            'id'   => 'user'
        ),
        'password' => array(
            'name' => __( 'Password', 'address-autocompletion' ),
            'type' => 'text',
            'desc' => '',
            'id'   => 'password'
        ),
        'timeout' => array(
            'name' => __( 'Timeout', 'address-autocompletion' ),
            'type' => 'text',
            'desc' => __( '150ms on empty', 'address-autocompletion' ),
            'desc_tip' => __( 'The delay in milliseconds between when a keystroke occurs and when a search is performed', 'address-autocompletion' ),
            'id'   => 'self_timeout'
        ),
        'logging' => array(
            'name' => __( 'Logging', 'address-autocompletion' ),
            'type' => 'checkbox',
            'desc' => __( 'Check this box if you want to be notified of sent queries in the Developer Console', 'address-autocompletion' ),
            'id'   => 'logging'
        ),
        'end' => array(
            'type' => 'sectionend',
            'id' => 'section_end'
        )
    );
    return apply_filters( 'wc_settings_tab_demo_settings', $settings );
}

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

