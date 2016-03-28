(function ($) {
	"use strict";

	$(function () {

		/*
		Limit maximum items on a multiple input
		http://stackoverflow.com/a/10105631
		 */
		if (typeof wpas !== 'undefined' && wpas.fileUploadMax) {
			var $fileUpload = $('#wpas_files');
			$fileUpload.on('change', function (event) {
				event.preventDefault();

				// Check file input size with jQuery | http://stackoverflow.com/a/3937404
				var bigFiles = [];
				$.each($fileUpload.get(0).files, function (index, val) {
					if (val.size > wpas.fileUploadSize) {
						bigFiles.push(val.name);
					}
				});
				if (bigFiles.length !== 0) {
					alert(wpas.fileUploadMaxSizeError[0] + '\n\n' + bigFiles.join('\n') + '.\n\n' + wpas.fileUploadMaxSizeError[1]);
					clearFileInput($fileUpload[0]);
				}

				// Check if not uploading too many files
				if (parseInt($fileUpload.get(0).files.length) > parseInt(wpas.fileUploadMax, 10)) {
					alert(wpas.fileUploadMaxError);
					clearFileInput($fileUpload[0]);
				}
			});
		}

	});

}(jQuery));