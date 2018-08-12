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

	$sender =  empty( $_GET['sender'] ) ? '' : esc_attr( $_GET['sender'] );

	switch($sender)
	{
		case 'city':
			$data = json_decode('[{"city": "Daaden","postCode": "57567"},{"city": "Daasdorf a Berge","postCode": "99428"},{"city": "Daasdorf b Buttelstedt","postCode": "99439"},{"city": "Dabel","postCode": "19406"},{"city": "Dabergotz","postCode": "16818"},{"city": "Daberkow","postCode": "17129"},{"city": "Dachau","postCode": "85221"},{"city": "Dachrieden","postCode": "99974"},{"city": "Dachsbach","postCode": "91462"},{"city": "Dachsberg","postCode": "79875"}]');
			break;
		case 'zip':
// 			$data = json_decode('[{"city": "Daaden","postCode": "57567"},{"city": "Daasdorf a Berge","postCode": "99428"},{"city": "Daasdorf b Buttelstedt","postCode": "99439"},{"city": "Dabel","postCode": "19406"},{"city": "Dabergotz","postCode": "16818"},{"city": "Daberkow","postCode": "17129"},{"city": "Dachau","postCode": "85221"},{"city": "Dachrieden","postCode": "99974"},{"city": "Dachsbach","postCode": "91462"},{"city": "Dachsberg","postCode": "79875"}]');
			$zip = empty( $_GET['zip'] ) ? '' : esc_attr( $_GET['zip'] );
// 			$city =  empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );

			$client = EnderecoClient::getInstance('mobilemojo', 'developer01', 'zG-BE$_9');
			$postCodeExpansionRequest = new EnderecoPostCodeExpansionRequest();


			// 	$postCodeExpansionRequest->setPostcode('972');
			// 		->setCity('Randersacker')
			// 		->setHousenumber('4b')
			// 		->setStreet('Balthasar-Neumann-Straße');


			if(!empty($zip))
			{
				$postCodeExpansionRequest->setPostcode($zip);
			}
// 			if(!empty($city))
// 			{
// 				$postCodeExpansionRequest->setCity($city);
// 			}
			$result = $client->executeRequest($postCodeExpansionRequest);
			$data = [];
			foreach($result->getElements() as $correction)
			{
				$data[] = [
						'postcode' => $correction->getPostCode(),
						'city'     => $correction->getCity(),
					];
			}
			break;
		case 'address':
			$data = json_decode('[{"street": "Eisenlohrsweg"},{"street": "Eppendorfer Baum"},{"street": "Eppendorfer Landstr."}]');
			break;
	}
	echo json_encode($data);

// 	$result = '[{"zip":"' . $zip . '";"city":"' . $city . '";"firstname":"' . $firstname . '";"sender":"' . $sender . '"}]';

// 	echo $result;

	wp_die();
}
