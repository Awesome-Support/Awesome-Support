jQuery(document).ready(function ($) {

	//////////////////////////
	// MailChimp Form JSON //
	// http://stackoverflow.com/a/15120409
	//////////////////////////
	var $modal = $('#wpas-extensions-modal'),
		$modalContent = $('#wpas-extensions-modal-content'),
		$form = $('#wpas-extensions-form'),
		$formSubmit = $form.find('[type="submit"]');

	$form.submit(function () {
		$formSubmit.prop('disabled', true).text('Submitting...');
		$.ajax({
			type: $form.attr('method'),
			url: $form.attr('action').replace('/post?', '/post-json?').concat('&c=?'),
			data: $form.serialize(),
			cache: false,
			dataType: 'jsonp',
			error: function (err) {
				console.log(err);
				alert.log('Could not connect to the registration server. Please try again later.');
				$formSubmit.prop('disabled', false).text('Notify me');
			},
			success: function (data) {
				console.log(data);
				if (data.result != 'success') {
					alert(data.msg);
					$formSubmit.prop('disabled', false).text('Notify me');
				} else {
					$modalContent.html('<div class="wpas-alert-success"><h2><span class="dashicons dashicons-yes"></span> Please confirm your email :)</h2><p class="wpas-lead">' + data.msg + '</p></div><div class="button-secondary" onclick="javascript:tb_remove();">Close this popup</div>');
				}
			}
		});
		return false;
	});

});