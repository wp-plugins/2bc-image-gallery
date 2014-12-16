<?php
/**
 * Plugin Name: 2BC Image Gallery
 * Plugin URI: http://2bcoding.com/plugins/2bc-image-gallery
 * Description: Add tags to images and group them into galleries, easily set options to display the lightbox galleries, or use the shortcode
 * Version: 1.0.1
 * Author: 2bcoding
 * Author URI: http://2bcoding.com
 * License: GPL2
 */

/*
 * Copyright 2014  2BCoding  (email : info@2bcoding.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/************************************************
 * EXIT CONDITIONS
 ***********************************************/
if ( !defined( 'ABSPATH' ) ) {
	// Exit if accessed directly
	exit;
}

/************************************************
 * PLUGIN INIT
 ***********************************************/
// remember that this is set above in the plugin comments as well
// stored in the DB for upgrade check
$twobc_image_gallery_version = '1.0.1';

// default plugin values
$twobc_image_gallery_default_values = array (
	'gallery_page' => -1,
	'page_content' => 'after',
	'sort_method' => 'date',
	'sort_order' => 'DESC',
	'paginate_galleries' => '1',
	'images_per_page' => '60',
	'default_gallery_thumb' => 'last',
	'hide_style' => '0',
	'disable_ajax' => '0',
	'add_calendar' => '0',
	'separate_galleries' => '0',
	'show_month_galleries' => '0',
	'version' => $twobc_image_gallery_version,
);

add_action('plugins_loaded', 'twobc_image_gallery_plugins_loaded');
/**
 * Plugin bootstrap
 *		* Define constants
 *		* Load textdomain
 *
 * @action plugins_loaded
 */
function twobc_image_gallery_plugins_loaded() {
	//define constants for plugin use
	define('TWOBC_IMAGEGALLERY_URL', plugin_dir_url(__FILE__));
	define('TWOBC_IMAGEGALLERY_TEXT_DOMAIN', 'TwoBCImageGallery');
	
	load_plugin_textdomain(TWOBC_IMAGEGALLERY_TEXT_DOMAIN, false, basename(dirname(__FILE__)) . '/lang');
	
	// handle install and upgrade
	global $twobc_image_gallery_version;
	global $twobc_image_gallery_default_values;
	$plugin_options = twobc_image_gallery_get_options();
	// install check
	if ( empty($plugin_options) ) {
		// init with default values
		update_option('twobc_image_gallery_options', $twobc_image_gallery_default_values);
		$plugin_options = $twobc_image_gallery_default_values;
	}
	// upgrade check
	if ( $twobc_image_gallery_version != $plugin_options['version'] ) {
		// init any empty db fields to catch any new additions
		foreach ($twobc_image_gallery_default_values as $_name => $_value) {
			if ( !isset($plugin_options[$_name]) ) {
				$plugin_options[$_name] = $_value;
			}
		}
		// set the updated settings
		update_option('twobc_image_gallery_options', $plugin_options);
	}
}

add_action('admin_enqueue_scripts', 'twobc_image_gallery_admin_enqueue');
/**
 * Admin script enqueue functions - 2bc-image-gallery-admin.js
 * Adds Gallery Featured Image option 
 * 
 * @action admin_enqueue_scripts
 */
function twobc_image_gallery_admin_enqueue() {
	// only need to load JS on attachment pages
	global $typenow;
	
	if (
		!empty($typenow)
		&& 'attachment' == $typenow
	) {
		wp_enqueue_media();
		
		global $twobc_image_gallery_version;
		wp_register_script(
			'twobc_galleries_js_admin', // handle
			TWOBC_IMAGEGALLERY_URL . 'includes/js/2bc-image-gallery-admin.js', // path
			array ('jquery'), // dependencies
			$twobc_image_gallery_version // version
		);
		wp_localize_script(
			'twobc_galleries_js_admin', // script handle
			'meta_image', // variable name
			array ( // values to pass
				  'title' => __('Choose or Upload an Image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
				  'button' => __('Use this image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			)
		);
		wp_enqueue_script('twobc_galleries_js_admin');
	}
}

add_action('wp_enqueue_scripts', 'twobc_image_gallery_front_enqueue');
/**
 * Front script and CSS enqueue functions
 * 
 * @action wp_enqueue_scripts
 */
function twobc_image_gallery_front_enqueue() {
	$plugin_options = twobc_image_gallery_get_options();
	global $post;
	
	// scripts
	if (
		is_a($post, 'WP_Post')
		&& (
			( // media gallery page
				isset($plugin_options['gallery_page']) 
				&& -1 != $plugin_options['gallery_page']
				&& $post->ID == $plugin_options['gallery_page']
			)
			|| ( // shortcode found in post content
				has_shortcode($post->post_content, '2bc_image_gallery')
			)
		)
		&& ( // ajax is not disabled
			empty($plugin_options['disable_ajax'])
			|| '1' != $plugin_options['disable_ajax']
		)
	) {
		global $twobc_image_gallery_version;
		wp_register_script(
			'twobc_galleries_js_front', // handle
			TWOBC_IMAGEGALLERY_URL . 'includes/js/2bc-image-gallery-front.js', // source
			array ('jquery'), // dependencies
			$twobc_image_gallery_version, // version
			true // load in footer
		);
		
		// localize script
		$script_options = array (
			'gallery' => null,
			'page_num' => 1,
			'page_id' => get_the_ID(),
			'sort_method' => esc_attr($plugin_options['sort_method']),
			'sort_order' => esc_attr($plugin_options['sort_order']),
			'paginate_galleries' => esc_attr($plugin_options['paginate_galleries']),
			'images_per_page' => esc_attr($plugin_options['images_per_page']),
			'separate_galleries' => esc_attr($plugin_options['separate_galleries']),
			'show_months' => esc_attr($plugin_options['show_months']),
			'parents' => '',
		);
		wp_localize_script('twobc_galleries_js_front', 'script_options', $script_options);
		
		// localize for ajax
		$ajax_options = array (
			'ajax_url' => admin_url('admin-ajax.php'),
			'ajax_nonce' => wp_create_nonce('twobc_image_gallery_ajax'),
		);
		wp_localize_script('twobc_galleries_js_front', 'ajax_object', $ajax_options);
		wp_enqueue_script('twobc_galleries_js_front');
		
		wp_register_script(
			'twobc_galleries_js_picomodal', // handle
			TWOBC_IMAGEGALLERY_URL . 'includes/js/2bc-image-gallery-picomodal.js', // source
			array ('jquery'), // dependencies
			$twobc_image_gallery_version // version
		);
		wp_enqueue_script('twobc_galleries_js_picomodal');
	}
	
	// styles
	wp_register_style(
		'twobc_galleries_css_core', // handle
		TWOBC_IMAGEGALLERY_URL . 'includes/css/2bc-image-gallery-core.css' // url source
	);
	
	wp_enqueue_style('twobc_galleries_css_core');
	
	// cosmetic style
	if (
		empty($plugin_options['hide_style'])
		|| '1' != $plugin_options['hide_style']
	) {
		wp_register_style(
			'twobc_galleries_css_cosmetic', // handle
			TWOBC_IMAGEGALLERY_URL . 'includes/css/2bc-image-gallery-cosmetic.css' // url source
		);
		wp_enqueue_style('twobc_galleries_css_cosmetic');
	}
}

/***********************/
/*** CUSTOM TAXONOMY ***/
/***********************/
add_action('init', 'twobc_image_gallery_register_taxonomy');
/**
 * Register custom taxonomy - twobc_img_galleries
 * 
 * @action init
 * 
 * @see register_taxonomy()
 */
function twobc_image_gallery_register_taxonomy() {
	$tax_galleries_labels = apply_filters(
		'twobc_image_gallery_tax_labels', // filter name
		array( // filter argument
			'name' => _x('Galleries', 'taxonomy general name', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'singular_name' => _x('Gallery', 'taxonomy singular name', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'menu_name' => __('Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'search_items' => __('Search Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'all_items' => __('All Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'edit_item' => __('Edit Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'view_item' => __('View Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'update_item' => __('Update Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'add_new_item' => __('Add New Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'new_item_name' => __('New Gallery Name', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'popular_items' => __('Popular Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // non-hierarchical only
			'separate_items_with_commas' => __('Separate galleries with commas', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // non-hierarchical only
			'add_or_remove_items' => __('Add or remove galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // JS disabled, non-hierarchical only
			'choose_from_most_used' => __('Choose from the most used galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'not_found' => __('No galleries found', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	
	$tax_galleries_args = apply_filters(
		'twobc_image_gallery_tax_args', // filter name
		array( // filter argument
			'hierarchical' => false,
			'labels' => $tax_galleries_labels,
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'update_count_callback' => '_update_generic_term_count',
			'query_var' => true,
			'rewrite' => false,
			'sort' => false,
		)
	);
	
	register_taxonomy('twobc_img_galleries', array('attachment'), $tax_galleries_args);
}

/******************/
/*** ADMIN PAGE ***/
/******************/
add_action('admin_menu', 'twobc_image_gallery_settings_menu');
/**
 * Add 2BC Image Gallery options page
 * 
 * @action admin_menu 
 */
function twobc_image_gallery_settings_menu() {
	add_options_page(
		__('2BC Image Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // page title
		__('2BC Image Gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // menu title
		'manage_options', // capability required
		'twobc_imagegallery', // page slug
		'twobc_image_gallery_settings_page_cb' // display callback
	);
}

add_action('admin_init', 'twobc_image_gallery_admin_init');
/**
 * Register all plugin options on settings page
 * 
 * @action admin_init
 */
function twobc_image_gallery_admin_init() {

	// admin menu and pages
	register_setting(
		'twobc_image_gallery_options', // option group name, declared with settings_fields()
		'twobc_image_gallery_options', // option name, best to set same as group name
		'twobc_image_gallery_options_sanitize_cb' // sanitization callback
	);

	// SECTION - GENERAL
	$section = 'general';
	add_settings_section(
		'twobc_image_gallery_options_' . $section, // section HTML id
		__('Gallery Options', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // section title
		'twobc_image_gallery_options_general_cb', // display callback
		'twobc_imagegallery' // page to display on
	);
	// Gallery Page
	$field = 'gallery_page';
	$current_pages_args = array(
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
		'post_type' => 'page',
	);
	// get all current pages to populate dropdown
	$gallery_page_options = array (
		__('Select a page&hellip;', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => '-1'
	);
	$current_pages = get_posts($current_pages_args);
	if (!empty($current_pages) & !is_wp_error($current_pages)) {
		foreach ($current_pages as $page_obj) {
			$gallery_page_options[sanitize_text_field($page_obj->post_title . ' - ID: ' . $page_obj->ID)] = $page_obj->ID;
		}
	}
	add_settings_field(
		$field, // field HTML id
		__('Gallery Page', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_dropdown', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Select a page to display galleries on.  The shortcode <code class="language-markup">[2bc_image_gallery]</code> can be used for manual display instead of setting a page here.', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			  'options' => $gallery_page_options,
		)
	);
	// Gallery Page Content
	$field = 'page_content';
	add_settings_field(
		$field, // field HTML id
		__('Page Content', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_radio', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Control how page content is handled on the Gallery Page', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			  'options' => array (
				__('Before page content', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'before',
				__('Replace &#37;&#37;2bc_image_gallery&#37;&#37; template tag', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'templatetag',
				__('After page content', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'after',
				__('Replace all page content with gallery', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'replace',
			),
		)
	);
	// Sort Method
	$field = 'sort_method';
	add_settings_field(
		$field, // field HTML id
		__('Sort Method', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_dropdown', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array( // additional arguments
			 'name' => $field,
			 'description' => __('Select how gallery images are sorted: by <strong>uploaded date</strong>, alphbetically by <strong>filename</strong>, or <strong>random</strong>', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			 'options' => array(
				__('Date uploaded', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'date',
				__('Filename', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'title',
				__('Random', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'rand',
			),
		)
	);
	// Sort Order
	$field = 'sort_order';
	add_settings_field(
		$field, // field HTML id
		__('Sort Order', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_dropdown', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			'name' => $field,
			'description' => __('Which direction to sort, <strong>ascending</strong> (1, 2, 3) or <strong>descending</strong> (9, 8, 7)', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			'options' => array(
				__('Descending', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'desc',
				__('Ascending', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'asc',
			),
		)
	);
	// Paginate Galleries
	$field = 'paginate_galleries';
	add_settings_field(
		$field, // field HTML id
		__('Paginate Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Break up galleries into pages', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	// Images Per Page
	$field = 'images_per_page';
	add_settings_field(
		$field, // field HTML id
		__('Images Per Page', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_number', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('How many images to display per page, only applies if pagination is enabled', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	
	// Default Gallery Thumb
	$field = 'default_gallery_thumb';
	add_settings_field(
		$field, // field HTML id
		__('Gallery Thumb Source', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_dropdown', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('If the gallery does not have a custom thumbnail, decide how to generate one', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
			  'options' => array(
				__('Last image added', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'last',
				__('First image added', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'first',
				__('Random', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) => 'random',
			  ),
		)
	);

	// Hide Style
	$field = 'hide_style';
	add_settings_field(
		$field, // field HTML id
		__('Hide Default Stying', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Check to not load the default style sheet, and create the styling in the theme CSS instead', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	// Disable AJAX
	$field = 'disable_ajax';
	add_settings_field(
		$field, // field HTML id
		__('Disable AJAX', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Check to disable AJAX calls.  Do this if galleries are not loading when clicked.  This will mean that the page will refresh with the new content.', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	
	// SECTION - DATE GALLERIES
	$section = 'calendar';
	add_settings_section(
		'twobc_image_gallery_options_' . $section, // section HTML id
		__('Calendar Based Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // section title
		'twobc_image_gallery_options_general_cb', // display callback
		'twobc_imagegallery' // page to display on
	);
	// Add Calendar Galleries
	$field = 'add_calendar';
	add_settings_field(
		$field, // field HTML id
		__('Add Calendar Based Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Add calendar galleries (i.e. January, 2012) to uploaded images', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	// Separate Calendar Galleries
	$field = 'separate_galleries';
	add_settings_field(
		$field, // field HTML id
		__('Separate Calendar Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Show calendar galleries in their own separate section', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
	// Show Month Galleries
	$field = 'show_months';
	add_settings_field(
		$field, // field HTML id
		__('Show Month Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // field title
		'twobc_image_gallery_create_checkbox', // display callback
		'twobc_imagegallery', // page to display on
		'twobc_image_gallery_options_' . $section, // section to display in
		array ( // additional arguments
			  'name' => $field,
			  'description' => __('Display month-based galleries on the main page, only applies if <strong>Separate Calendar Galleries</strong> is checked', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
		)
	);
}



/**
 * CSS for admin options page
 * 
 * @return string
 */
function twobc_image_gallery_get_admin_css() {
	$output = '';

	return $output;
}

/**
 * Javascript for admin options page
 * 
 * @return string
 */
function twobc_image_gallery_get_admin_js() {
	$output = '';

	return $output;
}

/**
 * 2BC Image Gallery options callback
 */
function twobc_image_gallery_settings_page_cb() {
	//must check that the user has the required capability
	if ( !current_user_can('manage_options' )) {
		wp_die(__('You do not have sufficient permissions to access this page.', TWOBC_IMAGEGALLERY_TEXT_DOMAIN));
	}

	$image_gallery_admin_css = twobc_image_gallery_get_admin_css();
	if ( !empty($image_gallery_admin_css) ) {
		echo '<style>' . $image_gallery_admin_css . '</style>
';
	}

	echo '<div id="twobc_image_gallery_options_wrap" class="wrap">
';
	settings_errors(
		'twobc_image_gallery_options', // settings group name
		false, // re-sanitize values on errors
		true // hide on update - set to true to get rid of duplicate Updated messages
	);
	echo '	<h2>' . __('2BC Image Gallery Options', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) . '</h2>
';
	echo '	<p>';
	_e('More help available at the <a href="http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-documentation/" target="_blank" ref="nofollow">2BC Image Gallery documentation page</a>.', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	echo '</p>
';
	echo '	<form method="post" action="options.php">
';

	settings_fields('twobc_image_gallery_options');
	do_settings_sections('twobc_imagegallery');

	submit_button();

	echo '	</form>
';
	echo '</div>
';

	$image_gallery_js = twobc_image_gallery_get_admin_js();
	if (!empty($image_gallery_js)) {
		echo '<script>' . $image_gallery_js . '</script>
';
	}

}

/**
 * Options page header callback
 */
function twobc_image_gallery_options_general_cb() {
	// intentionally left blank
}

/**
 * Sanitize option values callback
 * 
 * @param $saved_settings
 *
 * @return mixed
 */
function twobc_image_gallery_options_sanitize_cb($saved_settings) {
	$settings_errors = array (
		'updated' => false,
		'error' => array (),
	);

	$known_fields = array(
		'gallery_page' => 'dropdown',
		'page_content' => 'radio',
		'sort_method' => 'dropdown',
		'sort_order' => 'dropdown',
		'paginate_galleries' => 'checkbox',
		'images_per_page' => 'number',
		'default_gallery_thumb' => 'dropdown',
		'hide_style' => 'checkbox',
		'disable_ajax' => 'checkbox',
		'add_calendar' => 'checkbox',
		'separate_galleries' => 'checkbox',
		'show_months' => 'checkbox',
	);

	foreach ($saved_settings as $setting_key => $setting_val) {
		// security checks - nonce, capability
		if (
			isset($known_fields[$setting_key])
			&& check_admin_referer(
				'twobc_image_gallery_options_nonce', // nonce action
				'twobc_image_gallery_options_' . $setting_key // query arg, nonce name
			)
			&& current_user_can('manage_options')
		) {
			switch ($known_fields[$setting_key]) {
				case 'checkbox' :
					$saved_settings[$setting_key] = '1';
					$settings_errors['updated'] = true;

					break;
				
				case 'number' :
					if ( is_numeric($setting_val) ) {
						$saved_settings[$setting_key] = intval($setting_val);
						$settings_errors['updated'] = true;
					} else {
						unset($saved_settings[$setting_key]);
					}
					break;
				
				case 'dropdown' :
				case 'radio' :
					$saved_settings[$setting_key] = sanitize_text_field($setting_val);
					$settings_errors['updated'] = true;
					
					break;

				default :
					// unknown field type?  Shouldn't happen, but unset to be safe
					unset($saved_settings[$setting_key]);
			}
		} else { // unknown field? unset to be safe
			unset($saved_settings[$setting_key]);
		}
	}
	// separate validation for un-checked checkboxes
	foreach ($known_fields as $field_name => $field_type) {
		if (
			'checkbox' == $field_type
			&& !isset($saved_settings[$field_name])
		) {
			$saved_settings[$field_name] = '0';
			$settings_errors['updated'] = true;
		}
	}

	// register errors
	if (!empty($settings_errors['errors']) && is_array($settings_errors['errors'])) {
		foreach ($settings_errors['errors'] as $error) {
			add_settings_error(
				'twobc_image_gallery_options', // Slug title of the setting
				'twobc_image_gallery_options_error', // Slug of error
				sprintf(__('%s', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), $error), // Error message
				'error' // Type of error (**error** or **updated**)
			);
		}
	}
	if (true === $settings_errors['updated']) {
		add_settings_error(
			'twobc_image_gallery_options', // Slug title of the setting
			'twobc_image_gallery_options_error', // Slug of error
			__('Settings saved.', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), // Error message
			'updated' // Type of error (**error** or **updated**)
		);
	}

	// set plugin version number
	global $twobc_image_gallery_version;
	$saved_settings['version'] = $twobc_image_gallery_version;

	return $saved_settings;
}

/**
 * Create HTML checkbox for options page
 * 
 * @param $args
 */
function twobc_image_gallery_create_checkbox($args) {
	$field_value = twobc_image_gallery_get_options();
	$field_value = (isset($field_value[$args['name']]) && '1' == $field_value[$args['name']] ? '1' : null);

	// field nonce
	wp_nonce_field(
		'twobc_image_gallery_options_nonce', // action
		'twobc_image_gallery_options_' . $args['name'] // custom name
	);
	
	echo '<fieldset>
';
	echo '<input type="checkbox" id="' . $args['name'] . '" name="twobc_image_gallery_options[' . $args['name'] . ']"';
	echo ' value="1"';
	if ('1' == $field_value) {
		echo ' checked="checked"';
	}
	echo ' class="tog">
';
	echo '</fieldset>
';

	if (!empty($args['description'])) {
		echo '<p class="description">' . $args['description'] . '</p>
';
	}

}

/**
 * Create HTML dropdown for options page
 * 
 * @param $args
 */
function twobc_image_gallery_create_dropdown($args) {
	$field_value = twobc_image_gallery_get_options();
	$field_value = (isset($field_value[$args['name']]) ? sanitize_text_field($field_value[$args['name']]) : null);
	
	// field nonce
	wp_nonce_field(
		'twobc_image_gallery_options_nonce', // action
		'twobc_image_gallery_options_' . $args['name'] // custom name
	);
	
	echo '<select';
	echo ' id="' . $args['name'] . '"';
	echo ' name="twobc_image_gallery_options[' . $args['name'] . ']"';
	
	
	echo '>
';
	if (!empty($args['options'])) {
		foreach ($args['options'] as $opt_name => $opt_value) {
			echo '<option';
			
			// placeholder
			if ('placeholder' == $opt_value) {
				echo ' value="-1"';
				echo ' disabled="disabled"';
				
				if (
					empty($field_value)
					|| '-1' == $field_value
				) {
					echo ' selected="selected"';
				}
			} else {
				echo ' value="' . sanitize_text_field($opt_value) . '"';
			}
			
			if ($field_value == $opt_value) {
				echo ' selected="selected"';
			}
			echo '>';
			
			echo sanitize_text_field($opt_name);
			
			echo '</option>
';
		}
	}
	
	
	echo '</select>
';
	if (!empty($args['description'])) {
		echo '<p class="description">' . $args['description'] . '</p>
';
	}
}

/**
 * Create HTML radio input for options page
 * 
 * @param $args
 */
function twobc_image_gallery_create_radio($args) {

	$field_value = twobc_image_gallery_get_options();
	$field_value = (isset($field_value[$args['name']]) ? sanitize_text_field($field_value[$args['name']]) : null);
	
	// field nonce
	wp_nonce_field(
		'twobc_image_gallery_options_nonce', // action
		'twobc_image_gallery_options_' . $args['name'] // custom name
	);
	
	if ( !empty($args['options']) ) {
		if (!empty($args['description'])) {
			echo '<p class="description">' . $args['description'] . '</p>
';
		}
		
		echo '<fieldset>
';
		foreach ($args['options'] as $opt_name => $opt_value) {
			echo '	<label for="' . $args['name'] . '_' . $opt_value . '">';
			echo '<input type="radio"';
			echo ' id="' . $args['name'] . '_' . $opt_value . '"';
			echo ' name="twobc_image_gallery_options[' . $args['name'] . ']"';
			echo ' value="' . esc_attr($opt_value) . '"';
			
			if ( $field_value == $opt_value ) {
				echo ' checked="checked"';
			}
			
			echo '>';			
			echo '<span>' . sanitize_text_field($opt_name) . '</span>';
			echo '</label><br>
';
		}
		
		echo '</fieldset>
';
	}
}

/**
 * Create HTML number for options page
 * 
 * @param $args
 */
function twobc_image_gallery_create_number($args) {
	$field_value = twobc_image_gallery_get_options();
	$field_value = (isset($field_value[$args['name']]) ? intval($field_value[$args['name']]) : null);
	
	// field nonce
	wp_nonce_field(
		'twobc_image_gallery_options_nonce', // action
		'twobc_image_gallery_options_' . $args['name'] // custom name
	);
	
	echo '<input type="number" id="' . $args['name'] . '" name="twobc_image_gallery_options[' . $args['name'] . ']"';
	echo ' value="' . $field_value . '"';
	echo '>
';
	if (!empty($args['description'])) {
		echo '<p class="description">' . $args['description'] . '</p>
';
	}
}

add_action('admin_notices', 'twobc_image_gallery_admin_notices');
/**
 * Add admin notice to 2BC Image Gallery page
 * 
 * @action admin_notices
 */
function twobc_image_gallery_admin_notices() {
	$image_gallery_options = twobc_image_gallery_get_options();
	global $post;
	$current_screen = get_current_screen();
	if (
		isset($image_gallery_options['gallery_page'])
		&& 'page' == $current_screen->id
		&& 'edit' == $current_screen->parent_base
		&& $post->ID == $image_gallery_options['gallery_page']
	) {
		echo '<div class="updated">
	<p>
';
		_e('This page is currently being used to display the <strong>2BC Media Gallery</strong>', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
		echo '
	</p>
</div>
';
	}
}

/***************************/
/*** TAXONOMY ADMIN PAGE ***/
/***************************/
add_action('twobc_img_galleries_add_form_fields', 'twobc_image_gallery_taxonomy_fields_add', 10, 2);
/**
 * Add custom fields to Add Gallery page - featured gallery image picker
 * 
 * @action twobc_img_galleries_add_form_fields
 */
function twobc_image_gallery_taxonomy_fields_add() {
	echo '<div class="form-field">
	<label for="twobc_img_galleries_meta[gallery_featured_img]">';
	_e( 'Gallery Featured Image ID', TWOBC_IMAGEGALLERY_TEXT_DOMAIN );
	echo '</label>
';
	
	echo '	<input type="text" name="twobc_img_galleries_meta[gallery_featured_img]" id="twobc_img_galleries_meta[gallery_featured_img]" value="">
';
	
	echo '	<input type="button" id="twobc-image-gallery-featured-image-button" class="button" value="';
	_e('Choose or Upload an Image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	echo '"';
	echo '>
';
	
	echo '	<p class="description">';
	_e('Choose or upload a picture to be the galleries featured image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	echo '</p>
';
	
	echo '</div>
';
}

add_action('twobc_img_galleries_edit_form_fields', 'twobc_image_gallery_taxonomy_fields_edit', 10, 2);
/**
 * Add custom fields to Edit Gallery page - featured gallery image picker
 * 
 * @param $term
 * 
 * @action twobc_img_galleries_edit_form_fields
 */
function twobc_image_gallery_taxonomy_fields_edit($term) {
	// retrieve the existing value(s) for this meta field. This returns an array
	$term_meta = get_option( 'taxonomy_' . $term->term_id );
	
	echo '<tr class="form-field">
	<th scope="row" valign="top"><label for="twobc_img_galleries_meta[gallery_featured_img]">';
	_e('Gallery Featured Image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	echo '</label></th>
';
	
	echo '	<td>';
	echo '		<input type="text" name="twobc_img_galleries_meta[gallery_featured_img]" id="twobc_img_galleries_meta[gallery_featured_img]"';
	echo ' value="';
	if (!empty($term_meta['gallery_featured_img'])) {
		echo esc_attr($term_meta['gallery_featured_img']);
	}
	echo '"';
	echo '>
';
	
	echo '		<input type="button" id="twobc-image-gallery-featured-image-button" class="button" value="';
	_e( 'Choose or Upload an Image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	echo '"';
	echo '>
';
	
	echo '		<p class="description">';
	_e( 'Choose or upload a picture to be the galleries featured image', TWOBC_IMAGEGALLERY_TEXT_DOMAIN );
	echo '</p>
';
	
	echo '	</td>
';
	echo '</tr>
';
}

add_action('edited_twobc_img_galleries', 'twobc_image_gallery_taxonomy_fields_save', 10, 2);
add_action('created_twobc_img_galleries', 'twobc_image_gallery_taxonomy_fields_save', 10, 2);
/**
 * Save custom fields on Gallery pages
 * 
 * @param $term_id
 * 
 * @action edited_twobc_img_galleries, created_twobc_img_galleries
 */
function twobc_image_gallery_taxonomy_fields_save($term_id) {
	if ( isset($_POST['twobc_img_galleries_meta']) ) {
		$term_meta = (get_option('taxonomy_' . $term_id));
		$tax_keys = array_keys($_POST['twobc_img_galleries_meta']);
		foreach ($tax_keys as $a_key) {
			$term_meta[esc_attr($a_key)] = esc_attr($_POST['twobc_img_galleries_meta'][$a_key]);
		}
		
		// save the option array
		update_option('taxonomy_' . $term_id, $term_meta);
	}
}

add_filter('manage_edit-twobc_img_galleries_columns', 'twobc_image_gallery_taxonomy_columns');
/**
 * Set custom, sortable ID column on taxonomy page - add the custom column
 * 
 * @param $columns
 * 
 * @filter manage_edit-twobc_img_galleries_columns
 *
 * @return mixed
 */
function twobc_image_gallery_taxonomy_columns($columns) {
	$columns['id'] = __('ID', TWOBC_IMAGEGALLERY_TEXT_DOMAIN);
	
	return $columns;
}


add_action('manage_twobc_img_galleries_custom_column', 'twobc_image_gallery_taxonomy_id_column', 10, 3);
/**
 * Set custom, sortable ID column on taxonomy page - display the custom column contents
 * 
 * @param $value
 * @param $col_name
 * @param $id
 * 
 * @action manage_twobc_img_galleries_custom_column
 *
 * @return int
 */
function twobc_image_gallery_taxonomy_id_column($value, $col_name, $id) {
	if ( 'id' == $col_name ) {
		return intval($id);
	}
	
	return null;
}

add_filter('manage_edit-twobc_img_galleries_sortable_columns', 'twobc_image_gallery_taxonomy_sortable_columns');
/**
 * Set custom, sortable ID column on taxonomy page - set custom column as sortable
 * 
 * @param $columns
 * 
 * @filter manage_edit-twobc_img_galleries_sortable_columns
 *
 * @return mixed
 */
function twobc_image_gallery_taxonomy_sortable_columns($columns) {
	$columns['id'] = 'id';
	
	return $columns;
}


/***************/
/*** DISPLAY ***/
/***************/
add_filter('the_content', 'twobc_image_gallery_page_filter');
/**
 * Filter content of Image Gallery page to display galleries
 * 
 * @param $content
 * 
 * @filter the_content
 *
 * @return string
 */
function twobc_image_gallery_page_filter($content) {
	$plugin_options = twobc_image_gallery_get_options();
	
	if (
		isset($plugin_options['gallery_page'])
		&& '-1' != $plugin_options['gallery_page']
		&& is_page($plugin_options['gallery_page'])
		&& isset($plugin_options['page_content'])
	) {
		switch ($plugin_options['page_content']) {
			// before
			case 'before' :
				$content = twobc_image_gallery_get_display() . $content;
				break;
			
			// replace
			case 'replace' :
				$content = twobc_image_gallery_get_display();
				break;
			
			// templatetag - %%2bc_image_gallery%%
			case 'templatetag' :
				$limit = 1;
				$content = str_replace(
					'%%2bc_image_gallery%%', // needle
					twobc_image_gallery_get_display(), // replacement
					$content, // haystack
					$limit // limit
				);
				break;
			
			// after
			// default
			case 'after' :
			default :
				$content .= twobc_image_gallery_get_display();
		}
	}
	
	return $content;
}

/**
 * Get gallery display HTML
 * 
 * @param array $args
 *
 * @return string
 * 
 * @uses twobc_image_gallery_get_thumb_html()
 */
function twobc_image_gallery_get_display($args = array()) {
	$output = '';
	// get the plugin options
	$image_gallery_options = twobc_image_gallery_get_options();
	
	$default_args = array(
		'display_gallery' => '',
		'page_num' => '1',
		'page_id' => get_the_ID(),
		'parents' => '',
		'galleries' => '',
		'page_content' => $image_gallery_options['page_content'],
		'sort_method' => $image_gallery_options['sort_method'],
		'sort_order' => $image_gallery_options['sort_order'],
		'paginate_galleries' => $image_gallery_options['paginate_galleries'],
		'images_per_page' => $image_gallery_options['images_per_page'],
		'default_gallery_thumb' => $image_gallery_options['default_gallery_thumb'],
		'separate_galleries' => $image_gallery_options['separate_galleries'],
		'show_months' => $image_gallery_options['show_months'],
		'back_button' => '',
		'noajax' => '',
	);
	
	// set args over-ride flag
	$args_override = false;
	if ( !empty($args) ) {
		$args_override = $args;
	}
	
	// set GET over-ride flag
	if ( !empty($_GET) ) {
		foreach ($_GET as $_get_name => $_get_value) {
			$get_name = esc_attr($_get_name);
			$get_value = esc_attr($_get_value);
			
			if ( isset($default_args[$get_name]) ) {
				$args_override[$get_name] = $get_value;
				$args[$get_name] = $get_value;
			}
		}
	}
	
	$args = wp_parse_args($args, $default_args);
	
	// optional nested galleries - build array from args if present
	$parent_galleries = array ();
	if (
		!empty($args['parents'])
		&& is_string($args['parents'])
	) {
		$parent_galleries = explode(',', $args['parents']);
		if ( 1 == count($parent_galleries )) {
			$parent_galleries = reset($parent_galleries);
		}
	}
	
	// shortcut to display a gallery - for non-JS cases
	if (
		isset($args['display_gallery'])
		&& is_numeric($args['display_gallery'])
	) {
		$shortcut_args = array(
			'page_id' => get_the_ID(),
			'term_id' => $args['display_gallery'],
			'page_num' => $args['page_num'],
			'sort_order' => $args['sort_order'],
			'sort_method' => $args['sort_method'],
			'paginate_galleries' => $args['paginate_galleries'],
			'images_per_page' => $args['images_per_page'],
			'back_button' => $args['back_button'],
		);
		$shortcut_args['term_title'] = get_term($shortcut_args['term_id'], 'twobc_img_galleries');
		$shortcut_args['term_title'] = $shortcut_args['term_title']->name;
		$shortcut_args['parents'] = $args['parents'];
		
		$output = '<div class="twobc_image_gallery_universal_wrapper">
';
		$output .= '	<div class="twobc_image_gallery_loading"></div>
';
		$output .= '	<div class="twobc_image_gallery_overlay_wrapper show_gallery">'.twobc_image_gallery_get_gallery_html($shortcut_args).'</div>
';
		$output .= '	<div class="twobc_image_gallery_wrapper categories_wrapper';
		if ( false !== $args_override ) {
			foreach ($args_override as $opt_name => $opt_val) {
				switch ($opt_name) {
					case 'sort_method' :
					case 'sort_order' :
					case 'paginate_galleries' :
					case 'images_per_page' :
					case 'separate_galleries' :
					case 'show_months' :
						$output .= ' ' . sanitize_html_class($opt_name . '_' . $opt_val);
						break;
					
					case 'parents' :
						$output .= ' ' . sanitize_html_class($opt_name . '_');
						$parent_classes = str_replace(',', '_', $opt_val);
						$parent_classes = str_replace(' ', '', $parent_classes);
						$output .= sanitize_html_class($parent_classes);
						break;
					
					case 'noajax' :
						$output .= ' noajax';
						break;
					
					default :
				}
			}
		}
		
		$output .= '"></div>
';		
		$output .= '</div><!-- .twobc_image_gallery_universal_wrapper -->
';
		
		return $output;
	}
	
	// find out if we were passed galleries to display
	$optional_galleries = array();
	if (
		!empty($args['galleries'])
		&& is_string($args['galleries'])
	) {
		$optional_galleries = explode(',', $args['galleries']);
		
		if (
			!empty($optional_galleries) 
			&& is_array($optional_galleries)
		) {
			foreach ($optional_galleries as &$gallery_id) {
				$gallery_id = esc_attr(trim($gallery_id));
			}
		}
	}
	
	$get_terms_args = array(
		'cache_domain' => 'twobc_image_gallery',
	);
	
	if ( !empty($optional_galleries) ) {
		foreach ($optional_galleries as $a_term_id) {
			$get_terms_args['include'][] = $a_term_id;
		}
	}
	
	// get all current terms
	$term_array = get_terms('twobc_img_galleries', $get_terms_args);
	
	// Begin display
	$output .= '<div class="twobc_image_gallery_universal_wrapper';
	$output .= '">
';
	
	$output .= '	<div class="twobc_image_gallery_loading"></div>
';
	
	$output .= '	<div class="twobc_image_gallery_overlay_wrapper hide_gallery"></div>
';
	
	if (!empty($term_array)) {
		$output .= '	<div class="twobc_image_gallery_wrapper categories_wrapper';
		// add any overrides
		if (false !== $args_override) {
			foreach ($args_override as $opt_name => $opt_val) {
				switch ($opt_name) {
					case 'sort_method' :
					case 'sort_order' :
					case 'paginate_galleries' :
					case 'images_per_page' :
					case 'separate_galleries' :
					case 'show_months' :
						$output .= ' ' . sanitize_html_class($opt_name . '_' . $opt_val);
						break;
					
					case 'parents' :
						$output .= ' ' . sanitize_html_class($opt_name. '_');
						$parent_classes = str_replace(',', '_', $opt_val);
						$parent_classes = str_replace(' ', '', $parent_classes);
						$output .= sanitize_html_class($parent_classes);
						break;
					
					case 'noajax' :
						$output .= ' noajax';
						break;
				
					default :
				}
			}
		}
		$output .= '">
';
		
		$custom_div_open = false;
		// if separate calendar galleries is active
		if ( 
			isset($args['separate_galleries'])
			&& '1' == $args['separate_galleries'] 
		) {
			// get all year-based galleries into their own container
			$years_output = array();
			foreach ($term_array as $key => $term_obj) {
				// if name matches a 4 digit number, between 1900 and 2099
				if ( 1 == preg_match('/^(19|20)\d{2}$/', $term_obj->name) ) {
					// output this term
					$years_output[] = '			' . twobc_image_gallery_get_thumb_html($term_obj, $args);
					
					// remove from $term_array
					unset($term_array[$key]);
				}
			}
			
			if ( !empty($years_output) ) {
				// add opening html
				array_unshift($years_output, '		<div class="twobc_image_gallery_years">
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_year_title', __('Galleries by year', TWOBC_IMAGEGALLERY_TEXT_DOMAIN)) . '</p>
');
				// add closing html
				$years_output[] = '		</div><!-- .twobc_image_gallery_years -->
';
				
				foreach ($years_output as $_output) {
					$output .= $_output;
				}
			}
			
			if (
				isset($args['show_months'])
				&& '1' == $args['show_months']
			) {
				$display_months = true;
			} else {
				$display_months = false;
			}
			
			// build array of calendar months
			$calendar_months = apply_filters(
				'twobc_image_gallery_calendar_months', // filter name
				array ( // filter argument
					__('January', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('February', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('March', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('April', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('May', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('June', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('July', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('August', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('September', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('October', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('November', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
					__('December', TWOBC_IMAGEGALLERY_TEXT_DOMAIN),
				)
			);
			
			$month_output = array();
			
			foreach ($term_array as $_key => $_term_obj) {
				// if name matches a calendar month
				foreach ($calendar_months as $_month_key => $_month_name) {
					if ($_term_obj->name == $_month_name) {
						if ($display_months) {
							// store this entry in the correct order
							$month_output[$_month_key] = twobc_image_gallery_get_thumb_html($_term_obj, $args);
						}
						// remove from $term_array
						unset($term_array[$_key]);
					}
				}
			}
			
			if (
				!empty($month_output)
				&& $display_months
			) {
				// sort array by key
				ksort($month_output, SORT_NUMERIC);
				
				// add opening html
				array_unshift($month_output, '		<div class="twobc_image_gallery_months">
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_month_title', __('Galleries by month', TWOBC_IMAGEGALLERY_TEXT_DOMAIN)) . '</p>
');
				// add closing html
				$month_output[] = '		</div><!-- .twobc_image_gallery_months -->
';
				
				foreach ($month_output as $_output) {
					$output .= $_output;
				}
			}
			
			// check to see if we have any custom galleries left to display
			// for custom section title
			if ( !empty($term_array) ) {
				$custom_div_open = true;
				$output .= '		<div class="twobc_image_gallery_custom">
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_custom_title', __('Custom Galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN)) . '</p>
';
			}
		}
		
		// standard output, double check we still have terms
		if ( false === $custom_div_open ) {
			$output .= '		<div class="twobc_image_gallery_custom">
';
		}
		if ( !empty($term_array) ) {
			foreach ($term_array as $_term_obj) {
				$display_gallery = true;
				
				if ( !empty($parent_galleries) ) {
					$display_gallery = twobc_image_gallery_contains_like_terms($_term_obj->term_id, $parent_galleries);
				}
				
				if ( $display_gallery ){
					$output .= '	' . twobc_image_gallery_get_thumb_html($_term_obj, $args);
				}
			}
			$output .= '	</div><!-- .twobc_image_gallery_custom -->
';
		}

	} else {
		$output .= '	<p>' . __('No galleries to display!', TWOBC_IMAGEGALLERY_TEXT_DOMAIN) . '</p>
';
	}
		$output .= '	</div><!-- .twobc_image_gallery_wrapper -->
</div><!-- .twobc_image_gallery_universal_wrapper -->
';
	
	return apply_filters('twobc_image_gallery_output_list', $output);
}

/**
 * Get image thumb HTML
 * 
 * @param $term_obj
 *
 * @return string
 */
function twobc_image_gallery_get_thumb_html($term_obj, $passed_args = array()) {
	$output = '';
	
	$plugin_options = twobc_image_gallery_get_options();
	
	// try to get gallery featured image
	$cat_thumb_id = get_option('taxonomy_' . $term_obj->term_id);
	$cat_thumb_id = (!empty($cat_thumb_id['gallery_featured_img']) ? $cat_thumb_id['gallery_featured_img'] : null);
	
	// if no image present, get an image according to the plugin settings - first, last, random
	if ( empty($cat_thumb_id) ) {
		$orderby = 'date';
		$order = 'DESC';
		if ( !empty($plugin_options['default_gallery_thumb']) ) {
			switch ($plugin_options['default_gallery_thumb']) {
				case 'first' :
					$order = 'ASC';
					break;
				
				case 'random' :
					$orderby = 'rand';
					break;
				
				case 'last' :
				default :
			}
		}
		$get_posts_args = array (
			'posts_per_page' => 1,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'tax_query' => array (
				array (
					'taxonomy' => 'twobc_img_galleries',
					'terms' => $term_obj->term_id,
				),
			),
			'order' => $order,
			'orderby' => $orderby,
		);
		
		// update - filter thumb by parents, if present
		if ( !empty($passed_args['parents']) ) {
			$parents = explode(',', $passed_args['parents']);
			if ( !empty($parents) ) {
				$get_posts_args['tax_query']['relation'] = 'AND';
				foreach ($parents as $_parent) {
					if ( !empty($_parent) ) {
						$get_posts_args['tax_query'][] = array (
							'taxonomy' => 'twobc_img_galleries',
							'field' => 'id',
							'terms' => trim($_parent),
						);
					}
				}
			}
		}
		
		
		$cat_thumb_id = get_posts($get_posts_args);
		if (!empty($cat_thumb_id)) {
			$cat_thumb_id = reset($cat_thumb_id);
			$cat_thumb_id = $cat_thumb_id->ID;
		} else {
			$cat_thumb_id = -1;
		}
	}
	
	if ( -1 != $cat_thumb_id ) {
		// get attachment details
		$cat_thumb_obj = wp_get_attachment_image_src($cat_thumb_id, 'thumbnail');
		
		// build output
		$output .= '<a';
		global $post;
		$output .= ' href="' . twobc_image_gallery_get_gallery_url(
				$post->ID, // page id
				array(
					'display_gallery' => esc_attr($term_obj->term_id),
				)
			) . '"';
		$output .= ' class="thumb_wrapper';
		$output .= ' gallery_' . esc_attr($term_obj->term_id);
		$output .= '"';
		// output width in style tag here
		// get current thumb size
		$current_thumb_size = twobc_image_gallery_get_image_sizes('thumbnail');
		if ( !empty($current_thumb_size) ) {
			$output .= ' style="';
			$output .= 'width:' . $current_thumb_size['width'] . 'px';
			$output .= '"';
		}
		
		$output .= '>';
		$output .= '<img src="' . esc_url($cat_thumb_obj[0]) . '" height="' . esc_attr($cat_thumb_obj[1]) . '" width="' . esc_attr($cat_thumb_obj[2]) . '" alt="Thumbnail for ' . esc_attr($term_obj->name) . ' gallery">';
		$output .= '<span class="tax_title"> ' . esc_html($term_obj->name) . ' </span>';
		$output .= '</a>
';
	}
	
	return apply_filters('twobc_image_gallery_output_thumb', $output);
}

add_action('wp_ajax_twobc_image_gallery_generate', 'twobc_image_gallery_ajax_callback');
add_action('wp_ajax_nopriv_twobc_image_gallery_generate', 'twobc_image_gallery_ajax_callback');
/**
 * AJAX callback for image gallery display
 */
function twobc_image_gallery_ajax_callback() {
	// verify nonce
	check_ajax_referer('twobc_image_gallery_ajax', 'ajax_nonce');
	
	$output = '';
	$args = array();
	
	$args['page_id'] = esc_attr($_POST['page_id']);
	$args['term_id'] = esc_attr($_POST['gallery']);
	
	if (!empty($args['term_id'])) {
		
		$args['term_title'] = get_term($args['term_id'], 'twobc_img_galleries');
		$args['term_title'] = $args['term_title']->name;
	
		$args['page_num'] = intval(esc_attr($_POST['page_num']));
		
		$args['sort_order'] = esc_attr($_POST['sort_order']);
		$args['sort_method'] = esc_attr($_POST['sort_method']);
		$args['paginate_galleries'] = esc_attr($_POST['paginate_galleries']);
		$args['images_per_page'] = esc_attr($_POST['images_per_page']);
		$args['back_button'] = esc_attr($_POST['back_button']);
		if ( !empty($_POST['parents']) ) {
			$args['parents'] = esc_attr($_POST['parents']);
		}
	}
	
	$output .= twobc_image_gallery_get_gallery_html($args);
	
	echo apply_filters('twobc_image_gallery_output_ajax', $output);
	die(); // this is required to terminate immediately and return a proper response
}

/**
 * Get HTML display when viewing a gallery's contents
 * 
 * @param $args
 *
 * @return string
 */
function twobc_image_gallery_get_gallery_html($args) {
	$output = '';
	
	$plugin_options = twobc_image_gallery_get_options();
	
	$default_args = array(
		'page_id' => '-1',
		'term_id' => '-1',
		'term_title' => '',
		'page_num' => '1',
		'sort_order' => $plugin_options['sort_order'],
		'sort_method' => $plugin_options['sort_method'],
		'paginate_galleries' => $plugin_options['paginate_galleries'],
		'images_per_page' => $plugin_options['images_per_page'],
		'parents' => '',
		'back_button' => '',
	);
	
	$args = wp_parse_args($args, $default_args);
	
	if ( '-1' != $args['term_id'] ) {
		
		// pagination options
		if (
			'1' == $args['paginate_galleries']
			&& is_numeric($args['images_per_page'])
		) {
			$args['images_per_page'] = intval($args['images_per_page']);
			// calculate offset
			$offset = ($args['page_num'] - 1) * $args['images_per_page'];
		} else {
			$args['images_per_page'] = -1;
		}
		$get_posts_args = array (
			'posts_per_page' => $args['images_per_page'],
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'tax_query' => array (
				array (
					'taxonomy' => 'twobc_img_galleries',
					'field' => 'id',
					'terms' => $args['term_id'],
				),
			),
			'orderby' => $args['sort_method'],
			'order' => strtoupper($args['sort_order']),
		);
		// optional arguments
		if ( !empty($offset )) {
			$get_posts_args['offset'] = $offset;
		}
		if ( !empty($args['parents']) ) {
			$get_posts_args['tax_query']['relation'] = 'AND';
			$parents = explode(',', $args['parents']);
			if ( !empty($parents) ) {
				foreach ($parents as $_parent) {
					$get_posts_args['tax_query'][] = array(
						'taxonomy' => 'twobc_img_galleries',
						'field' => 'id',
						'terms' => trim($_parent),
					);
				}
			}
		}
		
		$gallery_images = get_posts($get_posts_args);
		if (!empty($gallery_images)) {
			$output .= '<div class="twobc_image_gallery_wrapper images_wrapper';
			// output gallery id in class for JS use in certain edge cases
			$output .= ' displayed_gallery_' . $args['term_id'];
			$output .= '">
';
			// gallery title
			$output .= '	<p class="twobc_image_gallery_title">';
			$output .= apply_filters('twobc_image_gallery_title', sprintf(__('%s', TWOBC_IMAGEGALLERY_TEXT_DOMAIN), $args['term_title']));
			$output .= '</p>
';
			// back button
			switch (true) {
				case ('0' === $args['back_button']) :
					$back_button_url = false;
					break;
				
				case (!empty($args['back_button'])) :
					$back_button_url = esc_url($args['back_button']);
					break;
				
				case (empty($args['back_button'])) :
				default :
					$back_button_url = twobc_image_gallery_get_gallery_url($args['page_id']);
			}
			
			if ( !empty($back_button_url) ) {
				$output .= '	<div class="twobc_image_galleries_back_wrapper">
';
				$output .= '		<a href="' . $back_button_url . '" class="twobc_galleries_back">';
				$output .= apply_filters('twobc_image_gallery_button_back', __('&laquo; Back to galleries', TWOBC_IMAGEGALLERY_TEXT_DOMAIN));
				$output .= '</a>
';
				$output .= '	</div>
';
			}
			foreach ($gallery_images as $a_image) {
				$attachment_obj = wp_get_attachment_image_src($a_image->ID, 'thumbnail');
				$attachment_obj_full = wp_get_attachment_image_src($a_image->ID, 'full');
				$output .= '	<a href="' . $attachment_obj_full[0] . '" class="thumb_wrapper image_' . $a_image->ID . '"';
				// output width in style tag here
				// get current thumb size
				$current_thumb_size = twobc_image_gallery_get_image_sizes('thumbnail');
				if (!empty($current_thumb_size)) {
					$output .= ' style="';
					$output .= 'width:' . $current_thumb_size['width'] . 'px';
					$output .= '"';
				}
				$output .= '>';
				$output .= '<img src="' . esc_url($attachment_obj[0]) . '" height="' . $attachment_obj[2] . '" width="' . $attachment_obj[1] . '" alt="Thumbnail for ' . esc_attr($a_image->post_title) . ' gallery">';
				$output .= '<span class="tax_title"> ' . esc_attr($a_image->post_title) . ' </span>';
				$output .= '</a>
';
			}
			
			// optional page buttons
			if ('1' == $args['paginate_galleries']) {
				$output .= '	<div class="gallery_page_buttons cf">';
				// calculate last page estimate
				$images_total = twobc_image_gallery_get_image_count($args['term_id']);
				$last_page_estimate = intval(ceil($images_total / $args['images_per_page']));
				
				// static first page
				$page_buttons_array = array (
					0 => array (
						'title' => '1',
						'url' => twobc_image_gallery_get_gallery_url(
							$args['page_id'], // page id
							array ( // query args
								  'display_gallery' => $args['term_id'],
								  'page_num' => '1',
							)
						),
						'current' => ('1' == $args['page_num'] ? true : false),
					),
				);

				// build the rest of the page buttons array
				if (9 >= $last_page_estimate) { // display the pages as is
					for ($i = 1; $i < $last_page_estimate; $i++) {
						$page_num = $i + 1;
						$page_buttons_array[$i] = array (
							'title' => $page_num,
							'url' => twobc_image_gallery_get_gallery_url(
								$args['page_id'], // page id
								array ( // query args
									  'display_gallery' => $args['term_id'],
									  'page_num' => $page_num,
								)
							),
							'current' => ($page_num == $args['page_num'] ? true : false),
						);
					}
				} else { // else, we need ellipses
					// first ellipses check
					if (5 < $args['page_num']) {
						$page_buttons_array[] = array (
							'title' => '<span>&hellip;</span>',
							'url' => '',
						);
					}
					$current_array_pos = intval(count($page_buttons_array));
					
					// get the rest of the pages
					$continue_processing = true;
					$loop_counter = 0;
					while ($continue_processing) {
						switch (true) {
							case (5 >= $args['page_num']) : // get the first 5 pages, after static first
								$page_num = 2 + $loop_counter;
								if ($loop_counter >= 4) {
									$continue_processing = false;
								}
								break;
							
							case ($last_page_estimate - 4 <= $args['page_num']) : // get the last 5 pages, before static last
								$page_num = ($last_page_estimate - 5) + $loop_counter;
								if ($loop_counter >= 4) {
									$continue_processing = false;
								}
								break;
							
							default :
								$page_num = ($args['page_num'] - 3) + $loop_counter; // get previous 3, current, and next 3 pages
								if ($loop_counter >= 6) {
									$continue_processing = false;
								}
						}
						$page_buttons_array[$current_array_pos] = array (
							'title' => strval($page_num),
							'url' => twobc_image_gallery_get_gallery_url(
								$args['page_id'], // page id
								array ( // query args
									 'display_gallery' => $args['term_id'],
									 'page_num' => $page_num,
								)
							),
							'current' => ($page_num == $args['page_num'] ? true : false),
						);
						$current_array_pos++;
						$loop_counter++;
					}
					// last ellipses check
					if (($last_page_estimate - 5) >= $args['page_num']) {
						$page_buttons_array[] = array (
							'title' => '<span>&hellip;</span>',
							'url' => '',
						);
					}
					
					// static last page
					$page_buttons_array[] = array(
						'title' => $last_page_estimate,
						'url' => twobc_image_gallery_get_gallery_url(
							$args['page_id'], // page id
							array ( // query args
								  'display_gallery' => $args['term_id'],
								  'page_num' => $last_page_estimate,
							)
						),
						'current' => ($last_page_estimate == $args['page_num'] ? true : false),
					);

				}
				
				// output buttons
				if ( !empty($page_buttons_array) ) {
					// previous page button
					if ( 1 < $args['page_num'] ) {
						$output .= '<a href="';
						$page_previous = $args['page_num'] - 1;
						$output .= twobc_image_gallery_get_gallery_url(
							$args['page_id'], // page id
							array( // query args
								'display_gallery' => $args['term_id'],
								'page_num' => $page_previous
							)
						);
						$output .= '"';
						$output .= ' class="previous_page"';
						$output .= '>';
						$output .= apply_filters('twobc_image_gallery_previous_page_button', '&laquo;');
						$output .= '</a>';
					}
					
					foreach ($page_buttons_array as $_page_button) {
						//$output .= '		';
						if ( !empty($_page_button['url']) ) {
							$output .= '<a';
							$output .= ' href="' . esc_url($_page_button['url']) . '"';
							if (true === $_page_button['current']) {
								$output .= ' class="current_page"';
							}
							$output .= '>';
						}
						
						$output .= $_page_button['title'];
						
						if ( !empty($_page_button['url']) ) {
							$output .= '</a>';
						}
					}
					
					// next page button
					if ( $last_page_estimate > $args['page_num'] ) {
						$page_next = $args['page_num'] + 1;
						$output .= '<a href="';
						$output .= twobc_image_gallery_get_gallery_url(
							$args['page_id'], // page id
							array( // query args
								'display_gallery' => $args['term_id'],
								'page_num' => $page_next,
							)
						);
						$output .= '"';
						$output .= ' class="next_page"';
						$output .= '>';
						$output .= apply_filters('twobc_image_gallery_next_page_button', '&raquo;');
						$output .= '</a>';
					}
				}
				
			$output .= '	</div><!-- .gallery_page_buttons -->
';
			}
		}
	$output .= '</div><!-- .twobc_image_gallery_wrapper -->
';
	}
	
	return $output;
}

add_action('wp_ajax_twobc_image_gallery_image_generate', 'twobc_image_gallery_image_ajax_callback');
add_action('wp_ajax_nopriv_twobc_image_gallery_image_generate', 'twobc_image_gallery_image_ajax_callback');
/**
 * AJAX callback for image modal
 * 
 * @action wp_ajax_gallery_image_generate, image_ajax_callback
 */
function twobc_image_gallery_image_ajax_callback() {
	// verify nonce
	check_ajax_referer('twobc_image_gallery_ajax', 'ajax_nonce');
	
	$output = '';
	$image_id = esc_attr($_POST['image_id']);
	$attachment_obj = wp_get_attachment_image_src($image_id, 'full');
	$output .= '<img src="' . esc_url($attachment_obj[0]) . '" height="' . $attachment_obj[2] . '" width="' . $attachment_obj[1] . '">
';
	// image file name
	$output .= '<p>';
	$output .= get_the_title($image_id);
	$output .= '</p>
';
	echo apply_filters('twobc_image_gallery_output_modal', $output);
	die(); // this is required to terminate immediately and return a proper response
}

/*****************/
/*** SHORTCODE ***/
/*****************/
add_shortcode('2bc_image_gallery', 'twobc_image_gallery_shortcode');
/**
 * Shortcode for 2BC Image Gallery
 * 
 * @param $atts
 * 
 * @see add_shortcode()
 *
 * @return string
 */
function twobc_image_gallery_shortcode($atts) {
	$image_gallery_options = twobc_image_gallery_get_options();
	
	$args = shortcode_atts(
		array( // defaults
			'display_gallery' => '',
			'page_num' => '1',
			'page_id' => get_the_ID(),
			'parents' => '',
			'galleries' => '',
			'sort_method' => $image_gallery_options['sort_method'],
			'sort_order' => $image_gallery_options['sort_order'],
			'paginate_galleries' => $image_gallery_options['paginate_galleries'],
			'images_per_page' => $image_gallery_options['images_per_page'],
			'default_gallery_thumb' => $image_gallery_options['deafult_gallery_thumb'],
			'separate_galleries' => $image_gallery_options['separate_galleries'],
			'show_months' => $image_gallery_options['show_months'],
			'noajax' => '',
			'back_button' => '',
		), 
		$atts // incoming
	);
	
	// unset any empty values
	// that do not equal 0
	$args = array_filter($args, 'strlen');
	
	// unset any default values
	foreach ($args as $opt_name => $opt_val) {
		if ( $opt_val == $image_gallery_options[$opt_name] ) {
			unset($args[$opt_name]);
		}
	}
	
	return twobc_image_gallery_get_display($args);
}

/************************/
/*** CALENDAR OPTIONS ***/
/************************/
add_filter('update_attached_file', 'twobc_image_gallery_update_attached_file', 10, 2);
/**
 * Add calendar entries to uploaded images
 * 
 * @param $file
 * @param $attachment_id
 *
 * @return mixed
 */
function twobc_image_gallery_update_attached_file($file, $attachment_id) {
	$plugin_options = twobc_image_gallery_get_options();
	
	if ( isset($plugin_options['add_calendar']) && '1' == $plugin_options['add_calendar'] ) {
	
		$attachment_obj = get_post($attachment_id);
		
		if ( !empty($attachment_obj) ) {
			// only filter images
			if ( false !== strpos($attachment_obj->post_mime_type, 'image') ) {
				// get month and year from post_date
				$post_date = strtotime($attachment_obj->post_date);
				$post_month = apply_filters('twobc_image_gallery_add_month', date('F', $post_date));
				$post_year = apply_filters('twobc_image_gallery_add_year', date('Y', $post_date));
				
				// add galleries to attachment
				wp_add_object_terms(
					$attachment_id, // attachment id
					array( // terms
						 $post_month,
						 $post_year,
					), 
					'twobc_img_galleries' // taxonomy
				);
				
			}
		}
	}
	
	return $file;
}

/*****************/
/*** UTILITIES ***/
/*****************/
/**
 * Get options for 2BC Image Gallery
 * 
 * @return mixed|void
 */
function twobc_image_gallery_get_options() {
	static $image_gallery_options;
	
	if ( empty($image_gallery_options )) {
		$image_gallery_options = get_option('twobc_image_gallery_options');
	}
	
	return $image_gallery_options;
}

/**
 * Return count of gallery, trying to be respectful of database reads
 * 
 * @param $gallery - id, slug, or name of gallery
 *
 * @return int|bool - count if a gallery matched, false if error
 */
function twobc_image_gallery_get_image_count($gallery) {
	static $galleries_current;
	
	if (empty($galleries_current)) {
		$get_categories_args = array(
			'type' => 'attachment',
			'hide_empty' => 0,
			'taxonomy' => 'twobc_img_galleries',
		);
		$galleries_current = get_categories($get_categories_args);
	}
	
	if (!empty($galleries_current)) {
		foreach ($galleries_current as $term_obj) {
			if (
				$gallery == $term_obj->term_id
				|| $gallery == $term_obj->slug
				|| $gallery == $term_obj->name
			) {
				return $term_obj->category_count;
			}
		}
	}
	
	// if we exit the foreach loop, we haven't found a match, return false for error
	return false;
}

/**
 * Return all registered image sizes.  Optionally, return one image size.
 * 
 * @param string $size
 *
 * @return array|bool
 */
function twobc_image_gallery_get_image_sizes($size = '') {

	global $_wp_additional_image_sizes;
	$sizes = array ();
	$get_intermediate_image_sizes = get_intermediate_image_sizes();
	// Create the full array with sizes and crop info
	foreach ($get_intermediate_image_sizes as $_size) {
		if (
			in_array(
				$_size,
				array (
					'thumbnail',
					'medium',
					'large'
				)
			)
		) {
			$sizes[$_size]['width'] = get_option($_size . '_size_w');
			$sizes[$_size]['height'] = get_option($_size . '_size_h');
			$sizes[$_size]['crop'] = (bool)get_option($_size . '_crop');

		} elseif (isset($_wp_additional_image_sizes[$_size])) {
			$sizes[$_size] = array (
				'width' => $_wp_additional_image_sizes[$_size]['width'],
				'height' => $_wp_additional_image_sizes[$_size]['height'],
				'crop' => $_wp_additional_image_sizes[$_size]['crop']
			);

		}

	}
	// Get only 1 size if found
	if ($size) {
		if (isset($sizes[$size])) {
			return $sizes[$size];
		} else {
			return false;
		}
	}

	return $sizes;
}

/**
 * Try to generate valid URL to the gallery page, mostly for Back To Gallery button
 * 
 * @param null $page_id
 * @param array $query_args
 *
 * @return bool|mixed|string|void
 */
function twobc_image_gallery_get_gallery_url($page_id = null, $query_args = array()) {
	if ( empty($page_id) ) {
		global $post;
		$page_id = $post->ID;
	}
	
	$gallery_url = get_permalink($page_id);
	
	// optional query args
	if (
		!empty($query_args)
		&& is_array($query_args)
	) {
		$gallery_url = add_query_arg(
			$query_args, // query args to add
			$gallery_url // old query or uri
		);
	}
	
	return $gallery_url;
}

/**
 * Determines if images from one gallery are present in other galleries
 * 
 * @param $term_haystack
 * @param $term_needle
 *
 * @return bool
 */
function twobc_image_gallery_contains_like_terms($term_haystack, $term_needle) {
	$term_haystack_obj = twobc_image_gallery_get_gallery_obj($term_haystack);
	if ( is_array($term_needle) ) {
		$term_needle_obj = array();
		foreach ($term_needle as $_needle) {
			$term_needle_obj[] = twobc_image_gallery_get_gallery_obj($_needle);
		}
	} else {
		$term_needle_obj[] = twobc_image_gallery_get_gallery_obj($term_needle);
	}
	
	if (
		!empty($term_haystack_obj)
		&& !empty ($term_needle_obj) 
	) {
		
		$tax_query_args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'twobc_img_galleries',
					'field' => 'id',
					'terms' => $term_haystack_obj->term_id,
				),
			),
		);
		
		// add needle arrays
		foreach ($term_needle_obj as $_needle) {
			$tax_query_args['tax_query'][] = array(
				'taxonomy' => 'twobc_img_galleries',
				'field' => 'id',
				'terms' => $_needle->term_id,
			);
		}
		
		$tax_query = new WP_Query( $tax_query_args );
		
		if ( 0 < $tax_query->post_count ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Get term object by id, name or slug
 * 
 * @param $term_identifier
 *
 * @return bool|mixed|null|WP_Error
 */
function twobc_image_gallery_get_gallery_obj($term_identifier) {
	if ( is_numeric($term_identifier) ) {
		$term_test = get_term(
			$term_identifier, // term id
			'twobc_img_galleries' // taxonomy to query
		);
	}
	
	if ( empty($term_test) ) {
		// try to get by name
		$term_test = get_term_by(
			'name', // field to search
			$term_identifier, // value to search for
			'twobc_img_galleries' // taxonomy name
		);
	}
	
	if ( empty($term_test) ) {
		// last, try to get by slug
		$term_test = get_term_by(
			'slug', // field to search
			$term_identifier, // value to search for
			'twobc_img_galleries' // taxonomy name
		);
	}
	
	if (
	!empty($term_test)
	&& !is_wp_error($term_test)
	) {
		return $term_test;
	} else {
		return false;
	}
}