<?php
class BSFR_Shortcode_Repair {
    private $backup_manager;
    
    public function __construct() {
        if (class_exists('BSFR_Backup_Manager')) {
            $this->backup_manager = new BSFR_Backup_Manager();
        }
    }
    
    public function remove_shortcode($shortcode, $create_backup = true) {
        $scanner = new BSFR_Shortcode_Scanner();
        $orphans = $scanner->find_orphaned_shortcodes();
        
        if (!isset($orphans[$shortcode])) {
            return 0;
        }

        $count = 0;
        foreach ($orphans[$shortcode]['posts'] as $post_data) {
            $post = get_post($post_data['id']);
            if (!$post) continue;

            // Verify the shortcode still exists in the content
            if (!has_shortcode($post->post_content, $shortcode)) {
                continue;
            }

            if ($create_backup && $this->backup_manager) {
                $this->backup_manager->create_backup($post);
            }

            // Remove using regex to handle all cases
            $pattern = get_shortcode_regex(array($shortcode));
            $updated_content = preg_replace("/$pattern/", '', $post->post_content);

            if ($updated_content !== $post->post_content) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $updated_content
                ));
                $count++;
            }
        }
        return $count;
    }
    
    public function replace_shortcode($old_shortcode, $new_shortcode, $create_backup = true) {
        $scanner = new BSFR_Shortcode_Scanner();
        $orphans = $scanner->find_orphaned_shortcodes();
        
        if (!isset($orphans[$old_shortcode])) {
            return 0;
        }

        $count = 0;
        foreach ($orphans[$old_shortcode]['posts'] as $post_data) {
            $post = get_post($post_data['id']);
            if (!$post) continue;

            // Verify the old shortcode still exists
            if (!has_shortcode($post->post_content, $old_shortcode)) {
                continue;
            }

            if ($create_backup && $this->backup_manager) {
                $this->backup_manager->create_backup($post);
            }

            // Replace while preserving attributes and content
            $pattern = get_shortcode_regex(array($old_shortcode));
            $updated_content = preg_replace_callback(
                "/$pattern/", 
                function($matches) use ($new_shortcode) {
                    return str_replace($matches[2], $new_shortcode, $matches[0]);
                }, 
                $post->post_content
            );

            if ($updated_content !== $post->post_content) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $updated_content
                ));
                $count++;
            }
        }
        return $count;
    }
    
    public function preview_shortcode_replacement($post_id, $old_shortcode, $new_shortcode = '') {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found');
        }

        // First verify the shortcode exists in the content
        $pattern = get_shortcode_regex(array($old_shortcode));
        if (!preg_match("/$pattern/", $post->post_content)) {
            return new WP_Error('shortcode_not_found', 'Shortcode not found in post');
        }

        if (empty($new_shortcode)) {
            // Removal preview
            $updated_content = preg_replace("/$pattern/", '', $post->post_content);
        } else {
            // Replacement preview
            $updated_content = preg_replace_callback(
                "/$pattern/",
                function($matches) use ($new_shortcode) {
                    return str_replace($matches[2], $new_shortcode, $matches[0]);
                },
                $post->post_content
            );
        }

        return array(
            'original' => $post->post_content,
            'modified' => $updated_content,
            'action' => empty($new_shortcode) ? 'remove' : 'replace'
        );
    }
}