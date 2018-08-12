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
			$city = empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );
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
			$zip     = empty( $_GET['zip'] ) ? '' : esc_attr( $_GET['zip'] );
			$city    = empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );
			$address = empty( $_GET['address'] ) ? '' : esc_attr( $_GET['address'] );
			if(!empty($zip) && !empty($city) && !empty($address))
			{
				$streetExpansionRequest = new EnderecoStreetExpansionRequest();
				$streetExpansionRequest->setPostcode($zip);
				$streetExpansionRequest->setCity($city);
				$streetExpansionRequest->setStreet($address);

				$data = getEndercoData($streetExpansionRequest);
			}
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
		if($expansion instanceof OrwellInputAssistantStreetExpansionResultElement)
		{
			$street = $expansion->getStreet();
			if(!empty($street))
			{
				$data[] = [
					'street' => $street
				];
			}
		}
		else //OrwellInputAssistantPostCodeCityExpansionResultElement
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
	}

	return $data;
}

