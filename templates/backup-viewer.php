<div class="wrap">
    <h1><?php esc_html_e('Content Backups', 'broken-shortcode-finder-cp'); ?></h1>
    
    <a href="<?php echo esc_url(admin_url('tools.php?page=broken-shortcode-finder-cp')); ?>" class="button">
        <?php esc_html_e('Back to Scanner', 'broken-shortcode-finder-cp'); ?>
    </a>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', 'broken-shortcode-finder-cp'); ?></th>
                <th><?php esc_html_e('Last Modified', 'broken-shortcode-finder-cp'); ?></th>
                <th><?php esc_html_e('Actions', 'broken-shortcode-finder-cp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $index => $backup) : ?>
                <tr>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($backup['date']))); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($backup['modified']))); ?></td>
                    <td>
                        <button class="button bsfr-view-backup" data-index="<?php echo esc_attr($index); ?>">
                            <?php esc_html_e('View', 'broken-shortcode-finder-cp'); ?>
                        </button>
                        <button class="button button-primary bsfr-restore-backup" data-index="<?php echo esc_attr($index); ?>">
                            <?php esc_html_e('Restore', 'broken-shortcode-finder-cp'); ?>
                        </button>
                    </td>
                </tr>
                <tr class="bsfr-backup-content" id="bsfr-backup-<?php echo esc_attr($index); ?>" style="display:none;">
                    <td colspan="3">
                        <div class="bsfr-diff-container">
                            <div class="bsfr-diff-original">
                                <h3><?php esc_html_e('Original Content', 'broken-shortcode-finder-cp'); ?></h3>
                                <pre><?php echo esc_html($backup['content']); ?></pre>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('.bsfr-view-backup').on('click', function() {
        var index = $(this).data('index');
        $('#bsfr-backup-' + index).toggle();
    });

    $('.bsfr-restore-backup').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to restore this backup?', 'broken-shortcode-finder-cp')); ?>')) {
            return;
        }

        var button = $(this);
        var index = button.data('index');

        $.post(ajaxurl, {
            action: 'bsfr_restore_backup',
            post_id: <?php echo esc_js((int) $post_id); ?>,
            index: index,
            nonce: bsfr_vars.nonce
        }, function(response) {
            if (response.success) {
                alert('<?php echo esc_js(__('Backup restored successfully!', 'broken-shortcode-finder-cp')); ?>');
                window.location.href = '<?php echo esc_url(get_edit_post_link($post_id, '')); ?>';
            } else {
                alert('<?php echo esc_js(__('Restore failed. Please try again.', 'broken-shortcode-finder-cp')); ?>');
            }
        });
    });
});
</script>
