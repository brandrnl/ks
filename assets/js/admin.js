jQuery(document).ready(function($) {
    // Color picker init
    $('.ksm-color-picker').wpColorPicker();
    
    // Add color button
    $('#ksm-add-color').on('click', function() {
        var template = $('#ksm-color-row-template').html();
        $('#ksm-colors-container').append(template);
        $('.ksm-color-picker').wpColorPicker();
    });
    
    // Remove color
    $(document).on('click', '.ksm-remove-color', function() {
        $(this).closest('.ksm-color-row').remove();
    });
});