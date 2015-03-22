=== 2BC Image Gallery ===
Contributors: 2bc_jason, 2bc_aron
Donate link: http://2bcoding.com/donate-to-2bcoding
Tags: 2bcoding, 2bc, image, gallery, image gallery, lightbox, gallery lightbox, ajax, javascript, responsive, mobile view, media, automatic, tags, categories, shortcode, slideshow, slider, slides, modal
Author URI: http://2bcoding.com
Plugin URI: http://2bcoding.com/plugins/2bc-image-gallery
Requires at least: 3.6
Tested up to: 4.1.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add tags to images and group them into galleries, easily set options to display the lightbox galleries, or use the shortcode

== Description ==

The [2BC Image Gallery](http://2bcoding.com/plugins/2bc-image-gallery/) WordPress plugin is designed to add tags to images in the WordPress media library. Once the images are tagged and grouped into galleries, there are several options to display galleries throughout the site.

The default styling includes a lightbox to open the images in a modal window with a slideshow and back/next buttons.  The gallery screens are loaded via AJAX, with a loading icon to let you know it's working. The display is designed to be responsive friendly, meaning it should work well with responsive themes on mobile devices. We intend to add more options around styling as we update the plugin.

= Features =

The 2BC Image Gallery allows for tagging of photos (including automatic tagging of uploaded photos) with date appropriate tags.  Custom tags can be added as well by editing an image and changing the *Galleries* section.  After a few galleries have been created, it's very simple to display them on various pages.  The gallery viewer has been designed with the following features:

* Lightbox gallery - All images open in a modal/lightbox view
* Slideshow and Forward / Back buttons for easy browsing
* AJAX driven - Galleries and images will appear without the page having to refresh, making for a quicker and smoother experience
* Responsive - Ready for viewing on mobile phones or tablets in responsive themes
* Lots of options - Use the settings page, or get technical with the shortcode or function call
* More options to come - We already have some ideas to make this plugin even better, please visit <http://2bcoding.com> to give suggestions or feedback, or use the WordPress support feature

= Documentation =

The [2BC Image Gallery documentation page](http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-documentation) contains an explanation of all the settings, as well as available arguments via the shortcode and function calls.  Any actions or filters that are included in the 2BC Image Gallery are also discussed here.

= Demo =

The [2BC Image Gallery demo page](http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-demo) has been setup to show what the plugin will look like with different settings, as well as what is possible via the settings screen and the shortcode call.

== Installation ==

The *2BC Image Gallery* can be installed via the WordPress plugin repository (automatic), or by uploading the files directly to the web server (manual).

= Automatic =

1. [Log in to the WordPress administration panel](https://codex.wordpress.org/First_Steps_With_WordPress#Log_In) with an administrator account
2. Click **Plugins** > **Add New**
3. Search for *2BC Image Gallery*
4. Find the plugin in the list of results and click the **Install Now** button
5. Click **OK** to confirm the plugin installation.  If there are any file permission issues, WordPress may ask for a valid FTP account to continue.  Either enter the FTP credentials, or proceed to the Manual installation instructions.
6. Click the **Activate Plugin** link after the installation is complete

= Manual =

1. [Download a copy of the plugin](https://wordpress.org/plugins/2bc-image-gallery/) and save it to the local computer.  Make sure that the folder has been unzipped.
2. [Using an FTP program or cPanel](https://codex.wordpress.org/FTP_Clients) (a good program for FTP is [FileZilla](https://filezilla-project.org/)), connect to the server that is hosting the website
3. Find the root folder for the site and browse to the following directories: **wp-content** > **plugins**
4. Upload the un-compressed *2bc-image-gallery* folder in to the *plugins* folder on the server
5. [Log in to the WordPress administration panel](https://codex.wordpress.org/First_Steps_With_WordPress#Log_In) with an administrator account
6. Click **Plugins** > **Installed Plugins**
7. Find the *2BC Image Gallery* plugin in the list and click the **Activate** link

Visit the [2BC Image Gallery documentation page](http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-documentation/) for instructions on how to configure the settings and display galleries once the installation is complete.

== Frequently Asked Questions ==

= How do I add images to galleries? =

To manually apply tags to images, click **Media** and edit an image.  The **Galleries** text box will appear in the right sidebar.  Enter a comma separated list of galleries to add to this image.  The galleries may appear in lower-case letters, but will save according to what is entered.  If the image is opened in the post editor, then the familiar tag editor will appear instead of the text box.

To automatically apply calendar-based galleries to uploaded images, click **Settings** > **2BC Image Gallery**, check **Add Calendar Based Galleries**, then click **Save Changes**.

= How do I customize the image that displays for the gallery thumbnail? =

**Setting a custom thumbnail**

1. Click **Media** > **Galleries**
2. Click the title of the gallery that should be modified
3. Scroll down to *Gallery Featured Image* and click **Choose or Upload an image**
4. Click an image from the media library or upload a file, and click **Use this image**
5. Click the **Update** button

**Change default thumbnails**

1. Click **Settings** > **2BC Image Gallery**
2. Click the **Gallery Thumb Source** and choose to get the *Last image added* to the gallery (default), *First image added*, or *Random*

= How can I change the styling of the gallery? =

There is currently one default style.  The recommend method to apply custom styling is to access the *2BC Image Gallery* settings page, and enable **Hide Default Styling**.  It should then be possible to write custom rules in the themes CSS files.

= How can I change the month, year, or custom section titles? =

We have included filters to allow for customizing just about everything.  In this case, there are two filters: one for the year gallery title, and one for the month gallery title.  To change the **year section title**, include the following in your themes `functions.php` file:

`add_filter('twobc_image_gallery_year_title', 'my_custom_year_title_function');
function my_custom_year_title_function($year_title) {
	$year_title = 'A New Custom Title For The Years Gallery Section';	
	return $year_title;
}`

For the **month section title**, include the following in your themes `functions.php` file:

`add_filter('twobc_image_gallery_month_title', 'my_custom_month_title_function');
function my_custom_month_title_function($month_title) {
	$month_title = 'A New Custom Title For The Months Gallery Section';
	return $month_title;
}`

For the **custom section title**, include the following in your themes `functions.php` file:

`add_filter('twobc_image_gallery_custom_title', 'my_custom_gallery_title_function');
function my_custom_gallery_title_function($custom_title) {
	$custom_title = 'A New Title For The Custom Gallery Section';
	return $custom_title;
}`

= How can I change the &laquo; Back to galleries button text? =

Include the following in your themes `functions.php` file:

`add_filter('twobc_image_gallery_button_back', 'my_custom_back_gallery_function');
function my_custom_back_gallery_function($button_text) {
	// default text = &amp;laquo; Back to galleries
	$button_text = 'New Back To Gallery Button Text';
	return $button_text;
}`

= How can I change the previous (&laquo;) or next (&raquo;) page buttons? =

Include the following in your themes `functions.php` file:

**Previous page button:**

`add_filter('twobc_image_gallery_previous_page_button', 'my_custom_previous_button_function');
function my_custom_previous_button_function($button_text) {
	// default text = &amp;laquo;
	$button_text = 'New Previous Page Button Text';
	return $button_text;
}`

**Next page button:**

`add_filter('twobc_image_gallery_next_page_button', 'my_custom_next_button_function');
function my_custom_next_button_function($button_text) {
	// default text = &amp;raquo;
	$button_text = 'New Next Page Button Text';
	return $button_text;
}`

== Screenshots ==

1. A screen shot of the [2BC Image Gallery demo page](http://2bcoding.com/plugins/2bc-image-gallery/2bc-image-gallery-demo/)
2. A screen shot of an image opened in the lightbox modal window
3. The 2BC Image Gallery settings screen installed in WordPress 4.0

== Other Notes ==

* Requires WordPress 3.6, and PHP 5+

== Changelog ==

= 2.0.0 =
* Rewrite entire plugin to be in custom class
* Added custom thumb background color
* Added twobc_wpadmin_input_fields for option fields
* Added Modal Options - title and background color
* Added slideshow to lightbox modal, with next and previous buttons
* Added slideshow delay option
* Edits to default cosmetic style
* Edits to admin style
* Customized PicoModal call to avoid conflicts
* Adjusted height of modal to work with new information
* New resizer function for modal
* Added accessors for plugin version and plugin default options instead of using globals
* Added shortcode atts to default filter - shortcode_atts_2bc_image_gallery
* Fixed issue with parsing default URL's
* Updated POT language file
* Created us_EN translations
* Updated tags

= 1.0.1 =
* Adding additional items to gettext filter for translation
* Fixed reference to lang folder

= 1.0.0 =
* Launch of the official plugin

== Upgrade Notice ==

= 2.0.0 =
Improves the look of the modal screen with a slideshow and adds more options

= 1.0.1 =
Fixes minor bugs with translations

= 1.0.0 =
Launch of the official plugin