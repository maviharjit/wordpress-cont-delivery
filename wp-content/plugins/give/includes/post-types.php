<?php
/**
 * Post Type Functions
 *
 * @package     Give
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and sets up the Donation Forms (give_forms) custom post type
 *
 * @since 1.0
 * @return void
 */
function give_setup_post_types() {

	/** Give Forms Post Type */
	$give_forms_singular = give_get_option( 'disable_forms_singular' ) !== 'on' ? true : false;

	$give_forms_archives = give_get_option( 'disable_forms_archives' ) !== 'on' ? true : false;

	$give_forms_slug = defined( 'GIVE_SLUG' ) ? GIVE_SLUG : 'donations';
	//support for old 'GIVE_FORMS_SLUG' constant
	if ( defined( 'GIVE_FORMS_SLUG' ) ) {
		$give_forms_slug = GIVE_FORMS_SLUG;
	}

	$give_forms_rewrite = defined( 'GIVE_DISABLE_FORMS_REWRITE' ) && GIVE_DISABLE_FORMS_REWRITE ? false : array(
		'slug'       => $give_forms_slug,
		'with_front' => false
	);

	$give_forms_labels = apply_filters( 'give_forms_labels', array(
		'name'               => esc_html__( 'Donation %2$s', 'give' ),
		'singular_name'      => '%1$s',
		'add_new'            => esc_html__( 'Add %1$s', 'give' ),
		'add_new_item'       => esc_html__( 'Add New Donation %1$s', 'give' ),
		'edit_item'          => esc_html__( 'Edit Donation %1$s', 'give' ),
		'new_item'           => esc_html__( 'New %1$s', 'give' ),
		'all_items'          => esc_html__( 'All %2$s', 'give' ),
		'view_item'          => esc_html__( 'View %1$s', 'give' ),
		'search_items'       => esc_html__( 'Search %2$s', 'give' ),
		'not_found'          => esc_html__( 'No %2$s found', 'give' ),
		'not_found_in_trash' => esc_html__( 'No %2$s found in Trash', 'give' ),
		'parent_item_colon'  => '',
		'menu_name'          => apply_filters( 'give_menu_name', esc_html__( 'Donations', 'give' ) ),
		'name_admin_bar'     => apply_filters( 'give_name_admin_bar_name', esc_html__( 'Donation Form', 'give' ) )
	) );

	foreach ( $give_forms_labels as $key => $value ) {
		$give_forms_labels[ $key ] = sprintf( $value, give_get_forms_label_singular(), give_get_forms_label_plural() );
	}

	//Default give_forms supports
	$give_form_supports = array(
		'title',
		'thumbnail',
		'excerpt',
		'revisions',
		'author'
	);

	//Has the user disabled the excerpt
	if ( give_get_option( 'disable_forms_excerpt' ) === 'on' ) {
		unset( $give_form_supports[2] );
	}

	//Has user disabled the featured image?
	if ( give_get_option( 'disable_form_featured_img' ) === 'on' ) {
		unset( $give_form_supports[1] );
		remove_action( 'give_before_single_form_summary', 'give_show_form_images' );
	}

	$give_forms_args = array(
		'labels'             => $give_forms_labels,
		'public'             => true,
		'publicly_queryable' => $give_forms_singular,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => $give_forms_rewrite,
		'map_meta_cap'       => true,
		'capability_type'    => 'give_form',
		'has_archive'        => $give_forms_archives,
		'menu_icon'          => 'dashicons-give',
		'hierarchical'       => false,
		'supports'           => apply_filters( 'give_forms_supports', $give_form_supports ),
	);
	register_post_type( 'give_forms', apply_filters( 'give_forms_post_type_args', $give_forms_args ) );
	
	/** Payment Post Type */
	$payment_labels = array(
		'name'               => _x( 'Donations', 'post type general name', 'give' ),
		'singular_name'      => _x( 'Donation', 'post type singular name', 'give' ),
		'add_new'            => esc_html__( 'Add New', 'give' ),
		'add_new_item'       => esc_html__( 'Add New Donation', 'give' ),
		'edit_item'          => esc_html__( 'Edit Donation', 'give' ),
		'new_item'           => esc_html__( 'New Donation', 'give' ),
		'all_items'          => esc_html__( 'All Donations', 'give' ),
		'view_item'          => esc_html__( 'View Donation', 'give' ),
		'search_items'       => esc_html__( 'Search Donations', 'give' ),
		'not_found'          => esc_html__( 'No Donations found', 'give' ),
		'not_found_in_trash' => esc_html__( 'No Donations found in Trash', 'give' ),
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__( 'Transactions', 'give' )
	);

	$payment_args = array(
		'labels'          => apply_filters( 'give_payment_labels', $payment_labels ),
		'public'          => false,
		'query_var'       => false,
		'rewrite'         => false,
		'map_meta_cap'    => true,
		'capability_type' => 'give_payment',
		'supports'        => array( 'title' ),
		'can_export'      => true
	);
	register_post_type( 'give_payment', $payment_args );

}

add_action( 'init', 'give_setup_post_types', 1 );


/**
 * Give Setup Taxonomies
 *
 * Registers the custom taxonomies for the give_forms custom post type
 *
 * @since      1.0
 * @return void
 */
function give_setup_taxonomies() {

	$slug = defined( 'GIVE_FORMS_SLUG' ) ? GIVE_FORMS_SLUG : 'donations';

	/** Categories */
	$category_labels = array(
		/* translators: %s: form singular label */
		'name'              => sprintf( _x( '%s Categories', 'taxonomy general name', 'give' ), give_get_forms_label_singular() ),
		'singular_name'     => _x( 'Category', 'taxonomy singular name', 'give' ),
		'search_items'      => esc_html__( 'Search Categories', 'give' ),
		'all_items'         => esc_html__( 'All Categories', 'give' ),
		'parent_item'       => esc_html__( 'Parent Category', 'give' ),
		'parent_item_colon' => esc_html__( 'Parent Category:', 'give' ),
		'edit_item'         => esc_html__( 'Edit Category', 'give' ),
		'update_item'       => esc_html__( 'Update Category', 'give' ),
		/* translators: %s: form singular label */
		'add_new_item'      => sprintf( esc_html__( 'Add New %s Category', 'give' ), give_get_forms_label_singular() ),
		'new_item_name'     => esc_html__( 'New Category Name', 'give' ),
		'menu_name'         => esc_html__( 'Categories', 'give' ),
	);

	$category_args = apply_filters( 'give_forms_category_args', array(
			'hierarchical' => true,
			'labels'       => apply_filters( 'give_forms_category_labels', $category_labels ),
			'show_ui'      => true,
			'query_var'    => 'give_forms_category',
			'rewrite'      => array(
				'slug'         => $slug . '/category',
				'with_front'   => false,
				'hierarchical' => true
			),
			'capabilities' => array(
				'manage_terms' => 'manage_give_form_terms',
				'edit_terms'   => 'edit_give_form_terms',
				'assign_terms' => 'assign_give_form_terms',
				'delete_terms' => 'delete_give_form_terms'
			)
		)
	);

	//Does the user want categories?
	if ( give_get_option( 'enable_categories' ) == 'on' ) {
		register_taxonomy( 'give_forms_category', array( 'give_forms' ), $category_args );
		register_taxonomy_for_object_type( 'give_forms_category', 'give_forms' );
	}


	/** Tags */
	$tag_labels = array(
		/* translators: %s: form singular label */
		'name'                  => sprintf( _x( '%s Tags', 'taxonomy general name', 'give' ), give_get_forms_label_singular() ),
		'singular_name'         => _x( 'Tag', 'taxonomy singular name', 'give' ),
		'search_items'          => esc_html__( 'Search Tags', 'give' ),
		'all_items'             => esc_html__( 'All Tags', 'give' ),
		'parent_item'           => esc_html__( 'Parent Tag', 'give' ),
		'parent_item_colon'     => esc_html__( 'Parent Tag:', 'give' ),
		'edit_item'             => esc_html__( 'Edit Tag', 'give' ),
		'update_item'           => esc_html__( 'Update Tag', 'give' ),
		'add_new_item'          => esc_html__( 'Add New Tag', 'give' ),
		'new_item_name'         => esc_html__( 'New Tag Name', 'give' ),
		'menu_name'             => esc_html__( 'Tags', 'give' ),
		/* translators: %s: form singular label */
		'choose_from_most_used' => sprintf( esc_html__( 'Choose from most used %s tags.', 'give' ), give_get_forms_label_singular() ),
	);

	$tag_args = apply_filters( 'give_forms_tag_args', array(
			'hierarchical' => false,
			'labels'       => apply_filters( 'give_forms_tag_labels', $tag_labels ),
			'show_ui'      => true,
			'query_var'    => 'give_forms_tag',
			'rewrite'      => array( 'slug' => $slug . '/tag', 'with_front' => false, 'hierarchical' => true ),
			'capabilities' => array(
				'manage_terms' => 'manage_give_form_terms',
				'edit_terms'   => 'edit_give_form_terms',
				'assign_terms' => 'assign_give_form_terms',
				'delete_terms' => 'delete_give_form_terms'
			)
		)
	);

	if ( give_get_option( 'enable_tags' ) == 'on' ) {
		register_taxonomy( 'give_forms_tag', array( 'give_forms' ), $tag_args );
		register_taxonomy_for_object_type( 'give_forms_tag', 'give_forms' );
	}


}

add_action( 'init', 'give_setup_taxonomies', 0 );


/**
 * Get Default Form Labels
 *
 * @since 1.0
 * @return array $defaults Default labels
 */
function give_get_default_form_labels() {
	$defaults = array(
		'singular' => esc_html__( 'Form', 'give' ),
		'plural'   => esc_html__( 'Forms', 'give' )
	);

	return apply_filters( 'give_default_form_name', $defaults );
}

/**
 * Get Singular Forms Label
 *
 * @since 1.0
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 */
function give_get_forms_label_singular( $lowercase = false ) {
	$defaults = give_get_default_form_labels();

	return ( $lowercase ) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Forms Label
 *
 * @since 1.0
 * @return string $defaults['plural'] Plural label
 */
function give_get_forms_label_plural( $lowercase = false ) {
	$defaults = give_get_default_form_labels();

	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

/**
 * Change default "Enter title here" input
 *
 * @since 1.0
 *
 * @param string $title Default title placeholder text
 *
 * @return string $title New placeholder text
 */
function give_change_default_title( $title ) {
	// If a frontend plugin uses this filter (check extensions before changing this function)
	if ( ! is_admin() ) {
		$title = sprintf(
			/* translators: %s: form singular label */
			esc_html__( 'Enter %s title here', 'give' ),
			give_get_forms_label_singular()
		);

		return $title;
	}

	$screen = get_current_screen();

	if ( 'give_forms' == $screen->post_type ) {
		$title = sprintf(
			/* translators: %s: form singular label */
			esc_html__( 'Enter %s title here', 'give' ),
			give_get_forms_label_singular()
		);
	}

	return $title;
}

add_filter( 'enter_title_here', 'give_change_default_title' );

/**
 * Registers Custom Post Statuses which are used by the Payments
 *
 * @since 1.0
 * @return void
 */
function give_register_post_type_statuses() {
	// Payment Statuses
	register_post_status( 'refunded', array(
		'label'                     => _x( 'Refunded', 'payment status', 'give' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'give' )
	) );
	register_post_status( 'failed', array(
		'label'                     => _x( 'Failed', 'payment status', 'give' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'give' )
	) );
	register_post_status( 'revoked', array(
		'label'                     => _x( 'Revoked', 'payment status', 'give' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Revoked <span class="count">(%s)</span>', 'Revoked <span class="count">(%s)</span>', 'give' )
	) );
	register_post_status( 'cancelled', array(
		'label'                     => _x( 'Cancelled', 'payment status', 'give' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'give' )
	) );
	register_post_status( 'abandoned', array(
		'label'                     => _x( 'Abandoned', 'payment status', 'give' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Abandoned <span class="count">(%s)</span>', 'Abandoned <span class="count">(%s)</span>', 'give' )
	) );

}

add_action( 'init', 'give_register_post_type_statuses' );

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since 1.0
 *
 * @param array $messages Post updated message
 *
 * @return array $messages New post updated messages
 */
function give_updated_messages( $messages ) {
	global $post, $post_ID;

	$url1 = '<a href="' . get_permalink( $post_ID ) . '">';
	$url2 = give_get_forms_label_singular();
	$url3 = '</a>';

	$messages['give_forms'] = array(
		1 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'give' ), $url1, $url2, $url3 ),
		4 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'give' ), $url1, $url2, $url3 ),
		6 => sprintf( __( '%2$s published. %1$sView %2$s%3$s.', 'give' ), $url1, $url2, $url3 ),
		7 => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'give' ), $url1, $url2, $url3 ),
		8 => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'give' ), $url1, $url2, $url3 )
	);

	return $messages;
}

add_filter( 'post_updated_messages', 'give_updated_messages' );


/**
 * Setup Post Type Images
 */
add_action( 'after_setup_theme', 'give_add_thumbnail_support', 10 );

/**
 * Ensure post thumbnail support is turned on
 */
function give_add_thumbnail_support() {
	if ( give_get_option( 'disable_form_featured_img' ) === 'on' ) {
		return;
	}
	if ( ! current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails' );
	}
	add_post_type_support( 'give_forms', 'thumbnail' );
}

/**
 * Give Sidebars
 *
 * This option adds Give sidebars; registered late so it display last in list
 *
 */
function give_widgets_init() {

	//Single Give Forms (disabled if single turned off in settings)
	if ( give_get_option( 'disable_forms_singular' ) !== 'on' && give_get_option( 'disable_form_sidebar' ) !== 'on' ) {

		register_sidebar( apply_filters( 'give_forms_single_sidebar', array(
			'name'          => esc_html__( 'Give Single Form Sidebar', 'give' ),
			'id'            => 'give-forms-sidebar',
			'description'   => esc_html__( 'Widgets in this area will be shown on the single Give forms aside area. This sidebar will not display for embedded forms.', 'give' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widgettitle widget-title">',
			'after_title'   => '</h3>',
		) ) );

	}
}

add_action( 'widgets_init', 'give_widgets_init', 999 );
