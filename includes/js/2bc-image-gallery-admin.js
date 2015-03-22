/*
 * Attaches the image uploader to the input field
 */
jQuery(document).ready(function ($) {

	// Instantiates the variable that holds the media library frame.
	var meta_image_frame;

	// Runs when the image button is clicked.
	$('#twobc-image-gallery-featured-image-button').click(function (e) {

		// Prevents the default action from occurring.
		e.preventDefault();

		// If the frame already exists, re-open it.
		if (meta_image_frame) {
			meta_image_frame.open();
			return;
		}

		// Sets up the media library frame
		meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
			title: meta_image.title,
			button: { text: meta_image.button },
			library: { type: 'image' }
		});

		// Runs when an image is selected.
		meta_image_frame.on('select', function () {

			// Grabs the attachment selection and creates a JSON representation of the model.
			var media_attachment = meta_image_frame.state().get('selection').first().toJSON();

			// Sends the attachment URL to our custom image input field.

			var meta_object = document.getElementById('twobc_img_galleries_meta[gallery_featured_img]');
			meta_object.value = media_attachment.id;
		});

		// Opens the media library frame.
		meta_image_frame.open();
	});

	// Color picker fields
	var cp_options = {
		defaultColor: "#cccccc",
		palettes: true
	};
	var colorpicker = $('.twobcig_color_picker').get(0);
	if(colorpicker) {
		$('.twobcig_color_picker').wpColorPicker(cp_options);
	}
});