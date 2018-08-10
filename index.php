<?php
/**
 * Plugin Name: adress autocompletion
 */
 
 function link_script()
{
    // Register the script like this for a plugin:
    wp_register_script( 'custom-script', plugins_url() . '/adress_autocompletion/adress_correction.js' );
	
    // For either a plugin or a theme, you can then enqueue the script:
    wp_enqueue_script( 'custom-script' );
}

add_action( 'wp_enqueue_scripts', 'link_script' );
?>