jQuery(document).ready(function($) {
    // Image upload button click event
    $(document).on('click', '.image-upload-button', function(e) {
      e.preventDefault();
  
      var button = $(this);
      var customUploader = wp.media({
        title: 'Upload Image',
        button: {
          text: 'Select Image'
        },
        multiple: false // Set to true if you want to allow multiple image uploads
      }).on('select', function() {
        var attachment = customUploader.state().get('selection').first().toJSON();
        var previewContainer = button.closest('.image-upload-field').find('.image-preview');
  
        // Set the value of the hidden input field
        button.closest('.image-upload-field').find('input[type="hidden"]').val(attachment.id);
  
        // Display the selected image preview
        previewContainer.html('<img src="' + attachment.url + '" alt="Preview Image">');
      }).open();
    });

    $(document).on('click', '.btn_action', function () {
      let status = $(this).attr('id') == 'cancel' ? 'cancelled' : 'on-hold';

      console.log(customAjax)
      $.ajax({
        url: customAjax.ajaxUrl,
        type: 'POST',
        data: {
          action: 'change_order_status',
          nonce: customAjax.nonce,
          order_id: $('#order_id_inp').val(),
          status
        },
        success: function(response) {
          window.open('/shop', '_self');
        },
        error: function(error) {
          console.log(error);
          // Handle error if necessary
        }
      });
    });
  });
  