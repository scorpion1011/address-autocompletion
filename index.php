<?php
/**
 * Plugin Name: Adress Autocompletion
 * Description: Set up Endereco Address WebServices to the checkout page
 */

add_action( 'wp_enqueue_scripts', 'link_script' );
function link_script()
{
    // Register the script like this for a plugin:
    ///wp_register_script( 'custom-script', plugins_url() . '/adress_autocompletion/adress_correction.js' );

	///wp_enqueue_script('jquery');
    // For either a plugin or a theme, you can then enqueue the script:
    ///wp_enqueue_script( 'custom-script' );

	wp_enqueue_script( 'custom-script', plugins_url() . '/adress_autocompletion/js/adress_correction.js', array('jquery') );

	wp_localize_script( 'custom-script', 'myPlugin',
		array(
			'ajaxurl' => admin_url('admin-ajax.php')
		)
	);
}

add_action('wp_ajax_action', 'my_action_callback');
add_action('wp_ajax_nopriv_action', 'my_action_callback');

function my_action_callback()
{
	require_once __DIR__.'/sdk/autoload.php';

	// 	$firstname = empty( $_GET['firstname'] ) ? '' : esc_attr( $_GET['firstname'] );

	$data = [];

	$sender =  empty( $_GET['sender'] ) ? '' : esc_attr( $_GET['sender'] );

	switch($sender)
	{
		case 'city':
			$city =  empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );
			if(!empty($city))
			{
				$cityExpansionRequest = new EnderecoCityExpansionRequest();
				$cityExpansionRequest->setCity($city);

				$data = getEndercoData($cityExpansionRequest);
			}
			break;
		case 'zip':
			$zip = empty( $_GET['zip'] ) ? '' : esc_attr( $_GET['zip'] );
			if(!empty($zip))
			{
				$postCodeExpansionRequest = new EnderecoPostCodeExpansionRequest();
				$postCodeExpansionRequest->setPostcode($zip);

				$data = getEndercoData($postCodeExpansionRequest);
			}
			break;
		case 'address':
			$data = json_decode('[{"street": "Eisenlohrsweg"},{"street": "Eppendorfer Baum"},{"street": "Eppendorfer Landstr."}]');
			break;
	}
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

	$client = EnderecoClient::getInstance('mobilemojo', 'developer01', 'zG-BE$_9');

	foreach($client->executeRequest($expansionRequest)->getElements() as $expansion)
	{
		$data[] = [
				'postcode' => $expansion->getPostCode(),
				'city'     => $expansion->getCity(),
			];
	}

	return $data;
}

