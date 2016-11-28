<?php
// if we're not uninstalling..
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// clean up..
require_once dirname( __FILE__ ) . '/plugin.php';
delete_option( 'jesin_' . $custom_error_pages_plugin->slug );