jQuery(document).ready(function ($) {

    /**
     * jQuery DatePicker
     *
     */
    if (jQuery().datepicker && $('input.wpas-date').length) {
        // Check first element compatibility for HTML5 <input type="date" />
        if ($('input.wpas-date:first').prop('type') != 'date') {
            // Not supported. Fallback to jQuery DatePicker
            $('input.wpas-date').datepicker();
        }
    }

});