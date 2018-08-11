<?php
/**
 * Plugin Name: adress autocompletion
 */
 
add_action( 'wp_enqueue_scripts', 'link_script' );
function link_script()
{
    // Register the script like this for a plugin:
    ///wp_register_script( 'custom-script', plugins_url() . '/adress_autocompletion/adress_correction.js' );
	
	///wp_enqueue_script('jquery');
    // For either a plugin or a theme, you can then enqueue the script:
    ///wp_enqueue_script( 'custom-script' );
	
	wp_enqueue_script( 'custom-script', plugins_url() . '/adress_autocompletion/adress_correction.js', array('jquery') );
	
	wp_localize_script( 'custom-script', 'myPlugin', 
		array(
			'ajaxurl' => admin_url('admin-ajax.php')
		)
	);  
}

add_action('wp_ajax_action', 'my_action_callback');
add_action('wp_ajax_nopriv_action', 'my_action_callback');

function my_action_callback() {
	$zip = empty( $_GET['zip'] ) ? '' : esc_attr( $_GET['zip'] );
	$city =  empty( $_GET['city'] ) ? '' : esc_attr( $_GET['city'] );
	$firstname = empty( $_GET['firstname'] ) ? '' : esc_attr( $_GET['firstname'] );
	$sender =  empty( $_GET['sender'] ) ? '' : esc_attr( $_GET['sender'] );
	
	$result = '[{"zip":"' . $zip . '";"city":"' . $city . '";"firstname":"' . $firstname . '";"sender":"' . $sender . '"}]';
	 
	echo $result;

	wp_die();
}
?>