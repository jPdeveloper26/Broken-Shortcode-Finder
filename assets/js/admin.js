jQuery(document).ready(function($) {
    var bsfr_scan_data = {};
    
    // Handle scan button click
    $('#bsfr-scan-button').on('click', function() {
        $(this).prop('disabled', true).text(bsfr_vars.scanning_text);
        
        $.post(bsfr_vars.ajax_url, {
            action: 'bsfr_scan',
            nonce: bsfr_vars.nonce
        }, function(response) {
            $('#bsfr-scan-button').prop('disabled', false).text(bsfr_vars.scan_text);
            
            if (response.success) {
                bsfr_scan_data = response.data;
                
                var template = _.template($('#bsfr-results-template').html());
                $('#bsfr-scan-results').html(template({
                    data: response.data
                }));
                
                if (Object.keys(response.data.stats).length > 0) {
                    $('.bsfr-repair-section').show();
                } else {
                    $('.bsfr-repair-section').hide();
                    $('#bsfr-scan-results').append('<div class="notice notice-success"><p>No orphaned shortcodes found!</p></div>');
                }
            } else {
                $('#bsfr-scan-results').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).fail(function() {
            $('#bsfr-scan-button').prop('disabled', false).text(bsfr_vars.scan_text);
            $('#bsfr-scan-results').html('<div class="notice notice-error"><p>Scan failed. Please try again.</p></div>');
        });
    });

    // Handle Remove All button
    $(document).on('click', '.bsfr-remove', function(e) {
        e.preventDefault();
        var button = $(this);
        var shortcode = button.data('shortcode');
        
        if (!confirm(bsfr_vars.confirm_remove)) {
            return;
        }
        
        button.prop('disabled', true).text('Removing...');
        
        $.post(bsfr_vars.ajax_url, {
            action: 'bsfr_repair',
            action_type: 'remove',
            shortcode: shortcode,
            nonce: bsfr_vars.nonce
        }, function(response) {
            if (response.success) {
                if (response.data.count > 0) {
                    alert(bsfr_vars.removed_text.replace('%d', response.data.count));
                } else {
                    alert('No instances were removed. The shortcode may not exist in content or may be registered now.');
                }
                $('#bsfr-scan-button').click();
            } else {
                alert(response.responseJSON.data || bsfr_vars.error_text);
            }
            button.prop('disabled', false).text('Remove All');
        }).fail(function(response) {
            alert(response.responseJSON && response.responseJSON.data ? 
                response.responseJSON.data : 
                bsfr_vars.error_text);
            button.prop('disabled', false).text('Remove All');
        });
    });

    // Handle Replace button
    $(document).on('click', '.bsfr-replace', function(e) {
        e.preventDefault();
        var shortcode = $(this).data('shortcode');
        var template = _.template($('#bsfr-replace-template').html());
        $('#bsfr-repair-options').html(template({
            shortcode: shortcode
        }));
    });

    // Handle Confirm Replace button
    $(document).on('click', '.bsfr-confirm-replace', function(e) {
        e.preventDefault();
        var button = $(this);
        var shortcode = button.data('shortcode');
        var replacement = $('#bsfr-replacement').val().trim();
        var create_backup = $('#bsfr-create-backup').is(':checked');
        
        if (!replacement) {
            alert(bsfr_vars.enter_replacement);
            return;
        }
        
        button.prop('disabled', true).text('Replacing...');
        
        $.post(bsfr_vars.ajax_url, {
            action: 'bsfr_repair',
            action_type: 'replace',
            shortcode: shortcode,
            replacement: replacement,
            create_backup: create_backup,
            nonce: bsfr_vars.nonce
        }, function(response) {
            if (response.success) {
                if (response.data.count > 0) {
                    alert(bsfr_vars.replaced_text.replace('%d', response.data.count));
                } else {
                    alert('No instances were replaced. The shortcode may not exist in content or may be registered now.');
                }
                $('#bsfr-scan-button').click();
                $('#bsfr-repair-options').empty();
            } else {
                alert(response.responseJSON.data || bsfr_vars.error_text);
            }
            button.prop('disabled', false).text('Confirm Replacement');
        }).fail(function(response) {
            alert(response.responseJSON && response.responseJSON.data ? 
                response.responseJSON.data : 
                bsfr_vars.error_text);
            button.prop('disabled', false).text('Confirm Replacement');
        });
    });

    // Handle Preview button
    $(document).on('click', '.bsfr-preview', function(e) {
        e.preventDefault();
        var shortcode = $(this).data('shortcode');
        
        if (!bsfr_scan_data.post_details || !bsfr_scan_data.post_details[shortcode]) {
            alert('No post data available for preview. Please run a scan first.');
            return;
        }
        
        var template = _.template($('#bsfr-preview-template').html());
        $('#bsfr-repair-options').html(template({
            shortcode: shortcode,
            posts: bsfr_scan_data.post_details[shortcode]
        }));
        
        // Load initial preview for first post
        update_preview_content();
    });

    // Update preview content when post selection changes
    $(document).on('change', '#bsfr-preview-post-select', update_preview_content);
    
    // Update preview content when replacement changes
    $(document).on('click', '.bsfr-update-preview', function(e) {
        e.preventDefault();
        update_preview_content();
    });

    // Function to update preview content
    function update_preview_content() {
        var post_id = $('#bsfr-preview-post-select').val();
        var shortcode = $('#bsfr-repair-options').find('.bsfr-preview-container').data('shortcode');
        var replacement = $('#bsfr-preview-replacement').val().trim();
        
        var button = $('.bsfr-update-preview');
        button.prop('disabled', true).text('Loading...');
        
        $.post(bsfr_vars.ajax_url, {
            action: 'bsfr_preview',
            post_id: post_id,
            shortcode: shortcode,
            replacement: replacement,
            nonce: bsfr_vars.nonce
        }, function(response) {
            if (response.success) {
                $('.bsfr-content-original').text(response.data.original);
                $('.bsfr-content-modified').text(response.data.modified);
            } else {
                alert('Preview failed: ' + (response.data || 'Shortcode not found in selected post'));
            }
            button.prop('disabled', false).text('Update Preview');
        }).fail(function() {
            alert('Preview failed. Please try again.');
            button.prop('disabled', false).text('Update Preview');
        });
    }

    // Handle Apply from Preview button
    $(document).on('click', '.bsfr-apply-from-preview', function(e) {
        e.preventDefault();
        var button = $(this);
        var shortcode = button.closest('.bsfr-preview-container').data('shortcode');
        var replacement = $('#bsfr-preview-replacement').val().trim();
        
        if (!confirm(bsfr_vars.confirm_apply)) {
            return;
        }
        
        if (!replacement) {
            alert(bsfr_vars.enter_replacement);
            return;
        }
        
        button.prop('disabled', true).text('Applying...');
        
        $.post(bsfr_vars.ajax_url, {
            action: 'bsfr_repair',
            action_type: 'replace',
            shortcode: shortcode,
            replacement: replacement,
            nonce: bsfr_vars.nonce
        }, function(response) {
            if (response.success) {
                alert(bsfr_vars.replaced_text.replace('%d', response.data.count));
                $('#bsfr-scan-button').click();
                $('#bsfr-repair-options').empty();
            } else {
                alert(response.responseJSON.data || bsfr_vars.error_text);
            }
            button.prop('disabled', false).text('Apply These Changes');
        }).fail(function(response) {
            alert(response.responseJSON && response.responseJSON.data ? 
                response.responseJSON.data : 
                bsfr_vars.error_text);
            button.prop('disabled', false).text('Apply These Changes');
        });
    });

    // Close preview/cancel buttons
    $(document).on('click', '.bsfr-close-preview, .bsfr-cancel-replace', function(e) {
        e.preventDefault();
        $('#bsfr-repair-options').empty();
    });
});