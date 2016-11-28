<?php
/*
Plugin Name: Ultimate Maintenance Mode
Plugin URI: http://seedprod.com
Description: Displays a screenshot of website with an overlayed window with the reason your site is down.
Version: 1.7.1
Author: SeedProd
Text Domain: ultimate-maintenance-mode
Domain Path: /languages
Author URI: http://seedprod.com
License: GPLv2
Copyright 2011  John Turner (email : john@seedprod.com, twitter : @johnturner)
*/

/**
 * Init
 *
 * @package WordPress
 * @subpackage Ultimate_Maintenence_Mode
 * @since 0.1
 */

/**
 * Require config to get our initial values
 */
define( 'UMM_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) );
define( 'UMM_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) );

load_plugin_textdomain('ultimate-maintenance-mode',false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

require_once('framework/framework.php');
require_once('inc/config.php');

add_action( 'after_plugin_row_' .  plugin_basename( __FILE__ ), 'seedprod_umm_deprication_msg', 10, 2 );

function seedprod_umm_deprication_msg($file, $plugin){
	echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update">';
	echo '<div style=" color: #a94442; background:#f2dede; padding:10px; border: 1px #ebccd1 solid;"><strong>Important:</strong> Ultimate Maintenance Mode plugin is being deprecated and will be removed soon from wordpress.org. Please use our new version located at: <a href="plugin-install.php?tab=search&s=Coming+Soon+Page+%26+Maintenance+Mode+by+SeedProd" >Coming Soon Page & Maintenance Mode by SeedProd</a></div>';
	echo '</td></tr>';
}
