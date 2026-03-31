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

function getIconPickerTrigger(inputId) {
    return $('[role="iconpicker"][data-input="' + inputId + '"]');
}

function hideIconPicker($trigger) {
    if (!$trigger || !$trigger.length) {
        return;
    }

    var iconpickerInstance = $trigger.data('iconpicker');

    if (iconpickerInstance && typeof iconpickerInstance.hide === 'function') {
        iconpickerInstance.hide();
        return;
    }

    $trigger.closest('.iconpicker-container').find('.iconpicker-popover').removeClass('in').hide();
}

function hideOtherIconPickers(activeInputId) {
    $('[role="iconpicker"]').each(function() {
        var $trigger = $(this);

        if (activeInputId && $trigger.data('input') === activeInputId) {
            return;
        }

        hideIconPicker($trigger);
    });
}

function showIconPicker($trigger) {
    if (!$trigger || !$trigger.length) {
        return;
    }

    var iconpickerInstance = $trigger.data('iconpicker');

    if (iconpickerInstance && typeof iconpickerInstance.show === 'function') {
        iconpickerInstance.show();
        return;
    }

    $trigger.iconpicker('show');
}

// Initialize icon picker for a specific input field
function initializeIconPicker(inputId, previewId) {
    var $trigger = getIconPickerTrigger(inputId);

    if (!$trigger.length) {
        return;
    }

    // Check if already initialized to prevent duplicate initialization
    if (!$trigger.data('iconpicker-initialized')) {
        $trigger.iconpicker({
            iconset: 'fontawesome5',
            input: '#' + inputId,
            animation: false,
            hideOnSelect: true,
        }).data('iconpicker-initialized', true);
    }

    // Update icon preview function
    function updateIconPreview(iconClass) {
        var preview = document.getElementById(previewId);
        if (preview) {
            preview.innerHTML = iconClass ? '<i class="' + iconClass + '"></i>' : '';
        }
    }

    // On icon picker select -> set value, update preview, close popover immediately
    $trigger.off('iconpickerSelected.iconpickerSet').on('iconpickerSelected.iconpickerSet', function(e) {
        var inputElement = $('#' + inputId)[0];

        $('#' + inputId).val(e.iconpickerValue);

        if (inputElement) {
            inputElement.dispatchEvent(new Event('input', { bubbles: true }));
            inputElement.dispatchEvent(new Event('change', { bubbles: true }));
        }

        updateIconPreview(e.iconpickerValue);
    });

    // On manual input change
    $('#' + inputId).off('input.iconpickerPreview').on('input.iconpickerPreview', function() {
        updateIconPreview(this.value);
    });

    // Initialize preview if value exists
    updateIconPreview($('#' + inputId).val());

    // Simple click handler: open immediately and keep control predictable
    $trigger.off('.iconpickerOpen')
        .on('mousedown.iconpickerOpen mouseup.iconpickerOpen click.iconpickerOpen', function(e) {
            e.stopPropagation();
        })
        .on('click.iconpickerOpen', function(e) {
            e.preventDefault();
            hideOtherIconPickers(inputId);
            showIconPicker($(this));
        });

    // Ensure only one popover is visible at a time when this picker's popover is shown
    $trigger.off('iconpickerShow.iconpickerOne iconpickerShown.iconpickerOne')
        .on('iconpickerShow.iconpickerOne', function() {
            hideOtherIconPickers(inputId);
        })
        .on('iconpickerShown.iconpickerOne', function() {
            var iconpickerInstance = $(this).data('iconpicker');

            if (iconpickerInstance && iconpickerInstance.popover) {
                iconpickerInstance.popover
                    .off('.iconpickerStop')
                    .on('mousedown.iconpickerStop mouseup.iconpickerStop click.iconpickerStop', function(ev) {
                        ev.stopPropagation();
                    });
            }
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
                hideOtherIconPickers();
            }
        });

        // Global escape key handler for all icon pickers
        $(document).off('keydown.iconpickerGlobal').on('keydown.iconpickerGlobal', function(e) {
            if (e.keyCode === 27) { // Escape key
                hideOtherIconPickers();
            }
        });
        
        window.iconPickerGlobalHandlersBound = true;
    }
});

