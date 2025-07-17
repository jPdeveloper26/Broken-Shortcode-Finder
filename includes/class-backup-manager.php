<?php
if (!defined('ABSPATH')) exit;

class BSFR_Backup_Manager {
    public function create_backup($post) {
        $backups = get_post_meta($post->ID, '_bsfr_content_backups', true);
        
        if (empty($backups)) {
            $backups = array();
        }
        
        // Keep only the last 5 backups
        if (count($backups) >= 5) {
            array_shift($backups);
        }
        
        $backups[] = array(
            'content' => $post->post_content,
            'date' => current_time('mysql'),
            'modified' => get_post_modified_time('mysql', false, $post)
        );
        
        update_post_meta($post->ID, '_bsfr_content_backups', $backups);
        return true;
    }
    
    public function get_backups($post_id) {
        return get_post_meta($post_id, '_bsfr_content_backups', true);
    }
    
    public function restore_backup($post_id, $backup_index) {
        $backups = $this->get_backups($post_id);
        
        if (isset($backups[$backup_index])) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $backups[$backup_index]['content']
            ));
            return true;
        }
        
        return false;
    }
}