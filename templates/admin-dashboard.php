<div class="wrap">
    <h1><?php esc_html_e('Broken Shortcode Finder', 'broken-shortcode-finder-cp'); ?></h1>
    
    <div class="bsfr-container">
        <div class="bsfr-scan-section">
            <h2><?php esc_html_e('Scan for Orphaned Shortcodes', 'broken-shortcode-finder-cp'); ?></h2>
            <button id="bsfr-scan-button" class="button button-primary">
                <?php esc_html_e('Run Scan', 'broken-shortcode-finder-cp'); ?>
            </button>
            <div id="bsfr-scan-results" class="bsfr-results"></div>
        </div>
        
        
    </div>
</div>

<script id="bsfr-results-template" type="text/x-underscore-template">
    <h3><?php esc_html_e('Scan Results', 'broken-shortcode-finder-cp'); ?></h3>
    <% if (Object.keys(data.stats).length === 0) { %>
        <p><?php esc_html_e('No orphaned shortcodes found!', 'broken-shortcode-finder-cp'); ?></p>
    <% } else { %>
        <div class="bsfr-scan-summary">
            <p><?php esc_html_e('Found', 'broken-shortcode-finder-cp');  ?>
            <%= Object.keys(data.stats).length %> <?php esc_html_e('orphaned shortcodes', 'broken-shortcode-finder-cp'); ?></p>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shortcode', 'broken-shortcode-finder-cp'); ?></th>
                    <th><?php esc_html_e('Usage Count', 'broken-shortcode-finder-cp'); ?></th>
                    <th><?php esc_html_e('Used In', 'broken-shortcode-finder-cp'); ?></th>
                    
                </tr>
            </thead>
            <tbody>
                <% _.each(data.stats, function(count, shortcode) { %>
                    <tr>
                        <td><code>[<%= shortcode %>]</code></td>
                        <td><%= count %></td>
                        <td>
                            <ul class="bsfr-post-list">
                                <% _.each(data.post_details[shortcode], function(post) { %>
                                    <li>
                                        <a href="<%= post.url %>" target="_blank"><%= post.title %></a>
                                        <% if (post.status !== 'publish') { %>
                                            <span class="bsfr-post-status">(<%= post.status %>)</span>
                                        <% } %>
                                        <a href="<%= post.edit_url %>" class="bsfr-edit-link" title="<?php esc_html_e('Edit Post', 'broken-shortcode-finder-cp'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                    </li>
                                <% }); %>
                            </ul>
                        </td>
                       
                    </tr>
                <% }); %>
            </tbody>
        </table>
    <% } %>
</script>

<script id="bsfr-replace-template" type="text/template">
    <div class="bsfr-repair-options-container">
        <h3><?php esc_html_e('Replace Shortcode', 'broken-shortcode-finder-cp'); ?>: [<%= shortcode %>]</h3>
        
        <div class="bsfr-option-section">
            <label for="bsfr-replacement">
                <?php esc_html_e('New Shortcode:', 'broken-shortcode-finder-cp'); ?>
            </label>
            <input type="text" id="bsfr-replacement" name="replacement" value="" placeholder="new_shortcode">
        </div>
        
        <div class="bsfr-option-section">
            <label>
                <input type="checkbox" id="bsfr-create-backup" checked>
                <?php esc_html_e('Create backup before making changes', 'broken-shortcode-finder-cp'); ?>
            </label>
        </div>
        
        <div class="bsfr-option-section">
            <label>
                <input type="checkbox" id="bsfr-dry-run">
                <?php esc_html_e('Dry run (show what would be changed without saving)', 'broken-shortcode-finder-cp'); ?>
            </label>
        </div>
        
        <div class="bsfr-action-buttons">
            <button class="button button-primary bsfr-confirm-replace" 
                    data-shortcode="<%= shortcode %>">
                <?php esc_html_e('Confirm Replacement', 'broken-shortcode-finder-cp'); ?>
            </button>
            <button class="button bsfr-cancel-replace">
                <?php esc_html_e('Cancel', 'broken-shortcode-finder-cp'); ?>
            </button>
        </div>
    </div>
</script>

<script id="bsfr-preview-template" type="text/template">
    <div class="bsfr-preview-container">
        <h3><?php esc_html_e('Preview Changes for:', 'broken-shortcode-finder-cp'); ?> [<%= shortcode %>]</h3>
        
        <div class="bsfr-preview-actions">
            <select id="bsfr-preview-post-select">
                <% _.each(posts, function(post) { %>
                    <option value="<%= post.id %>"><%= post.title %></option>
                <% }); %>
            </select>
            
            <div class="bsfr-preview-options">
                <label for="bsfr-preview-replacement">
                    <?php esc_html_e('Replacement Shortcode:', 'broken-shortcode-finder-cp'); ?>
                </label>
                <input type="text" id="bsfr-preview-replacement" placeholder="new_shortcode">
                <button class="button bsfr-update-preview">
                    <?php esc_html_e('Update Preview', 'broken-shortcode-finder-cp'); ?>
                </button>
            </div>
        </div>
        
        <div class="bsfr-diff-container">
            <div class="bsfr-diff-original">
                <h4><?php esc_html_e('Original Content', 'broken-shortcode-finder-cp'); ?></h4>
                <div class="bsfr-content-original"></div>
            </div>
            <div class="bsfr-diff-modified">
                <h4><?php esc_html_e('Modified Content', 'broken-shortcode-finder-cp'); ?></h4>
                <div class="bsfr-content-modified"></div>
            </div>
        </div>
        
        <div class="bsfr-preview-buttons">
            <button class="button button-primary bsfr-apply-from-preview">
                <?php esc_html_e('Apply These Changes', 'broken-shortcode-finder-cp'); ?>
            </button>
            <button class="button bsfr-close-preview">
                <?php esc_html_e('Close Preview', 'broken-shortcode-finder-cp'); ?>
            </button>
        </div>
    </div>
</script>