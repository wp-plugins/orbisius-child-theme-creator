/* Theme Editor Code */
jQuery(document).ready(function($) {
    orbisius_ctc_theme_editor_setup();
});

/**
 * This is called when on doc ready.
 * It setups the actoins that we want to handle e.g. what happens when
 * somebody selects something from the dropdowns.
 * @returns {undefined}
 */
function orbisius_ctc_theme_editor_setup() {
    var $ = jQuery;

    var current_theme_dir = $('#theme_1').val();

    if (current_theme_dir != '') {
        // prefill dropdown files with the current theme's files.
        app_load('#orbisius_ctc_theme_editor_theme_1_form', 'generate_dropdown', '#theme_1_file', app_handle_theme_change);
    }

    // Change theme selection
    $('#theme_1').on("change", function () {
        app_load('#orbisius_ctc_theme_editor_theme_1_form', 'generate_dropdown', '#theme_1_file', app_handle_theme_change);
    });

    $('#orbisius_ctc_theme_editor_theme_1_form').submit(function () {
        app_load('#orbisius_ctc_theme_editor_theme_1_form', 'save_file', '#theme_1_file_contents');

        return false;
    });

    var current_theme_dir = $('#theme_2').val();

    if (current_theme_dir != '') {
        app_load('#orbisius_ctc_theme_editor_theme_2_form', 'generate_dropdown', '#theme_2_file', app_handle_theme_change);
    }

    // Change theme selection
    $('#theme_2').on("change", function () {
        app_load('#orbisius_ctc_theme_editor_theme_2_form', 'generate_dropdown', '#theme_2_file', app_handle_theme_change);
    });

    $('#orbisius_ctc_theme_editor_theme_2_form').submit(function () {
        app_load('#orbisius_ctc_theme_editor_theme_2_form', 'save_file', '#theme_2_file_contents');

        return false;
    });
}

/**
 * When the theme is selected we need to check if there is a file selected so we can load it.
 * When the file dropdown is changed/selected we'll load the selected file.
 *
 * @returns {undefined}
 */
function app_handle_theme_change(form_id, action, target_container, result) {
    var form_prefix = jQuery(form_id) ? jQuery(form_id).attr('id') : ''; // orbisius_ctc_theme_editor_theme_1_form
    form_prefix = form_prefix.replace(/.+(theme[-_]*\d+).*/, '$1');
    form_prefix = '#' + form_prefix + '_'; // jQuery ID prefix. Res: #theme_2_

    var cur_file = jQuery(form_prefix + '_file').val();

    if (cur_file !== '') {
        app_load(form_id, 'load_file', form_prefix + 'file_contents');
    }

    jQuery(form_prefix + 'file').on("change", function () {
        app_load(form_id, 'load_file', form_prefix + 'file_contents');
    });
}

/**
 * Sends ajax call to WP. Different requests append sub_cmd because WP is used key: 'action'.
 * Depending on the target element a different method for setting the value is used.
 *
 * @param {type} form_id
 * @param {type} action
 * @param {type} target_container
 * @param {type} callback
 * @returns {undefined}
 */
function app_load(form_id, action, target_container, callback) {
    var result = 'Loading...';
    var undo_readonly = 0;

    if (action.indexOf('save') >= 0) { // save action
        if (jQuery(target_container).is("input,textarea")) {
            jQuery(target_container).attr('readonly', 'readonly');
            jQuery(target_container).addClass('saving_action');
        }

        jQuery('.status', jQuery(target_container).parent()).html(result);
    } else {
        if (jQuery(target_container).is("input,textarea")) {
            jQuery(target_container).val(result);
            jQuery(target_container).addClass('saving_action');
        } else if (jQuery(target_container).is("select")) { // for loading. we want to override options of the select
            jQuery(target_container + ' option').text(result);
        } else {
            jQuery(target_container).html(result);
        }
    }

    jQuery.ajax({
        type : "post",
        //dataType : "json",
        url : ajaxurl, // contains all the necessary params
        data : jQuery(form_id).serialize() + '&action=orbisius_ctc_theme_editor_ajax&sub_cmd=' + escape(action),

        success : function (result) {
            if (jQuery(target_container).is("input,textarea")) {
                jQuery(target_container).val(result);
            } else {
                jQuery(target_container).html(result);
            }

            if (typeof callback != 'undefined') {
                callback(form_id, action, target_container, result);
            }
        },

        complete : function (result) { // this is always called
            jQuery(target_container).removeClass('saving_action');

            if (action.indexOf('save') >= 0) { // save action
                if (jQuery(target_container).is("input,textarea")) {
                    jQuery(target_container).removeAttr('readonly');

                    // http://stackoverflow.com/questions/2432749/jquery-delay-not-delaying
                    jQuery('.status', jQuery(target_container).parent()).html('Saved.');

                    setTimeout(function () {
                        jQuery('.status', jQuery(target_container).parent()).empty();
                    }, 2000);
                }
            }
        }
    });
}
