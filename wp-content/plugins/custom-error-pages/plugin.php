<?php
/*
Plugin Name: Custom Error Pages
Plugin URI: http://websistent.com/wordpress-plugins/custom-error-pages/
Description: Create custom 401 and 403 error pages with any WordPress theme without writing a single line of code, set it up and forget it.
Author: Jesin
Author URI: http://websistent.com
Version: 1.1
*/

if( !class_exists( 'Create_Custom_Error_Pages' ) && !class_exists( 'Custom_Error_Pages_Plugin' ) )
{
	class Custom_Error_Pages_Plugin
	{
		var $options;
		var $basename;
		var $slug;
		var $defaults;

		function __construct()
		{
			$this->basename = plugin_basename( __FILE__ );
			$this->slug = str_replace( array( basename( __FILE__ ), '/' ), '', $this->basename );
			$this->options = get_option( 'jesin_' . $this->slug );

			//Set the default text to display on error pages
			$this->defaults = array();
			$this->defaults['title_401'] = 'HTTP 401 Unauthorized';
			$this->defaults['content_401'] = 'Access denied due to invalid HTTP credentials';
			$this->defaults['title_403'] = 'HTTP 403 Forbidden';
			$this->defaults['content_403'] = 'You don&#39;t have permission to access this resource';

			add_action( 'init', array( $this, 'plugin_init' ) );
			add_filter( 'query_vars', array( $this, 'add_status_query_var' ) ); //Add a custom query variable
			add_action( 'parse_request', array( $this, 'get_status_query_var' ) ); //Check if the custom query variable exists
		}

		function plugin_init()
		{
			load_plugin_textdomain( $this->slug, FALSE, $this->slug . '/languages' );
		}

		//Adds custom "status" query variable
		function add_status_query_var( $vars )
		{
			$vars[] = 'status';
			return $vars;
		}

		//Checks for the existence of "status" query variable
		function get_status_query_var( &$wp )
		{
			if( isset( $wp->query_vars[ 'status' ] ) && ( $wp->query_vars[ 'status' ] == '401' || $wp->query_vars[ 'status' ] == '403' ) )
			{
				//Tells caching plugins like W3 Total Cache and WP Supercache not to cache these custom error pages
				define( 'DONOTCACHEPAGE', TRUE );

				$status = (int) $wp->query_vars[ 'status' ];

				//Cancel the canonical URL redirect
				remove_action( 'template_redirect', 'redirect_canonical' );

				//Create a custom error page based on our custom query variable
				$create_custom_error_pages = new Create_Custom_Error_Pages( array( 'options' => $this->options, 'code' => $status ) );
			}
		}
	}

	//The Class which creates a "Virtual Page"
	class Create_Custom_Error_Pages
	{
		var $http_code;
		var $options;

		function __construct( $args )
		{
			$this->http_code = $args['code'];
			$this->options = $args['options'];

			add_action( 'wp', array( $this, 'send_status' ) );
			add_filter( 'the_posts', array( $this, 'generate_error_page' ) );
		}

		//Sends sets the appropriate HTTP error code in the response header
		function send_status()
		{
			status_header( $this->http_code );
		}

		//Create a Virtual "Page" with comments disabled on the fly
		function generate_error_page( $posts )
		{
			global $wp, $wp_query, $custom_error_pages_plugin;

			if( $wp_query->post_count == 0 )
			{
				$post = new stdClass;
				$post->ID = 0;
				$post->post_author = 1;
				$post->post_date = current_time( 'mysql' );
				$post->post_date_gmt = current_time( 'mysql', 1 );
				$post->post_content = ( (isset( $this->options['content_' . $this->http_code] ) && !empty( $this->options['content_' . $this->http_code] )) ? $this->options['content_' . $this->http_code] : $custom_error_pages_plugin->defaults['content_' . $this->http_code] );
				$post->post_title = ( (isset( $this->options['title_' . $this->http_code] ) && !empty( $this->options['title_' . $this->http_code] )) ? $this->options['title_' . $this->http_code] : $custom_error_pages_plugin->defaults['title_' . $this->http_code] );
				$post->post_excerpt = '';
				$post->post_status = 'publish';
				$post->comment_status = 'closed';
				$post->ping_status = 'closed';
				$post->post_password = '';
				$post->post_name = $this->http_code . '-error-' . time();
				$post->to_ping = '';
				$post->pinged = '';
				$post->post_type = 'page';
				$post->modified = $post->post_date;
				$post->modified_gmt = $post->post_date_gmt;
				$post->post_content_filtered = '';
				$post->post_parent = 0;
				$post->guid = get_home_url('/' . $post->post_name);
				$post->menu_order = 0;
				$post->post_tyle = 'page';
				$post->post_mime_type = '';
				$post->comment_count = 0;
 
				$posts = array( $post );
 
				$wp_query->is_page = TRUE;
				$wp_query->is_singular = TRUE;
				$wp_query->is_single = FALSE;
				$wp_query->is_home = FALSE;
				$wp_query->is_archive = FALSE;
				$wp_query->is_category = FALSE;
				unset( $wp_query->query['error'] );
				$wp_query->query_vars['error'] = '';
				$wp_query->is_404 = FALSE;
				$wp_query->comment_count = 0;
				$wp_query->current_comment = null;
			}

			return ( $posts );
		}
	}

	//Execution of the plugin begins here
	$custom_error_pages_plugin = new Custom_Error_Pages_Plugin;
}

if( is_admin() )
	require_once dirname( __FILE__ ) . '/admin_options.php';
