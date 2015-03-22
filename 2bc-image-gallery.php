<?php
/**
 * Plugin Name: 2BC Image Gallery
 * Plugin URI: http://2bcoding.com/plugins/2bc-image-gallery
 * Description: Add tags to images and group them into galleries, easily set options to display the lightbox galleries, or use the shortcode
 * Version: 2.0.0
 * Author: 2BCoding
 * Author URI: http://2bcoding.com
 * Text Domain: 2bc-image-gallery
 * License: GPL2
 */

/*
 * Copyright 2014-2015  2BCoding  (email : info@2bcoding.com)
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
// instantiate class
$core = twobc_image_gallery::get_instance();

// hooks
add_action('plugins_loaded', array($core, 'hook_plugins_loaded'));
add_action('admin_enqueue_scripts', array($core, 'hook_admin_enqueue_scripts'));
add_action('wp_enqueue_scripts', array($core, 'hook_wp_enqueue_scripts'));
add_action('init', array($core, 'hook_init'));
add_action('admin_menu', array($core, 'hook_admin_menu'));
add_action('admin_init', array($core, 'hook_admin_init'));
add_action('admin_notices', array($core, 'hook_admin_notices'));
add_action('twobc_img_galleries_add_form_fields', array($core, 'hook_taxonomy_add_form_fields'), 10, 2);
add_action('twobc_img_galleries_edit_form_fields', array($core, 'hook_taxonomy_edit_form_fields'), 10, 2);
add_action('edited_twobc_img_galleries', array($core, 'hook_save_twobc_img_galleries'), 10, 2);
add_action('created_twobc_img_galleries', array($core, 'hook_save_twobc_img_galleries'), 10, 2);
add_filter('manage_edit-twobc_img_galleries_columns', array($core, 'hook_edit_twobc_img_galleries_cols'));
add_action('manage_twobc_img_galleries_custom_column', array($core, 'hook_twobc_img_galleries_custom_col'), 10, 3);
add_filter('manage_edit-twobc_img_galleries_sortable_columns', array($core, 'hook_twobc_img_galleries_sortable_cols'));
add_filter('the_content', array($core, 'hook_the_content'));

add_action('wp_ajax_twobc_image_gallery_generate', array($core, 'gallery_ajax_callback'));
add_action('wp_ajax_nopriv_twobc_image_gallery_generate', array($core, 'gallery_ajax_callback'));

add_action('wp_ajax_twobc_image_gallery_image_generate', array($core, 'image_ajax_callback'));
add_action('wp_ajax_nopriv_twobc_image_gallery_image_generate', array($core, 'image_ajax_callback'));
add_filter('update_attached_file', array($core, 'hook_update_attached_file'), 10, 2);

// shortcode
add_shortcode('2bc_image_gallery', array($core, 'register_shortcode'));

add_action('wp_head', array($core, 'custom_css'));

/**
 * Class twobc_image_gallery
 */
class twobc_image_gallery {
	private static $instance = null;
	private static $plugin_options;
	private static $plugin_url;
	private static $plugin_path;
	private static $plugin_text_domain;

	/**
	 * Constructor
	 */
	private function __construct() {
		self::$plugin_text_domain = '2bc-image-gallery';
		self::$plugin_url = plugin_dir_url(__FILE__);
		self::$plugin_path = plugin_dir_path(__FILE__);
	}

	/**
	 * Get current instance
	 *
	 * @return null|twobc_image_gallery
	 */
	public static function get_instance() {
		if (null === self::$instance)
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Get current plugin version
	 *
	 * @return string
	 */
	private static function get_version() {
		$current_version = '2.0.0';

		return $current_version;
	}

	/**
	 * Deprecate?
	 *
	 * @return string
	 */
	public static function get_text_domain() {
		return self::$plugin_text_domain;
	}

	/**
	 * Plugin Init
	 */
	public static function hook_plugins_loaded() {

		// handle install and upgrade
		$plugin_version = self::get_version();
		$twobc_image_gallery_default_values = self::get_options_default();
		$plugin_options = self::get_options();
		$update_options = false;

		// install check
		if ( empty($plugin_options) ) {
			// init with default values
			$update_options = true;
			$plugin_options = $twobc_image_gallery_default_values;
		}

		// upgrade check
		if ( $plugin_version != $plugin_options['version'] ) {
			// init any empty db fields to catch any new additions
			foreach ( $twobc_image_gallery_default_values as $_name => $_value ) {
				if ( !isset($plugin_options[$_name]) ) {
					$plugin_options[$_name] = $_value;
				}
			}

			// set the updated settings
			$update_options = true;
		}

		if ( $update_options )
			update_option('twobc_image_gallery_options', $plugin_options);

		// add twobc_wpadmin_input_fields for option fields
		require_once(self::$plugin_path . 'includes/class_twobc_wpadmin_input_fields_1_0_0.php');
	}

	/**
	 * Enqueue CSS and JS - Admin
	 */
	public static function hook_admin_enqueue_scripts() {
		global $typenow;
		$current_screen = get_current_screen();

		if ( !empty($typenow) && 'attachment' == $typenow ) {
			wp_register_script(
				'twobc_galleries_js_admin', // handle
				self::$plugin_url . 'includes/js/2bc-image-gallery-admin.js', // path
				array('jquery'), // dependencies
				self::get_version() // version
			);
			wp_localize_script(
				'twobc_galleries_js_admin', // script handle
				'meta_image', // variable name
				array( // values to pass
					'title' => __('Choose or Upload an Image', self::$plugin_text_domain),
					'button' => __('Use this image', self::$plugin_text_domain),
				)
			);

			wp_enqueue_media();
			wp_enqueue_script(
				'twobc_galleries_js_admin' // handle
			);
		}


		// option page specific enqueues
		if ( 'settings_page_twobc_imagegallery' == $current_screen->id ) {
			wp_enqueue_style('wp-color-picker');

			wp_register_script(
				'twobc_galleries_js_admin', // handle
				self::$plugin_url . 'includes/js/2bc-image-gallery-admin.js', // path
				array('wp-color-picker'), // dependencies
				self::get_version(), // version
				true // in footer
			);
			wp_enqueue_script(
				'twobc_galleries_js_admin' // handle
			);
		}
	}

	/**
	 * Enqueue CSS and JS - Main
	 */
	public static function hook_wp_enqueue_scripts() {
		$plugin_options = self::get_options();
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
			$twobc_image_gallery_version = self::get_version();
			wp_register_script(
				'twobc_galleries_js_front', // handle
				self::$plugin_url . 'includes/js/2bc-image-gallery-front.js', // source
				array('jquery'), // dependencies
				$twobc_image_gallery_version, // version
				true // load in footer
			);

			// localize script
			$script_options = array(
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
			$ajax_options = array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'ajax_nonce' => wp_create_nonce('twobc_image_gallery_ajax'),
			);
			wp_localize_script('twobc_galleries_js_front', 'ajax_object', $ajax_options);
			wp_enqueue_script('twobc_galleries_js_front');

			wp_register_script(
				'twobc_galleries_js_picomodal', // handle
				self::$plugin_url . 'includes/js/2bc-image-gallery-picomodal.js', // source
				array('jquery'), // dependencies
				$twobc_image_gallery_version // version
			);
			wp_enqueue_script('twobc_galleries_js_picomodal');


			// styles
			wp_register_style(
				'twobc_galleries_css_core', // handle
				self::$plugin_url . 'includes/css/2bc-image-gallery-core.css', // url source
				array(), // dependencies
				$twobc_image_gallery_version // version
			);

			wp_enqueue_style('twobc_galleries_css_core');

			// cosmetic style
			if (
				empty($plugin_options['hide_style'])
				|| '1' != $plugin_options['hide_style']
			) {
				wp_register_style(
					'twobc_galleries_css_cosmetic', // handle
					self::$plugin_url . 'includes/css/2bc-image-gallery-cosmetic.css', // url source
					array('twobc_galleries_css_core'), // dependencies
					$twobc_image_gallery_version // version
				);
				wp_enqueue_style('twobc_galleries_css_cosmetic');
			}
		}
	}

	/**
	 * Register taxonomy - twobc_img_galleries
	 */
	public static function hook_init() {
		load_plugin_textdomain(self::$plugin_text_domain, false, self::$plugin_path . 'lang');

		$tax_galleries_labels = apply_filters(
			'twobc_image_gallery_tax_labels', // filter name
			array( // filter argument
				'name' => _x('Galleries', 'taxonomy general name', self::$plugin_text_domain),
				'singular_name' => _x('Gallery', 'taxonomy singular name', self::$plugin_text_domain),
				'menu_name' => __('Galleries', self::$plugin_text_domain),
				'search_items' => __('Search Galleries', self::$plugin_text_domain),
				'all_items' => __('All Galleries', self::$plugin_text_domain),
				'edit_item' => __('Edit Gallery', self::$plugin_text_domain),
				'view_item' => __('View Gallery', self::$plugin_text_domain),
				'update_item' => __('Update Gallery', self::$plugin_text_domain),
				'add_new_item' => __('Add New Gallery', self::$plugin_text_domain),
				'new_item_name' => __('New Gallery Name', self::$plugin_text_domain),
				'popular_items' => __('Popular Galleries', self::$plugin_text_domain), // non-hierarchical only
				'separate_items_with_commas' => __('Separate galleries with commas', self::$plugin_text_domain), // non-hierarchical only
				'add_or_remove_items' => __('Add or remove galleries', self::$plugin_text_domain), // JS disabled, non-hierarchical only
				'choose_from_most_used' => __('Choose from the most used galleries', self::$plugin_text_domain),
				'not_found' => __('No galleries found', self::$plugin_text_domain),
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

	/**
	 * Add admin menu - twobc_imagegallery
	 */
	public static function hook_admin_menu() {
		add_options_page(
			__('2BC Image Gallery', self::$plugin_text_domain), // page title
			__('2BC Image Gallery', self::$plugin_text_domain), // menu title
			'manage_options', // capability required
			'twobc_imagegallery', // page slug
			array(self::get_instance(), 'settings_page_cb') // display callback
		);
	}

	/**
	 * Add settings page and settings - twobc_image_gallery_options
	 */
	public static function hook_admin_init() {
		$checkbox_value = '1';
		$core = self::get_instance();

		// admin menu and pages
		register_setting(
			'twobc_image_gallery_options', // option group name, declared with settings_fields()
			'twobc_image_gallery_options', // option name, best to set same as group name
			array($core, 'options_sanitize_cb') // sanitization callback
		);

		// SECTION - GENERAL
		$section = 'general';
		add_settings_section(
			'twobc_image_gallery_options_' . $section, // section HTML id
			__('Gallery Options', self::$plugin_text_domain), // section title
			array($core, 'options_general_cb'), // display callback
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
		$gallery_page_options = array(
			'-1' => __('Select a page&hellip;', self::$plugin_text_domain),
		);
		$current_pages = get_posts($current_pages_args);
		if ( !empty($current_pages) && !is_wp_error($current_pages) ) {
			foreach ( $current_pages as $page_obj ) {
				$gallery_page_options[$page_obj->ID] = esc_html($page_obj->post_title . ' - ID: ' . $page_obj->ID);
			}
		}
		add_settings_field(
			$field, // field HTML id
			__('Gallery Page', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'select',
				'name' => $field,
				'description' => __('Select a page to display galleries on.  The shortcode <code class="language-markup">[2bc_image_gallery]</code> can be used for manual display instead of setting a page here.', self::$plugin_text_domain),
				'options' => $gallery_page_options,
			)
		);
		// Gallery Page Content
		$field = 'page_content';
		add_settings_field(
			$field, // field HTML id
			__('Page Content', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'radio',
				'name' => $field,
				'description' => __('Control how page content is handled on the Gallery Page', self::$plugin_text_domain),
				'options' => array(
					'before' => __('Before page content', self::$plugin_text_domain),
					'templatetag' => __('Replace &#37;&#37;2bc_image_gallery&#37;&#37; template tag', self::$plugin_text_domain),
					'after' => __('After page content', self::$plugin_text_domain),
					'replace' => __('Replace all page content with gallery', self::$plugin_text_domain),
				),
			)
		);
		// Sort Method
		$field = 'sort_method';
		add_settings_field(
			$field, // field HTML id
			__('Sort Method', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'select',
				'name' => $field,
				'description' => __('Select how gallery images are sorted: by <strong>uploaded date</strong>, alphbetically by <strong>filename</strong>, or <strong>random</strong>', self::$plugin_text_domain),
				'options' => array(
					'date' => __('Date uploaded', self::$plugin_text_domain),
					'title' => __('Filename', self::$plugin_text_domain),
					'rand' => __('Random', self::$plugin_text_domain),
				),
			)
		);
		// Sort Order
		$field = 'sort_order';
		add_settings_field(
			$field, // field HTML id
			__('Sort Order', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'select',
				'name' => $field,
				'description' => __('Which direction to sort, <strong>ascending</strong> (1, 2, 3) or <strong>descending</strong> (9, 8, 7)', self::$plugin_text_domain),
				'options' => array(
					'desc' => __('Descending', self::$plugin_text_domain),
					'asc' => __('Ascending', self::$plugin_text_domain),
				),
			)
		);
		// Paginate Galleries
		$field = 'paginate_galleries';
		add_settings_field(
			$field, // field HTML id
			__('Paginate Galleries', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Break up galleries into pages', self::$plugin_text_domain),
			)
		);
		// Images Per Page
		$field = 'images_per_page';
		add_settings_field(
			$field, // field HTML id
			__('Images Per Page', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'number',
				'name' => $field,
				'description' => __('How many images to display per page, only applies if pagination is enabled', self::$plugin_text_domain),
			)
		);

		// Default Gallery Thumb
		$field = 'default_gallery_thumb';
		add_settings_field(
			$field, // field HTML id
			__('Gallery Thumb Source', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'select',
				'name' => $field,
				'description' => __('If the gallery does not have a custom thumbnail, decide how to generate one', self::$plugin_text_domain),
				'options' => array(
					'last' => __('Last image added', self::$plugin_text_domain),
					'first' => __('First image added', self::$plugin_text_domain),
					'random' => __('Random', self::$plugin_text_domain),
				),
			)
		);

		// Background Color - Thumb
		$field = 'bg_thumb';
		add_settings_field(
			$field, // field HTML id
			__('Background Color: Image Thumbs', self::$plugin_text_domain), // field title
			array($core, 'color_picker_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'colorpicker',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Choose an optional custom color for the image thumbnail backgrounds', self::$plugin_text_domain),
				'default_color' => '#cccccc',
			)
		);

		// Hide Style
		$field = 'hide_style';
		add_settings_field(
			$field, // field HTML id
			__('Hide Default Stying', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Check to not load the default style sheet, and create the styling in the theme CSS instead', self::$plugin_text_domain),
			)
		);
		// Disable AJAX
		$field = 'disable_ajax';
		add_settings_field(
			$field, // field HTML id
			__('Disable AJAX', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Check to disable AJAX calls.  Do this if galleries are not loading when clicked.  This will mean that the page will refresh with the new content.', self::$plugin_text_domain),
			)
		);

		// SECTION - MODAL OPTIONS
		$section = 'modal';
		add_settings_section(
			'twobc_image_gallery_options_' . $section, // section HTML id
			__('Modal Options', self::$plugin_text_domain), // section title
			array($core, 'options_general_cb'), // display callback
			'twobc_imagegallery' // page to display on
		);

		// Display Title
		$field = 'display_title';
		add_settings_field(
			$field, // field HTML id
			__('Display Image Title', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Display the image title when viewing a single image in the lightbox', self::$plugin_text_domain),
			)
		);

		// Slideshow Delay
		$field = 'slideshow_delay';
		add_settings_field(
			$field, // field HTML id
			__('Slideshow Delay', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'number',
				'name' => $field,
				'description' => __('Enter how many milliseconds to wait before displaying the next slide, when the slideshow is playing', self::$plugin_text_domain),
			)
		);

		// Background Color - Thumb
		$field = 'bg_modal';
		add_settings_field(
			$field, // field HTML id
			__('Background Color: Modal Window', self::$plugin_text_domain), // field title
			array($core, 'color_picker_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'colorpicker',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Choose an optional custom color for the modal window background', self::$plugin_text_domain),
				'default_color' => '#fefefe',
			)
		);
/*
		// Display Description
		$field = 'display_description';
		add_settings_field(
			$field, // field HTML id
			__('Display Image Description', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Display the image description when viewing a single image in the lightbox', self::$plugin_text_domain),
			)
		);
*/
/*
		// Display Galleries
		$field = 'display_galleries';
		add_settings_field(
			$field, // field HTML id
			__('Display Share Buttons', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Display social share buttons when viewing a single image in the lightbox', self::$plugin_text_domain),
			)
		);
*/
/*
		// Display Sharing Buttons
		$field = 'display_share_buttons';
		add_settings_field(
			$field, // field HTML id
			__('Display Share Buttons', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Display social share buttons when viewing a single image in the lightbox', self::$plugin_text_domain),
			)
		);
*/

		// SECTION - DATE GALLERIES
		$section = 'calendar';
		add_settings_section(
			'twobc_image_gallery_options_' . $section, // section HTML id
			__('Calendar Based Galleries', self::$plugin_text_domain), // section title
			array($core, 'options_general_cb'), // display callback
			'twobc_imagegallery' // page to display on
		);
		// Add Calendar Galleries
		$field = 'add_calendar';
		add_settings_field(
			$field, // field HTML id
			__('Add Calendar Based Galleries', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Add calendar galleries (i.e. January, 2012) to uploaded images', self::$plugin_text_domain),
			)
		);
		// Separate Calendar Galleries
		$field = 'separate_galleries';
		add_settings_field(
			$field, // field HTML id
			__('Separate Calendar Galleries', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Show calendar galleries in their own separate section', self::$plugin_text_domain),
			)
		);
		// Show Month Galleries
		$field = 'show_months';
		add_settings_field(
			$field, // field HTML id
			__('Show Month Galleries', self::$plugin_text_domain), // field title
			array($core, 'option_field'), // display callback
			'twobc_imagegallery', // page to display on
			'twobc_image_gallery_options_' . $section, // section to display in
			array( // additional arguments
				'type' => 'checkbox',
				'name' => $field,
				'value' => $checkbox_value,
				'description' => __('Display month-based galleries on the main page, only applies if <strong>Separate Calendar Galleries</strong> is checked', self::$plugin_text_domain),
			)
		);
	}

	/**
	 * Build a form field
	 *
	 * @param $field_args
	 */
	public static function option_field($field_args) {
		$twobc_image_gallery_wpadmin_fields = new twobc_wpadmin_input_fields_1_0_0(
			array(
				'nonce' => false,
			)
		);

		// parse field args
		$field_args = array_merge($twobc_image_gallery_wpadmin_fields->field_default_args(), $field_args);

		// get current value
		$current_value = self::get_options();
		$field_args['current_value'] = (isset($current_value[$field_args['name']]) ? $current_value[$field_args['name']] : '');

		// field nonce
		wp_nonce_field(
			'twobc_image_gallery_options_nonce', // action
			'twobc_image_gallery_options_' . $field_args['name'] // custom name
		);

		// fix name
		$field_args['name'] = 'twobc_image_gallery_options[' . $field_args['name'] . ']';


		echo '<fieldset>
';
		$twobc_image_gallery_wpadmin_fields->field($field_args);
		echo '</fieldset>
';
	}

	public static function color_picker_field($field_args) {
		$current_value = self::get_options();
		$default_color = ( !empty($field_args['default_color']) ? $field_args['default_color'] : '' );

		wp_nonce_field(
			'twobc_image_gallery_options_nonce', // action
			'twobc_image_gallery_options_' . $field_args['name'] // custom name
		);

		echo '<fieldset>
';

		echo '	<input type="text" name="twobc_image_gallery_options[' . $field_args['name'] . ']" class="twobcig_color_picker" value="' . (!empty($current_value[$field_args['name']]) ? $current_value[$field_args['name']] : '') . '"';

		if ( !empty($default_color) ) {
			echo 'data-default-color="' . $default_color . '">';
		}

		echo '</fieldset>
';

	}

	/**
	 * Get current plugin options
	 *
	 * @return mixed|void
	 */
	public static function get_options() {
		if ( empty(self::$plugin_options) )
			self::$plugin_options = get_option('twobc_image_gallery_options');

		return self::$plugin_options;
	}

	/**
	 * Get CSS for admin options screen
	 *
	 * @return string
	 */
	private static function get_admin_css() {
		$return = 'h3 {
	background: #ccc;
	margin: 1em -10px;
	padding: 20px 10px;
}
input[type="number"].small-text {
	width: 80px;
	max-width: 80px;
}
';

		return $return;
	}

	/**
	 * Settings page callback
	 */
	public static function settings_page_cb() {
		//must check that the user has the required capability
		if ( !current_user_can('manage_options') ) {
			wp_die(__('You do not have sufficient permissions to access this page.', self::$plugin_text_domain));
		}

		$image_gallery_admin_css = self::get_admin_css();
		if ( !empty($image_gallery_admin_css) ) {
			echo '<style type="text/css">' . $image_gallery_admin_css . '</style>
';
		}

		echo '<div id="twobc_image_gallery_options_wrap" class="wrap">
';
		settings_errors(
			'twobc_image_gallery_options', // settings group name
			false, // re-sanitize values on errors
			true // hide on update - set to true to get rid of duplicate Updated messages
		);
		echo '	<h2>' . __('2BC Image Gallery Options', self::$plugin_text_domain) . '</h2>
';
		echo '	<p>';
		_e('More help available at the <a href="http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-documentation/" target="_blank" ref="nofollow">2BC Image Gallery documentation page</a>.', self::$plugin_text_domain);
		echo '</p>
';
		echo '	<form method="post" action="options.php">
';

		settings_fields('twobc_image_gallery_options');
		do_settings_sections('twobc_imagegallery');

		submit_button(__('Save all settings', self::$plugin_text_domain));

		echo '	</form>
';
		echo '</div>
';

		// $image_gallery_js = self::get_admin_js();
		if ( !empty($image_gallery_js) ) {
			echo '<script>' . $image_gallery_js . '</script>
';
		}
	}

	/**
	 * Settings section callback
	 */
	public static function options_general_cb($section) {
		// intentionally left blank
	}

	/**
	 * Settings page sanitization callback
	 *
	 * @param $saved_settings
	 *
	 * @return mixed
	 */
	public static function options_sanitize_cb($saved_settings) {
		$settings_errors = array(
			'updated' => false,
			'error' => array(),
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
			'display_title' => 'checkbox',
			'slideshow_delay' => 'number',
			//'display_description' => 'checkbox',
			'display_galleries' => 'checkbox',
			//'display_share_buttons' => 'checkbox',
			'add_calendar' => 'checkbox',
			'separate_galleries' => 'checkbox',
			'show_months' => 'checkbox',
			'bg_thumb' => 'colorpicker',
			'bg_modal' => 'colorpicker',
		);

		foreach ( $saved_settings as $setting_key => $setting_val ) {
			// security checks - nonce, capability
			if (
				isset($known_fields[$setting_key])
				&& check_admin_referer(
					'twobc_image_gallery_options_nonce', // nonce action
					'twobc_image_gallery_options_' . $setting_key // query arg, nonce name
				)
				&& current_user_can('manage_options')
			) {
				switch ( $known_fields[$setting_key] ) {
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

					case 'colorpicker' :
						if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $setting_val) )
							$saved_settings[$setting_key] = ($setting_val);
						break;

					default :
						// unknown field type?  Shouldn't happen, but unset to be safe
						unset($saved_settings[$setting_key]);
				}
			} else { // unknown field or security fail, unset to be safe
				unset($saved_settings[$setting_key]);
			}
		}
		// separate validation for un-checked checkboxes
		foreach ( $known_fields as $field_name => $field_type ) {
			if (
				'checkbox' == $field_type
				&& !isset($saved_settings[$field_name])
			) {
				$saved_settings[$field_name] = '0';
				$settings_errors['updated'] = true;
			}
		}

		// register errors
		if ( !empty($settings_errors['errors']) && is_array($settings_errors['errors']) ) {
			foreach ( $settings_errors['errors'] as $error ) {
				add_settings_error(
					'twobc_image_gallery_options', // Slug title of the setting
					'twobc_image_gallery_options_error', // Slug of error
					$error, // Error message
					'error' // Type of error (**error** or **updated**)
				);
			}
		}
		if ( true === $settings_errors['updated'] ) {
			add_settings_error(
				'twobc_image_gallery_options', // Slug title of the setting
				'twobc_image_gallery_options_error', // Slug of error
				__('Settings saved.', self::$plugin_text_domain), // Error message
				'updated' // Type of error (**error** or **updated**)
			);
		}

		// update the static class property
		self::$plugin_options = $saved_settings;

		// set plugin version number
		$saved_settings['version'] = self::get_version();

		return $saved_settings;
	}

	/**
	 * Add admin notice to Image Gallery page
	 */
	public static function hook_admin_notices() {
		$image_gallery_options = self::get_options();
		//global $post;
		$post_id = (!empty($_GET['post']) ? intval($_GET['post']) : '');
		$current_screen = get_current_screen();
		if (
			isset($image_gallery_options['gallery_page'])
			&& 'page' == $current_screen->id
			&& 'edit' == $current_screen->parent_base
			&& $post_id == $image_gallery_options['gallery_page']
		) {
			echo '<div class="updated">
	<p>
';
			_e('This page is currently being used to display the <strong>2BC Media Gallery</strong>', self::$plugin_text_domain);
			echo '
	</p>
</div>
';
		}
	}

	/**
	 * Add custom fields to Add Gallery page - featured gallery image picker
	 */
	public static function hook_taxonomy_add_form_fields() {
		echo '<div class="form-field">
	<label for="twobc_img_galleries_meta[gallery_featured_img]">';
		_e('Gallery Featured Image ID', self::$plugin_text_domain);
		echo '</label>
';

		echo '	<input type="text" name="twobc_img_galleries_meta[gallery_featured_img]" id="twobc_img_galleries_meta[gallery_featured_img]" value="">
';

		echo '	<input type="button" id="twobc-image-gallery-featured-image-button" class="button" value="';
		_e('Choose or Upload an Image', self::$plugin_text_domain);
		echo '"';
		echo '>
';

		echo '	<p class="description">';
		_e('Choose or upload a picture to be the galleries featured image', self::$plugin_text_domain);
		echo '</p>
';

		echo '</div>
';
	}

	/**
	 * Add custom fields to Edit Gallery page - featured gallery image picker
	 *
	 * @param $term
	 */
	public static function hook_taxonomy_edit_form_fields($term) {
		// retrieve the existing value(s) for this meta field. This returns an array
		$term_meta = get_option('taxonomy_' . $term->term_id);

		echo '<tr class="form-field">
	<th scope="row" valign="top"><label for="twobc_img_galleries_meta[gallery_featured_img]">';
		_e('Gallery Featured Image', self::$plugin_text_domain);
		echo '</label></th>
';

		echo '	<td>';
		echo '		<input type="text" name="twobc_img_galleries_meta[gallery_featured_img]" id="twobc_img_galleries_meta[gallery_featured_img]"';
		echo ' value="';
		if ( !empty($term_meta['gallery_featured_img']) ) {
			echo esc_attr($term_meta['gallery_featured_img']);
		}
		echo '"';
		echo '>
';

		echo '		<input type="button" id="twobc-image-gallery-featured-image-button" class="button" value="';
		_e('Choose or Upload an Image', self::$plugin_text_domain);
		echo '"';
		echo '>
';

		echo '		<p class="description">';
		_e('Choose or upload a picture to be the galleries featured image', self::$plugin_text_domain);
		echo '</p>
';

		echo '	</td>
';
		echo '</tr>
';
	}

	/**
	 * Save custom fields on Gallery pages
	 *
	 * @param $term_id
	 */
	public static function hook_save_twobc_img_galleries($term_id) {
		if ( isset($_REQUEST['twobc_img_galleries_meta']) ) {
			$term_meta = (get_option('taxonomy_' . $term_id));
			$tax_keys = array_keys($_REQUEST['twobc_img_galleries_meta']);
			foreach ( $tax_keys as $a_key ) {
				$term_meta[sanitize_text_field($a_key)] = sanitize_text_field($_POST['twobc_img_galleries_meta'][$a_key]);
			}

			// save the option array
			update_option('taxonomy_' . $term_id, $term_meta);
		}
	}

	/**
	 * Add ID Column to taxonomy page - new column
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function hook_edit_twobc_img_galleries_cols($columns) {
		$columns['id'] = __('ID', self::$plugin_text_domain);

		return $columns;
	}

	/**
	 * Add ID Column to taxonomy page - value
	 *
	 * @param $value
	 * @param $col_name
	 * @param $id
	 *
	 * @return int|null
	 */
	public static function hook_twobc_img_galleries_custom_col($value, $col_name, $id) {
		$return = null;

		if ( 'id' == $col_name )
			$return = intval($id);

		return $return;

	}

	/**
	 * Add ID Column to taxonomy page - sortable (numeric)
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function hook_twobc_img_galleries_sortable_cols($columns) {
		$columns['id'] = 'id';

		return $columns;
	}

	/**
	 * Filter the_content on Image Gallery page according to plugin options
	 *
	 * @param $content
	 *
	 *
	 * @return mixed|string|void
	 */
	public static function hook_the_content($content) {
		$plugin_options = self::get_options();

		if (
			isset($plugin_options['gallery_page'])
			&& '-1' != $plugin_options['gallery_page']
			&& is_page($plugin_options['gallery_page'])
			&& isset($plugin_options['page_content'])
		) {
			// prepare arguments
			$twobc_image_gallery_args = self::get_display_args_default();

			$twobc_image_gallery_args['page_id'] = $plugin_options['gallery_page'];

			switch ( $plugin_options['page_content'] ) {
				// before
				case 'before' :
					$content = self::get_display($twobc_image_gallery_args) . $content;
					break;

				// replace
				case 'replace' :
					$content = self::get_display($twobc_image_gallery_args);
					break;

				// templatetag - %%2bc_image_gallery%%
				case 'templatetag' :
					$limit = 1;
					$content = str_replace(
						'%%2bc_image_gallery%%', // needle
						self::get_display($twobc_image_gallery_args), // replacement
						$content, // haystack
						$limit // limit
					);
					break;

				// after
				// default
				case 'after' :
				default :
					$content .= self::get_display($twobc_image_gallery_args);
			}
		}

		return $content;
	}


	/**
	 * Get the URL for the page the gallery is being displayed on
	 *
	 * @param null $page_id
	 * @param array $query_args
	 *
	 * @return bool|string
	 */
	public static function get_gallery_url($page_id = null, $query_args = array()) {
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
	 * Get the default plugin options
	 *
	 * @return array
	 */
	private static function get_options_default() {
		$default_options = array(
			'gallery_page' => -1,
			'page_content' => 'after',
			'sort_method' => 'date',
			'sort_order' => 'DESC',
			'paginate_galleries' => '1',
			'images_per_page' => '60',
			'default_gallery_thumb' => 'last',
			'hide_style' => '0',
			'disable_ajax' => '0',
			'display_title' => '1',
			'slideshow_delay' => '5000',
			//'display_description' => '0',
			'display_galleries' => '0',
			//'display_share_buttons' => '0',
			'add_calendar' => '0',
			'separate_galleries' => '0',
			'show_month_galleries' => '0',
			'bg_thumb' => '#cccccc',
			'bg_modal' => '#fefefe',
			'version' => self::get_version(),
		);

		return $default_options;
	}

	/**
	 * Get the default arguments for get_display and the shortcode
	 *
	 * @return array
	 */
	public static function get_display_args_default() {
		$image_gallery_options = self::get_options();

		$default_args = array(
			'display_gallery' => '',
			'page_num' => '1',
			'page_id' => get_the_ID(),
			'parents' => '',
			'galleries' => '',
			'sort_method' => $image_gallery_options['sort_method'],
			'sort_order' => $image_gallery_options['sort_order'],
			'paginate_galleries' => $image_gallery_options['paginate_galleries'],
			'images_per_page' => $image_gallery_options['images_per_page'],
			'default_gallery_thumb' => $image_gallery_options['default_gallery_thumb'],
			'display_title' => $image_gallery_options['display_title'],
			'slideshow_delay' => $image_gallery_options['slideshow_delay'],
			'separate_galleries' => $image_gallery_options['separate_galleries'],
			'show_months' => $image_gallery_options['show_months'],
			'back_button' => '',
			'noajax' => '',
		);

		return $default_args;
	}

	/**
	 * Add calendar based galleries to new uploads
	 *
	 * @param $file
	 * @param $attachment_id
	 *
	 * @return mixed
	 */
	public static function hook_update_attached_file($file, $attachment_id) {
		$attachment_obj = get_post($attachment_id);

		if ( !empty($attachment_obj) && false !== strpos($attachment_obj->post_mime_type, 'image') ) {
			$plugin_options = self::get_options();

			// image uploaded
			if ( !empty($plugin_options['add_calendar']) ) {
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

		return $file;
	}

	/**
	 * Main Image Gallery display function
	 *
	 * @param $args
	 *
	 * @return mixed|string|void
	 */
	public static function get_display($args) {
		$output = '';

		//$args = $this->display_args_current;

		$default_args = self::get_display_args_default();

		// set args over-ride flag
		$args_override = false;
		if ( !empty($args) ) {
			$args_override = $args;
		}

		// set GET over-ride flag
		if ( !empty($_GET) ) {
			foreach ( $_GET as $_get_name => $_get_value ) {
				$get_name = esc_attr($_get_name);
				$get_value = esc_attr($_get_value);

				if ( isset($default_args[$get_name]) ) {
					$args_override[$get_name] = $get_value;
					$args[$get_name] = $get_value;
				}
			}
		}

		$args = wp_parse_args($args, $default_args);
		// update display_args_current with any changes
		//$this->display_args_current = $args;

		// optional nested galleries - build array from args if present
		$parent_galleries = array();
		if (
			!empty($args['parents'])
			&& is_string($args['parents'])
		) {
			$parent_galleries = explode(',', $args['parents']);
			if ( 1 == count($parent_galleries) ) {
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

			$term_obj = get_term($shortcut_args['term_id'], 'twobc_img_galleries');
			$shortcut_args['term_obj'] = $term_obj;
			$shortcut_args['term_title'] = $term_obj->name;
			$shortcut_args['parents'] = $args['parents'];

			$output = '<div class="twobc_image_gallery_universal_wrapper">
';
			$output .= '	<div class="twobc_image_gallery_loading"></div>
';
			$output .= '	<div class="twobc_image_gallery_overlay_wrapper show_gallery">' . self::get_gallery_html($shortcut_args) . '</div>
';
			$output .= '	<div class="twobc_image_gallery_wrapper categories_wrapper';

			// UPDATE - store args in data attribute instead of class
			if ( false !== $args && is_array($args) ) {
				$custom_data_attb = array();

				foreach ( $args as $_opt_name => $_opt_val ) {
					$_opt_name = esc_attr($_opt_name);
					$_opt_val = esc_attr($_opt_val);

					$custom_data_attb[$_opt_name] = $_opt_val;

					// build custom classes - DEPRECATED
					// TODO: remove
					/*
					switch ( $_opt_name ) {
						case 'sort_method' :
						case 'sort_order' :
						case 'paginate_galleries' :
						case 'images_per_page' :
						case 'separate_galleries' :
						case 'show_months' :
							$output .= ' ' . sanitize_html_class($_opt_name . '_' . $_opt_val);
							break;

						case 'parents' :
							if ( !empty($_opt_val) ) {
								$output .= ' ' . sanitize_html_class($_opt_name . '_');
								$parent_classes = str_replace(',', '_', $_opt_val);
								$parent_classes = str_replace(' ', '', $parent_classes);
								$output .= sanitize_html_class($parent_classes);
							}
							break;

						case 'noajax' :
							if ( !empty($_opt_val) )
								$output .= ' noajax';
							break;

						default :
					}
					*/
				}
			}



			$output .= '"';

			$custom_data_attb['gallery_count'] = $term_obj->count;

			// add custom data attribute as JSON object
			if ( !empty($custom_data_attb) )
				$output .= ' data-twobcig-args=\'' . json_encode($custom_data_attb) . '\'';

			$output .= '></div>
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
				foreach ( $optional_galleries as &$gallery_id ) {
					$gallery_id = esc_attr(trim($gallery_id));
				}
			}
		}

		$get_terms_args = array();

		if ( !empty($optional_galleries) ) {
			foreach ( $optional_galleries as $a_term_id ) {
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

		if ( !empty($term_array) ) {
			$output .= '	<div class="twobc_image_gallery_wrapper categories_wrapper';
			// add any overrides
			if ( false !== $args && is_array($args) ) {
				$custom_data_attb = array();
				
				foreach ( $args as $_opt_name => $_opt_val ) {
					$_opt_name = esc_attr($_opt_name);
					$_opt_val = esc_attr($_opt_val);

					$custom_data_attb[$_opt_name] = $_opt_val;

					// build custom classes - DEPRECATED
					// TODO: remove
					/*
					switch ( $_opt_name ) {
						case 'sort_method' :
						case 'sort_order' :
						case 'paginate_galleries' :
						case 'images_per_page' :
						case 'separate_galleries' :
						case 'show_months' :
							$output .= ' ' . sanitize_html_class($_opt_name . '_' . $_opt_val);
							break;

						case 'parents' :
							if ( !empty($_opt_val) ) {
								$output .= ' ' . sanitize_html_class($_opt_name . '_');
								$parent_classes = str_replace(',', '_', $_opt_val);
								$parent_classes = str_replace(' ', '', $parent_classes);
								$output .= sanitize_html_class($parent_classes);
							}
							break;

						case 'noajax' :
							if ( !empty($_opt_val) )
								$output .= ' noajax';
							break;

						default :
					}*/
				}
			}
			$output .= '"';

			if ( !empty($custom_data_attb) )
				$output .= ' data-twobcig-args=\'' . json_encode($custom_data_attb) . '\'';

			$output .= '>
';

			$custom_div_open = false;
			// if separate calendar galleries is active
			if (
				isset($args['separate_galleries'])
				&& '1' == $args['separate_galleries']
			) {
				// get all year-based galleries into their own container
				$years_output = array();
				foreach ( $term_array as $key => $_term_obj ) {
					// if name matches a 4 digit number, between 1900 and 2099
					if ( 1 == preg_match('/^(19|20)\d{2}$/', $_term_obj->name) ) {
						// output this term
						$get_thumb_args = array(
							'term_id' => $_term_obj->term_id,
							'term_name' => $_term_obj->name,
						);
						if ( !empty($args['parents']) )
							$get_thumb_args['parents'] = $args['parents'];
						$years_output[] = '			' . self::get_thumb_html($get_thumb_args);

						// remove from $term_array
						unset($term_array[$key]);
					}
				}

				if ( !empty($years_output) ) {
					// add opening html
					array_unshift($years_output, '		<div class="twobc_image_gallery_years">
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_year_title', __('Galleries by year', self::$plugin_text_domain)) . '</p>
');
					// add closing html
					$years_output[] = '		</div><!-- .twobc_image_gallery_years -->
';

					foreach ( $years_output as $_output ) {
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
					array( // filter argument
						__('January', self::$plugin_text_domain),
						__('February', self::$plugin_text_domain),
						__('March', self::$plugin_text_domain),
						__('April', self::$plugin_text_domain),
						__('May', self::$plugin_text_domain),
						__('June', self::$plugin_text_domain),
						__('July', self::$plugin_text_domain),
						__('August', self::$plugin_text_domain),
						__('September', self::$plugin_text_domain),
						__('October', self::$plugin_text_domain),
						__('November', self::$plugin_text_domain),
						__('December', self::$plugin_text_domain),
					)
				);

				$month_output = array();

				foreach ( $term_array as $_key => $_term_obj ) {
					// if name matches a calendar month
					foreach ( $calendar_months as $_month_key => $_month_name ) {
						if ( $_term_obj->name == $_month_name ) {
							if ( $display_months ) {
								// store this entry in the correct order
								$get_thumb_args = array(
									'term_id' => $_term_obj->term_id,
									'term_name'=> $_term_obj->name,
								);
								if (!empty($args['parents']))
									$get_thumb_args['parents'] = $args['parents'];

								$month_output[$_month_key] = self::get_thumb_html($get_thumb_args);
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
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_month_title', __('Galleries by month', self::$plugin_text_domain)) . '</p>
');
					// add closing html
					$month_output[] = '		</div><!-- .twobc_image_gallery_months -->
';

					foreach ( $month_output as $_output ) {
						$output .= $_output;
					}
				}

				// check to see if we have any custom galleries left to display
				// for custom section title
				if ( !empty($term_array) ) {
					$custom_div_open = true;
					$output .= '		<div class="twobc_image_gallery_custom">
	<p class="image_gallery_section_title">' . apply_filters('twobc_image_gallery_custom_title', __('Custom Galleries', self::$plugin_text_domain)) . '</p>
';
				}
			}

			// standard output, double check we still have terms
			if ( false === $custom_div_open ) {
				$output .= '		<div class="twobc_image_gallery_custom">
';
			}
			if ( !empty($term_array) ) {
				foreach ( $term_array as $_term_obj ) {
					$display_gallery = true;

					if ( !empty($parent_galleries) ) {
						$display_gallery = self::contains_like_terms(intval($_term_obj->term_id), $parent_galleries);
					}

					if ( $display_gallery ) {
						$get_thumb_args = array(
							'term_id' => $_term_obj->term_id,
							'term_name' => $_term_obj->name,
						);
						if ( !empty($args['parents']) )
							$get_thumb_args['parents'] = $args['parents'];

						$output .= '	' . self::get_thumb_html($get_thumb_args);
					}
				}

			}

			$output .= '	</div><!-- .twobc_image_gallery_custom -->
';

		} else {
			$output .= '	<p>' . __('No galleries to display!', self::$plugin_text_domain) . '</p>
';
		}
		$output .= '	</div><!-- .twobc_image_gallery_wrapper -->
</div><!-- .twobc_image_gallery_universal_wrapper -->
';

		return apply_filters('twobc_image_gallery_output_list', $output);
	}

	/**
	 * Get HTML for one gallery
	 *
	 * @param $args
	 *
	 * @return string
	 */
	private static function get_gallery_html($args) {
		$output = '';

		$plugin_options = self::get_options();

		$default_args = array(
			'page_id' => '-1',
			'term_obj' => '-1',
			'page_num' => '1',
			'sort_order' => $plugin_options['sort_order'],
			'sort_method' => $plugin_options['sort_method'],
			'paginate_galleries' => $plugin_options['paginate_galleries'],
			'images_per_page' => $plugin_options['images_per_page'],
			'parents' => '',
			'back_button' => '',
		);

		$args = wp_parse_args($args, $default_args);

		if ( '-1' == $args['term_obj'] || is_wp_error($args['term_obj']) )
			return $output;

		$term_obj = $args['term_obj'];

		$get_posts_args = array(
			'term_obj' => $term_obj,
			'page_num' => $args['page_num'],
			'sort_method' => $args['sort_method'],
			'sort_order' => $args['sort_order'],
			'images_per_page' => $args['images_per_page'],
			'paginate_galleries' => $args['paginate_galleries'],
			'parents' => $args['parents'],
		);

		$gallery_images = self::get_session_posts($get_posts_args);

		if ( empty($gallery_images) )
			return $output;


		$output .= '<div class="twobc_image_gallery_wrapper images_wrapper';
		// output gallery id in class for JS use in certain edge cases
		$output .= ' displayed_gallery_' . $term_obj->term_id;
		$output .= '">
';
		// gallery title
		$output .= '	<p class="twobc_image_gallery_title">';
		$output .= apply_filters('twobc_image_gallery_title', $term_obj->name);
		$output .= '</p>
';
		// back button
		switch ( true ) {
			case ('0' === $args['back_button']) :
				$back_button_url = false;
				break;

			case (!empty($args['back_button'])) :
				$back_button_url = esc_url($args['back_button']);
				break;

			case (empty($args['back_button'])) :
			default :
				$back_button_url = self::get_gallery_url($args['page_id']);
		}

		if ( !empty($back_button_url) ) {
			$output .= '	<div class="twobc_image_galleries_back_wrapper">
';
			$output .= '		<a href="' . $back_button_url . '" class="twobc_galleries_back">';
			$output .= apply_filters('twobc_image_gallery_button_back', __('&laquo; Back to galleries', self::$plugin_text_domain));
			$output .= '</a>
';
			$output .= '	</div>
';
		}

		foreach ( $gallery_images as $_image_key => $_image_obj ) {
			$attachment_obj = wp_get_attachment_image_src($_image_obj->ID, 'thumbnail');
			$attachment_obj_full = wp_get_attachment_image_src($_image_obj->ID, 'full');

			$output .= '	<a href="' . $attachment_obj_full[0] . '" class="thumb_wrapper image_' . $_image_obj->ID . '"';
			// output width in style tag here
			// get current thumb size
			$current_thumb_size = self::get_image_sizes('thumbnail');
			if ( !empty($current_thumb_size) ) {
				$output .= ' style="';
				$output .= 'width:' . $current_thumb_size['width'] . 'px';
				$output .= '"';
			}


			// add next and previous attributes for slideshow
			$output .= ' data-twobcig-gallery="' . (isset($term_obj->term_id) ? $term_obj->term_id : '') . '"';

			if ( empty($args['paginate_galleries']) ) {
				$dynamic_index = $_image_key;
			} else {
				$dynamic_index = (($args['page_num'] - 1) * $args['images_per_page']) + $_image_key;
			}

			$output .= ' data-twobcig-index="' . $dynamic_index . '"';
			
			$output .= ' data-twobcig-count="' . $term_obj->count . '"';

			$output .= '>';
			$output .= '<img src="' . esc_url($attachment_obj[0]) . '" height="' . $attachment_obj[2] . '" width="' . $attachment_obj[1] . '" alt="Thumbnail for ' . esc_attr($_image_obj->post_title) . '">';
			$output .= '<span class="tax_title"> ' . esc_attr($_image_obj->post_title) . ' </span>';
			$output .= '</a>
';
		}

		// optional page buttons
		if ( '1' == $args['paginate_galleries'] ) {
			$output .= '	<div class="gallery_page_buttons cf">';
			// calculate last page estimate
			$images_total = $args['term_obj']->count;
			$last_page_estimate = intval(ceil($images_total / $args['images_per_page']));

			// static first page
			$page_buttons_array = array(
				0 => array(
					'title' => '1',
					'url' => self::get_gallery_url(
						$args['page_id'], // page id
						array( // query args
							'display_gallery' => $term_obj->term_id,
							'page_num' => '1',
						)
					),
					'current' => ('1' == $args['page_num'] ? true : false),
				),
			);

			// build the rest of the page buttons array
			if ( 9 >= $last_page_estimate ) { // display the pages as is
				for ( $i = 1; $i < $last_page_estimate; $i++ ) {
					$page_num = $i + 1;
					$page_buttons_array[$i] = array(
						'title' => $page_num,
						'url' => self::get_gallery_url(
							$args['page_id'], // page id
							array( // query args
								'display_gallery' => $term_obj->term_id,
								'page_num' => $page_num,
							)
						),
						'current' => ($page_num == $args['page_num'] ? true : false),
					);
				}
			} else { // else, we need ellipses
				// first ellipses check
				if ( 5 < $args['page_num'] ) {
					$page_buttons_array[] = array(
						'title' => '<span>&hellip;</span>',
						'url' => '',
					);
				}
				$current_array_pos = intval(count($page_buttons_array));

				// get the rest of the pages
				$continue_processing = true;
				$loop_counter = 0;
				while ( $continue_processing ) {
					switch ( true ) {
						case (5 >= $args['page_num']) : // get the first 5 pages, after static first
							$page_num = 2 + $loop_counter;
							if ( $loop_counter >= 4 ) {
								$continue_processing = false;
							}
							break;

						case ($last_page_estimate - 4 <= $args['page_num']) : // get the last 5 pages, before static last
							$page_num = ($last_page_estimate - 5) + $loop_counter;
							if ( $loop_counter >= 4 ) {
								$continue_processing = false;
							}
							break;

						default :
							$page_num = ($args['page_num'] - 3) + $loop_counter; // get previous 3, current, and next 3 pages
							if ( $loop_counter >= 6 ) {
								$continue_processing = false;
							}
					}
					$page_buttons_array[$current_array_pos] = array(
						'title' => strval($page_num),
						'url' => self::get_gallery_url(
							$args['page_id'], // page id
							array( // query args
								'display_gallery' => $term_obj->term_id,
								'page_num' => $page_num,
							)
						),
						'current' => ($page_num == $args['page_num'] ? true : false),
					);
					$current_array_pos++;
					$loop_counter++;
				}
				// last ellipses check
				if ( ($last_page_estimate - 5) >= $args['page_num'] ) {
					$page_buttons_array[] = array(
						'title' => '<span>&hellip;</span>',
						'url' => '',
					);
				}

				// static last page
				$page_buttons_array[] = array(
					'title' => $last_page_estimate,
					'url' => self::get_gallery_url(
						$args['page_id'], // page id
						array( // query args
							'display_gallery' => $term_obj->term_id,
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
					$output .= self::get_gallery_url(
						$args['page_id'], // page id
						array( // query args
							'display_gallery' => $term_obj->term_id,
							'page_num' => $page_previous
						)
					);
					$output .= '"';
					$output .= ' class="previous_page"';
					$output .= '>';
					$output .= apply_filters('twobc_image_gallery_previous_page_button', '&laquo;');
					$output .= '</a>';
				}

				foreach ( $page_buttons_array as $_page_button ) {
					//$output .= '		';
					if ( !empty($_page_button['url']) ) {
						$output .= '<a';
						$output .= ' href="' . esc_url($_page_button['url']) . '"';
						if ( true === $_page_button['current'] ) {
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
					$output .= self::get_gallery_url(
						$args['page_id'], // page id
						array( // query args
							'display_gallery' => $term_obj->term_id,
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
		$output .= '</div><!-- .twobc_image_gallery_wrapper -->
';

		return $output;

	}

	/**
	 * Get HTML for one thumb
	 *
	 * @param $args
	 *
	 * @return mixed|void
	 */
	private static function get_thumb_html($args) {
		//$passed_args = $this->display_args_current;

		$output = '';

		$default_args = array(
			'term_id' => '',
			'term_name' => '',
			'parents' => '',
		);

		$args = wp_parse_args($args, $default_args);

		$plugin_options = self::get_options();

		// try to get gallery featured image
		$cat_thumb_id = get_option('taxonomy_' . $args['term_id']);
		$cat_thumb_id = (!empty($cat_thumb_id['gallery_featured_img']) ? $cat_thumb_id['gallery_featured_img'] : null);

		// if no image present, get an image according to the plugin settings - first, last, random
		if ( empty($cat_thumb_id) ) {
			$orderby = 'date';
			$order = 'DESC';
			if ( !empty($plugin_options['default_gallery_thumb']) ) {
				switch ( $plugin_options['default_gallery_thumb'] ) {
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
			$get_posts_args = array(
				'posts_per_page' => 1,
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'tax_query' => array(
					array(
						'taxonomy' => 'twobc_img_galleries',
						'terms' => $args['term_id'],
					),
				),
				'order' => $order,
				'orderby' => $orderby,
			);

			// update - filter thumb by parents, if present
			if ( !empty($args['parents']) ) {
				$parents = explode(',', $args['parents']);
				if ( !empty($parents) ) {
					$get_posts_args['tax_query']['relation'] = 'AND';
					foreach ( $parents as $_parent ) {
						if ( !empty($_parent) ) {
							$get_posts_args['tax_query'][] = array(
								'taxonomy' => 'twobc_img_galleries',
								'field' => 'id',
								'terms' => trim($_parent),
							);
						}
					}
				}
			}


			$cat_thumb_id = get_posts($get_posts_args);
			if ( !empty($cat_thumb_id) ) {
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
			$output .= ' href="' . twobc_image_gallery::get_gallery_url(
				$post->ID, // page id
				array(
					'display_gallery' => esc_attr($args['term_id']),
				)
			) . '"';
			$output .= ' class="thumb_wrapper';
			$output .= ' gallery_' . esc_attr($args['term_id']);
			$output .= '"';
			// output width in style tag here
			// get current thumb size
			$current_thumb_size = self::get_image_sizes('thumbnail');
			if ( !empty($current_thumb_size) ) {
				$output .= ' style="';
				$output .= 'width:' . $current_thumb_size['width'] . 'px';
				$output .= '"';
			}

			$output .= '>';
			$output .= '<img src="' . esc_url($cat_thumb_obj[0]) . '" height="' . esc_attr($cat_thumb_obj[1]) . '" width="' . esc_attr($cat_thumb_obj[2]) . '" alt="Thumbnail for ' . esc_attr($args['term_name']) . ' gallery">';
			$output .= '<span class="tax_title"> ' . esc_html($args['term_name']) . ' </span>';
			$output .= '</a>
';
		}

		return apply_filters('twobc_image_gallery_output_thumb', $output);
	}

	/**
	 * AJAX callback - HTML for one image (modal)
	 */
	public static function image_ajax_callback() {
		// verify nonce
		check_ajax_referer('twobc_image_gallery_ajax', 'ajax_nonce');

		$plugin_options = twobc_image_gallery::get_options();

		// figure out how many columns we're going to have
		// 1 column for slideshow buttons, always
		$cols = 1;

		// 1 column for title / galleries
		if ( !empty($plugin_options['display_title']) || !empty($plugin_options['display_galleries']) )
			$cols++;

		// 1 column for description
		if ( !empty($plugin_options['display_description']) )
			$cols++;

		$output = '';
		//$image_id = esc_attr($_POST['image_id']);

		//$current_args = $this->display_args_current;


		// get previous and next images
		$current_index = (isset($_POST['twobcig_index']) ? intval($_POST['twobcig_index']) : '');
		$current_gallery = (!empty($_POST['twobcig_gallery']) ? intval($_POST['twobcig_gallery']) : '');
		$current_sort_method = (!empty($_POST['twobcig_sort_method']) ? esc_attr($_POST['twobcig_sort_method']) : '');
		$current_sort_order = (!empty($_POST['twobcig_sort_order']) ? esc_attr($_POST['twobcig_sort_order']) : '');
		$current_page_num = ( !empty($_POST['twobcig_page_num']) && is_numeric($_POST['twobcig_page_num']) ? intval($_POST['twobcig_page_num']) : '' );
		$gallery_count = ( !empty($_POST['twobcig_gallery_count']) && is_numeric($_POST['twobcig_gallery_count']) ? intval($_POST['twobcig_gallery_count']) : '' );
		$current_pagination = ( !empty($_POST['twobcig_paginate_galleries']) ? esc_attr($_POST['twobcig_paginate_galleries']) : '' );
		$current_images_per_page = ( !empty($_POST['twobcig_images_per_page']) ? intval($_POST['twobcig_images_per_page']) : '' );
		$current_parents = ( !empty($_POST['twobcig_parents']) ? esc_attr($_POST['twobcig_parents']) : '' );



		$get_posts_args = array(
			'term_obj' => get_term($current_gallery, 'twobc_img_galleries'),
			'current_index' => $current_index,
			'page_num' => $current_page_num,
			'sort_order' => $current_sort_order,
			'sort_method' => $current_sort_method,
			'paginate_galleries' => $current_pagination,
			'images_per_page' => $current_images_per_page,
			'parents' => $current_parents,
		);

		$this_image = self::get_session_posts($get_posts_args);

		if ( empty($this_image) )
			die(__('ERROR: Could not get image from database', self::$plugin_text_domain));

		$this_image = reset($this_image);

		$attachment_obj = wp_get_attachment_image_src($this_image->ID, 'full');

		// calculate previous and next indexes for buttons
		//$image_count = count($all_images);
		$index_prev = ( 0 == $current_index ? $gallery_count - 1 : ($current_index - 1) );
		$index_next = ( ($gallery_count - 1) == $current_index ? 0 : ($current_index + 1) );


		// BEGIN OUTPUT
		$output .= '<div class="twobc_ig_modal_wrapper">
';
		$output .= '	<img src="' . esc_url($attachment_obj[0]) . '" height="' . $attachment_obj[2] . '" width="' . $attachment_obj[1] . '" class="twobc_ig_modal_image">
';
		// modal info area
		$output .= '	<div class="twobc_ig_modal_info_wrapper">
';

		// social share buttons


		// slideshow buttons
		$output .= '		<div class="twobc_ig_modal_buttons_wrapper twobc_ig_cols' . $cols . '">
';


		// previous button
		$output .= '			<a href="" class="twobc_ig_modal_prev_button twobc_ig_modal_button"';
		$output .= ' data-twobcig-gallery="' . $current_gallery . '"';
		$output .= ' data-twobcig-index="' . $index_prev . '"';
		$output .= ' data-twobcig-sortorder="' . $current_sort_order . '"';
		$output .= ' data-twobcig-sortmethod="' . $current_sort_method . '"';
		$output .= '>';
		//$output .= '<img src="' . self::$plugin_url . 'includes/images/button_previous.png" height="40" width="40" alt="Previous image button">';
		$output .= '</a>
';

		// play button
		$output .= '			<a href="" class="twobc_ig_modal_play_button twobc_ig_modal_button">';
		//$output .= '<img src="' . self::$plugin_url . 'includes/images/button_play.png" height="60" width="60" alt="Play/Pause image slideshow button">';
		$output .= '</a>
';

		// next button
		$output .= '			<a href="" class="twobc_ig_modal_next_button twobc_ig_modal_button"';
		$output .= ' data-twobcig-gallery="' . $current_gallery . '"';
		$output .= ' data-twobcig-index="' . $index_next . '"';
		$output .= ' data-twobcig-sortorder="' . $current_sort_order . '"';
		$output .= ' data-twobcig-sortmethod="' . $current_sort_method . '"';
		$output .= '>';
		//$output .= '<img src="' . self::$plugin_url . 'includes/images/button_next.png" height="40" width="40" alt="Next image button">';
		$output .= '</a>
';

		$output .= '		</div>
';
		$output .= '</div>
';


		// image file name
		if ( !empty($plugin_options['display_title']) ) {
			$output .= '<p>';
			$output .= get_the_title($this_image->ID);

			// image galleries

			$output .= '</p>
	';
		}

		// image description


		$output .= '</div>
';
		echo apply_filters('twobc_image_gallery_output_modal', $output);

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * AJAX callback - HTML for one gallery of images
	 */
	public static function gallery_ajax_callback() {
		// verify nonce
		check_ajax_referer('twobc_image_gallery_ajax', 'ajax_nonce');

		$output = '';
		$args = array();

		$args['page_id'] = esc_attr($_POST['twobcig_page_id']);
		$args['term_id'] = esc_attr($_POST['twobcig_gallery']);

		if ( !empty($args['term_id']) ) {
			$args['term_obj'] = get_term($args['term_id'], 'twobc_img_galleries');
			//$args['term_title'] = get_term($args['term_id'], 'twobc_img_galleries');
			//$args['term_title'] = $args['term_title']->name;
			$args['page_num'] = intval(esc_attr($_POST['twobcig_page_num']));
			$args['sort_order'] = (!empty($_POST['twobcig_sort_order']) ? esc_attr($_POST['twobcig_sort_order']) : '');
			$args['sort_method'] = (!empty($_POST['twobcig_sort_method']) ? esc_attr($_POST['twobcig_sort_method']) : '');
			$args['paginate_galleries'] = (!empty($_POST['twobcig_paginate_galleries']) ? esc_attr($_POST['twobcig_paginate_galleries']) : '');
			$args['images_per_page'] = (!empty($_POST['twobcig_images_per_page']) ? esc_attr($_POST['twobcig_images_per_page']) : '');
			$args['back_button'] = ('0' === ($_POST['twobcig_back_button']) ? esc_attr($_POST['twobcig_back_button']) : '');
			if ( !empty($_POST['twobcig_parents']) )
				$args['parents'] = esc_attr($_POST['twobcig_parents']);
		}


		$output .= self::get_gallery_html($args);

		echo apply_filters('twobc_image_gallery_output_ajax', $output);

		die(); // this is required to terminate immediately and return a proper response
	}


	/**
	 * Get posts appropriate according to the args
	 *
	 * @param $args
	 *
	 * @return array|bool
	 */
	private static function get_session_posts($args) {
		$plugin_options = self::get_options();

		$default_args = array(
			'term_obj' => '-1',
			'page_num' => '1',
			'sort_order' => $plugin_options['sort_order'],
			'sort_method' => $plugin_options['sort_method'],
			'paginate_galleries' => $plugin_options['paginate_galleries'],
			'images_per_page' => $plugin_options['images_per_page'],
			'parents' => '',
			'current_index' => '',
		);

		$args = wp_parse_args($args, $default_args);


		// exit values
		// no term object provided
		if ( empty($args['term_obj']) || is_wp_error($args['term_obj']) )
			return false;

		$term_obj = $args['term_obj'];

		if ( !empty($args['paginate_galleries']) && is_numeric($args['images_per_page']) ) {
			$posts_per_page = intval($args['images_per_page']);
			$offset = ($args['page_num'] - 1) * $posts_per_page;
		} else {
			$posts_per_page = -1;

		}

		// single image request
		if ( isset($args['current_index']) && is_numeric($args['current_index']) ) {
			$posts_per_page = 1;
			$offset = $args['current_index'];
		}

		$get_posts_args = array(
			'posts_per_page' => $posts_per_page,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'tax_query' => array(
				array(
					'taxonomy' => 'twobc_img_galleries',
					'field' => 'id',
					'terms' => intval($term_obj->term_id),
				)
			),
			'orderby' => $args['sort_method'],
			'order' => $args['sort_order'],
		);

		if ( !empty($offset) )
			$get_posts_args['offset'] = $offset;

		if ( !empty($args['parents']) ) {
			$get_posts_args['tax_query']['relation'] = 'AND';

			$parents = explode(',', $args['parents']);
			if ( !empty($parents) ) {
				foreach ( $parents as $_parent ) {
					$get_posts_args['tax_query'][] = array(
						'taxonomy' => 'twobc_img_galleries',
						'field' => 'id',
						'terms' => intval(trim($_parent)),
					);
				}
			}

		}

		$gallery_images = get_posts($get_posts_args);

		return $gallery_images;
	}

	/**
	 * Check to see if one gallery has terms from another gallery
	 *
	 * @param $term_haystack
	 * @param $term_needle
	 *
	 * @return bool
	 */
	private static function contains_like_terms($term_haystack, $term_needle) {
		$return = false;

		$term_haystack_obj = self::get_gallery_obj($term_haystack);
		if ( is_array($term_needle) ) {
			$term_needle_obj = array();
			foreach ( $term_needle as $_needle ) {
				$term_needle_obj[] = self::get_gallery_obj($_needle);
			}
		} else {
			$term_needle_obj[] = self::get_gallery_obj($term_needle);
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
			foreach ( $term_needle_obj as $_needle ) {
				$tax_query_args['tax_query'][] = array(
					'taxonomy' => 'twobc_img_galleries',
					'field' => 'id',
					'terms' => $_needle->term_id,
				);
			}

			$tax_query = new WP_Query($tax_query_args);

			if ( 0 < $tax_query->post_count ) {
				$return = true;
			}
		}

		return $return;
	}

	/**
	 * Get details of all or one registered image size
	 *
	 * @param string $size
	 *
	 * @return array|bool
	 */
	private static function get_image_sizes($size = '') {
		global $_wp_additional_image_sizes;
		$sizes = array();
		$get_intermediate_image_sizes = get_intermediate_image_sizes();
		// Create the full array with sizes and crop info
		foreach ( $get_intermediate_image_sizes as $_size ) {
			if (
				in_array(
					$_size, // haystack
					array( // needles
						'thumbnail',
						'medium',
						'large'
					)
				)
			) {
				$sizes[$_size]['width'] = get_option($_size . '_size_w');
				$sizes[$_size]['height'] = get_option($_size . '_size_h');
				$sizes[$_size]['crop'] = (bool)get_option($_size . '_crop');

			} elseif ( isset($_wp_additional_image_sizes[$_size]) ) {
				$sizes[$_size] = array(
					'width' => $_wp_additional_image_sizes[$_size]['width'],
					'height' => $_wp_additional_image_sizes[$_size]['height'],
					'crop' => $_wp_additional_image_sizes[$_size]['crop']
				);

			}

		}

		$return = $sizes;

		// Get only 1 size if found
		if ( $size ) {
			if ( isset($sizes[$size]) ) {

				$return = $sizes[$size];
			} else {
				$return = false;
			}
		}

		return $return;
	}

	/**
	 * Get the term object by term id, slug, or name
	 *
	 * @param $term_identifier
	 *
	 * @return bool|mixed|null|WP_Error
	 */
	private static function get_gallery_obj($term_identifier) {
		if ( is_int($term_identifier) ) {
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

		if ( !empty($term_test) && !is_wp_error($term_test) ) {
			return $term_test;
		} else {
			return false;
		}
	}

	/**
	 * Register and process the shortcode
	 *
	 * @param $atts
	 *
	 * @return mixed|string|void
	 */
	public static function register_shortcode($atts) {
		$default_args = self::get_display_args_default();

		$atts = shortcode_atts(
			$default_args, // default attributes
			$atts, // incoming,
			'2bc_image_gallery' // optional shortcode name, include to add shortcode_atts_$shortcode filter
		);

		return self::get_display($atts);
	}

	/**
	 * Add custom CSS from plugin options to wp_head
	 */
	public static function custom_css() {
		$output = '';

		$plugin_options = self::get_options();

		$bg_thumb = ( !empty($plugin_options['bg_thumb']) && '#cccccc' != $plugin_options['bg_thumb'] ? $plugin_options['bg_thumb'] : '' );
		$bg_modal = (!empty($plugin_options['bg_modal']) && '#fefefe' != $plugin_options['bg_modal'] ? $plugin_options['bg_modal'] : '');

		if ( !empty($bg_thumb) || !empty($bg_modal) ) {
			$output = '<style type="text/css">';

			if ( !empty($bg_thumb) )
				$output .= '.twobc_image_gallery_wrapper .thumb_wrapper {background:' . $bg_thumb . '}';

			if ( !empty($bg_modal) )
			$output .= '.twobc-pico-content{background:' . $bg_modal . '}';

			$output .= '</style>';
		}

		if ( !empty($output) ) {
			echo $output;
		}
	}

} // END OF CLASS - twobc_image_gallery

/**
 * 2BC Image Gallery - display accessor
 *
 * @param $args | array (
 *            display_gallery' => '',
 *            'page_num' => '1',
 *            'page_id' => get_the_ID(),
 *            'parents' => '',
 *            'galleries' => '',
 *            'sort_method' => $image_gallery_options['sort_method'],
 *            'sort_order' => $image_gallery_options['sort_order'],
 *            'paginate_galleries' => $image_gallery_options['paginate_galleries'],
 *            'images_per_page' => $image_gallery_options['images_per_page'],
 *            'default_gallery_thumb' => $image_gallery_options['default_gallery_thumb'],
 *            'display_title' => $image_gallery_options['display_title'],
 *            'slideshow_delay' => $image_gallery_options['slideshow_delay'],
 *            'separate_galleries' => $image_gallery_options['separate_galleries'],
 *            'show_months' => $image_gallery_options['show_months'],
 *            'back_button' => '',
 *            'noajax' => '',
 * )
 *
 *
 * @return mixed|string|void
 */
function twobc_image_gallery_get_display($args) {
	$default_args = twobc_image_gallery::get_display_args_default();

	$args = wp_parse_args($args, $default_args);

	return twobc_image_gallery::get_display($args);
}
