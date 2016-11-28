<?php
if(defined('SHORTPIXEL_DEBUG') && SHORTPIXEL_DEBUG === true) {
    require_once('shortpixel-debug.php');
}

require_once('class/wp-shortpixel-settings.php');
require_once('shortpixel_api.php');
require_once('class/shortpixel_queue.php');
//entities
require_once('class/model/shortpixel-entity.php');
require_once('class/model/shortpixel-meta.php');
require_once('class/model/shortpixel-folder.php');
//exceptions
require_once('class/model/sp-file-rights-exception.php');
//database access
require_once('class/db/shortpixel-db.php');
require_once('class/db/wp-shortpixel-db.php');
require_once('class/db/shortpixel-custom-meta-dao.php');
require_once('class/db/shortpixel-nextgen-adapter.php');
require_once('class/db/wp-shortpixel-media-library-adapter.php');
require_once('class/db/shortpixel-meta-facade.php');
//view
require_once('class/view/shortpixel_view.php');

require_once('class/shortpixel-tools.php');

require_once( ABSPATH . 'wp-admin/includes/image.php' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 

/*
if ( !is_plugin_active( 'wpmandrill/wpmandrill.php' ) //avoid conflicts with some plugins
  && !is_plugin_active( 'wp-ses/wp-ses.php' ) 
  && !is_plugin_active( 'wordfence/wordfence.php') ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
} 
*/


