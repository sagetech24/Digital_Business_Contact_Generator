jQuery(document).ready(function($) {
    $('#upload_bg_image_button').click(function(e) {
        e.preventDefault();
        const image_frame = wp.media({
            title: 'Select or Upload Background Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use this image' }
        });

        image_frame.on('select', function() {
            const attachment = image_frame.state().get('selection').first().toJSON();
            $('#background_image').val(attachment.url);
            $('#bg_image_preview').attr('src', attachment.url);
        });

        image_frame.open();
    });

    $('#upload_top_logo_image_button').click(function(e) {
        e.preventDefault();
        const image_frame = wp.media({
            title: 'Select or Upload Top Logo Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use this image' }
        });

        image_frame.on('select', function() {
            const attachment = image_frame.state().get('selection').first().toJSON();
            $('#top_logo_image').val(attachment.url);
            $('#top_logo_image_preview').attr('src', attachment.url);
        });

        image_frame.open();
    });

    $('#upload_bottom_logo_image_button').click(function(e) {
        e.preventDefault();
        const image_frame = wp.media({
            title: 'Select or Upload Bottom Logo Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use this image' }
        });

        image_frame.on('select', function() {
            const attachment = image_frame.state().get('selection').first().toJSON();
            $('#bottom_logo_image').val(attachment.url);
            $('#bottom_logo_image_preview').attr('src', attachment.url);
        });

        image_frame.open();
    });
});
