<?php
if( !class_exists( 'Custom_Error_Pages_Admin' ) )
{
	class Custom_Error_Pages_Admin extends Custom_Error_Pages_Plugin
	{
		function __construct( )
		{
			parent::__construct();
			add_action( 'admin_menu' , array( $this, 'plugin_menu' ) );
			add_action( 'admin_init' , array( $this, 'plugin_settings' ) );
		}

		function plugin_menu()
		{
			add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'settings_link' ) );
			$plugin_page = add_options_page( __( 'Custom Error Pages', $this->slug ), __( 'Custom Error Pages', $this->slug ), 'manage_options', $this->slug, array( $this, 'plugin_options' ) );
			add_action( 'admin_head-' . $plugin_page, array( $this, 'plugin_panel_styles' ) );
			add_action( 'load-' . $plugin_page, array( $this, 'notice_hook' ) );
		}

		function notice_hook()
		{
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}

		function notice()
		{
			echo '<div class="updated"><p>' . sprintf( __( 'If you find this plugin useful please consider giving it a %sfive star%s rating.', $this->slug ), '<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/' . $this->slug . '?rate=5#postform">', '</a>' ) . '</p></div>';
		}

		//Adds additional links under this plugin on the WordPress Plugins page
		function settings_link( $links )
		{
			array_unshift( $links, '<a href="' . admin_url( 'options-general.php?page=' . $this->slug ) . '">' . __( 'Settings', $this->slug ) . '</a>' );
			$links[] = '<a href="http://websistent.com/wordpress-plugins/" target="_blank">' .  __( 'More Plugins', $this->slug ) . '</a>';
			return $links;
		}

		function plugin_settings()
		{
			register_setting( $this->slug . '_options', 'jesin_' . $this->slug, array( $this, 'sanitize_input' ) );

			add_settings_section( $this->slug.'_403_settings', sprintf( __( '%s Error Page', $this->slug ), '403' ), array( $this, 'callback_403' ), $this->slug );
			add_settings_field( 'title_403', __( 'Page Title', $this->slug ), array( $this, 'title_403' ), $this->slug, $this->slug . '_403_settings', array( 'label_for' => 'title_403' ) );
			add_settings_field( 'content_403', __( 'Page Content', $this->slug ), array( $this, 'content_403' ), $this->slug, $this->slug . '_403_settings', array( 'label_for' => 'content_403' ) );

			add_settings_section( $this->slug.'_401_settings', sprintf( __( '%s Error Page', $this->slug ), '401' ), array( $this, 'callback_401' ), $this->slug );
			add_settings_field( 'title_401', __( 'Page Title', $this->slug ), array( $this, 'title_401' ), $this->slug, $this->slug . '_401_settings', array( 'label_for' => 'title_401' ) );
			add_settings_field( 'content_401', __( 'Page Content', $this->slug ), array( $this, 'content_401' ), $this->slug, $this->slug . '_401_settings', array( 'label_for' => 'content_401' ) );
		}

		//Additional CSS for the plugin options page
		function plugin_panel_styles()
		{
			echo '<style type="text/css">
			#title_403, #title_401 { margin-bottom:5px;padding:3px 8px;font-size:1.7em;line-height:100%;height:1.7em;width:100%;outline:0;margin:1px 0; }
			#icon-' . $this->slug . '{ background:transparent url(\'' . plugin_dir_url( __FILE__ ) . 'screen-icon.png\') no-repeat; }</style>';
		}

		//Some instructions for the 403 page
		function callback_403()
		{
			echo sprintf( __( '%sPreview%s this page. (Changes will apply only after you click %sSave Changes%s)', $this->slug ), '<a href="' . home_url( '/?status=403' ) . '" target="_blank">', '</a>', '&quot;', '&quot;' );
		}

		//Some instructions for the 401 page
		function callback_401()
		{
			echo sprintf( __( '%sPreview%s this page. (Changes will apply only after you click %sSave Changes%s)', $this->slug ), '<a href="' . home_url( '/?status=401' ) . '" target="_blank">', '</a>', '&quot;', '&quot;' );
		}

		//Displays the title input box for the custom 403 error page
		function title_403()
		{
			$value = ( isset( $this->options['title_403'] ) && !empty( $this->options['title_403'] ) ) ? $this->options['title_403'] : $this->defaults['title_403'];
			echo '<input id="title_403" type="text" name="jesin_' . $this->slug . '[title_403]" value="' . $value . '"/>';
		}

		//Displays a WordPress editor for entering content for the custom 403 page
		function content_403()
		{
			$value = ( isset( $this->options['content_403'] ) && !empty( $this->options['content_403'] ) ) ? $this->options['content_403'] : $this->defaults['content_403'];
			wp_editor( $value, 'content_403', array( 'textarea_name' => 'jesin_' . $this->slug . '[content_403]' ) );
		}

		//Displays the title input box for the custom 403 error page
		function title_401()
		{
			$value = ( isset( $this->options['title_401'] ) && !empty( $this->options['title_401'] ) ) ? $this->options['title_401'] : $this->defaults['title_401'];
			echo '<input id="title_401" type="text" name="jesin_' . $this->slug . '[title_401]" value="' . $value . '"/>';
		}

		//Displays a WordPress editor for entering content for the custom 401 page
		function content_401()
		{
			$value = ( isset( $this->options['content_401'] ) && !empty( $this->options['content_401'] ) ) ? $this->options['content_401'] : $this->defaults['content_401'];
			wp_editor( $value, 'content_401', array( 'textarea_name' => 'jesin_' . $this->slug . '[content_401]' ) );
		}

		//Sanitizing the heading input fields
		function sanitize_input( $input )
		{
			$input['title_403'] = esc_attr( $input['title_403'] );
			$input['title_401'] = esc_attr( $input['title_401'] );
			return $input;
		}

		//
		function plugin_options()
		{
		?>
			<div class="wrap">
			<?php screen_icon( $this->slug ); ?>
			<h2><?php _e( 'Custom Error Pages', $this->slug ); ?></h2>
			<form method="post" action="options.php">
			<?php settings_fields( $this->slug . '_options' ); ?>
			<p><?php printf( __( '%s users place the following in your %s file.', $this->slug ), '<strong>Apache</strong>', '<code>' . get_home_path() . '.htaccess</code>' ); ?><br />
			<pre>ErrorDocument 403 <?php echo str_replace( $_SERVER['DOCUMENT_ROOT'], '', get_home_path() ) . 'index.php?status=403'; ?>

ErrorDocument 401 <?php echo str_replace( $_SERVER['DOCUMENT_ROOT'], '', get_home_path() ) . 'index.php?status=401'; ?></pre></p>
			<p><?php printf( __( '%s users place the following in your %s file.', $this->slug ), '<strong>Nginx</strong>', '<code>nginx.conf</code>' ); ?>
			<pre>error_page	403 = <?php echo str_replace( $_SERVER['DOCUMENT_ROOT'], '', get_home_path() ) . 'index.php?status=403;'; ?>

error_page	401 = <?php echo str_replace( $_SERVER['DOCUMENT_ROOT'], '', get_home_path() ) . 'index.php?status=401;'; ?></pre></p>
			<?php do_settings_sections( $this->slug );
			submit_button(); ?>
			</form>
			</div>
		<?php
		}
	}

	//Admin options page begins here
	$custom_error_pages_admin = new Custom_Error_Pages_Admin;
}
