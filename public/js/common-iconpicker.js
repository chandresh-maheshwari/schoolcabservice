/**
 * Common Icon Picker Functionality
 * This file provides reusable icon picker functionality for forms
 * 
 * Usage:
 * 1. Include this script in your Blade template: <script src="{{ asset('js/common-iconpicker.js') }}"></script>
 * 2. Add data attributes to your icon picker button:
 *    - data-input="your_input_id" (ID of the input field)
 *    - data-preview="your_preview_id" (ID of the preview span)
 * 3. The icon picker will automatically initialize on page load
 * 
 * Example HTML:
 * <div class="input-group">
 *     <span class="input-group-text" id="icon-preview">Preview</span>
 *     <input type="text" id="my_icon" name="my_icon" class="form-control">
 *     <button type="button" role="iconpicker" data-iconset="fontawesome5" data-input="my_icon" data-preview="icon-preview">
 *         <i class="fas fa-icons"></i>
 *     </button>
 * </div>
 */

// Initialize icon picker for a specific input field
function initializeIconPicker(inputId, previewId) {
    // Check if already initialized to prevent duplicate initialization
    if ($('[role="iconpicker"][data-input="' + inputId + '"]').data('iconpicker-initialized')) {
        return;
    }
    
    // Initialize icon picker
    $('[role="iconpicker"][data-input="' + inputId + '"]').iconpicker({
        iconset: 'fontawesome5',
        input: '#' + inputId,
    }).data('iconpicker-initialized', true);

    // Update icon preview function
    function updateIconPreview(iconClass) {
        var preview = document.getElementById(previewId);
        if (preview) {
            preview.innerHTML = iconClass ? '<i class="' + iconClass + '"></i>' : '';
        }
    }

    // On icon picker select -> set value, update preview, close popover immediately
    $('[role="iconpicker"][data-input="' + inputId + '"]').off('iconpickerSelected.iconpickerSet').on('iconpickerSelected.iconpickerSet', function(e) {
        $('#' + inputId).val(e.iconpickerValue);
        $('#' + inputId)[0].dispatchEvent(new Event('input'));
        updateIconPreview(e.iconpickerValue);
        // Close the popover after selection (no delay)
        $('.iconpicker-popover').hide();
    });

    // On manual input change
    $('#' + inputId).on('input', function() {
        updateIconPreview(this.value);
    });

    // Initialize preview if value exists
    updateIconPreview($('#' + inputId).val());

    // Simple click handler: open immediately and keep control predictable
    var $trigger = $('[role="iconpicker"][data-input="' + inputId + '"]');
    $trigger.off('click.iconpickerOpen').on('click.iconpickerOpen', function(e) {
        // Prevent plugin's internal click toggle to avoid double toggle
        e.preventDefault();
        e.stopPropagation();
        // Close others and explicitly show this popover right away
        $('.iconpicker-popover:visible').hide();
        try { $(this).iconpicker('show'); } catch (err) { /* fallback ignored */ }
    });

    // Ensure only one popover is visible at a time when this picker's popover is shown
    $('[role="iconpicker"][data-input="' + inputId + '"]').off('iconpickerShown.iconpickerOne').on('iconpickerShown.iconpickerOne', function() {
        // Hide any previously opened popovers, keep the most recently shown
        $('.iconpicker-popover:visible').not(':last').hide();
        // Prevent clicks within the popover from bubbling to the document
        $('.iconpicker-popover').off('mousedown.iconpickerStop click.iconpickerStop').on('mousedown.iconpickerStop click.iconpickerStop', function(ev) {
            ev.stopPropagation();
        });
    });
}

// Initialize multiple icon pickers at once
function initializeMultipleIconPickers(iconPickerConfigs) {
    iconPickerConfigs.forEach(function(config) {
        initializeIconPicker(config.inputId, config.previewId);
    });
}

// Clear icon picker validation errors
function clearIconPickerErrors(inputId) {
    $('#' + inputId).next('.error-message').remove();
}

// Validate icon picker field
function validateIconPicker(inputId, fieldName) {
    var value = $('#' + inputId).val();
    var isValid = value && value.trim() !== '';
    
    if (!isValid) {
        $('#' + inputId).after('<span class="error-message" style="color: red;">' + fieldName + ' is required.</span>');
    }
    
    return isValid;
}

// Auto-initialize all icon pickers on page load
$(document).ready(function() {
    // Find all icon picker buttons and initialize them
    $('[role="iconpicker"]').each(function() {
        var $button = $(this);
        var inputId = $button.data('input');
        var previewId = $button.data('preview');
        
        if (inputId && previewId) {
            initializeIconPicker(inputId, previewId);
        }
    });

    // Global click outside handler for all icon pickers (only bind once)
    if (!window.iconPickerGlobalHandlersBound) {
        $(document).off('click.iconpickerGlobal').on('click.iconpickerGlobal', function(e) {
            if (!$(e.target).closest('[role="iconpicker"]').length && 
                !$(e.target).closest('.iconpicker-popover').length) {
                $('.iconpicker-popover').hide();
            }
        });

        // Global escape key handler for all icon pickers
        $(document).off('keydown.iconpickerGlobal').on('keydown.iconpickerGlobal', function(e) {
            if (e.keyCode === 27) { // Escape key
                $('.iconpicker-popover').hide();
            }
        });
        
        window.iconPickerGlobalHandlersBound = true;
    }
});

