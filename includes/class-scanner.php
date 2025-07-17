<?php
if (!defined('ABSPATH')) exit;

class BSFR_Shortcode_Scanner {
    private $orphaned_shortcodes = array();
    private $content_types = array('post', 'page');
    
    public function __construct() {
        $this->content_types = apply_filters('bsfr_content_types', $this->content_types);
    }
    
/**
 * Find all shortcode tags in the content, including nested ones.
 *
 * @param string $content The post or text content to scan.
 * @return array List of unique shortcode tags found in the content.
 */
 
/**
 * Detects all shortcodes in content, including broken ones in Gutenberg blocks.
 *
 * @param string $content The post content.
 * @return array List of unique shortcode tags found.
 */
public function find_all_shortcodes_in_content($content) {
    $found = [];

    // 1. Clean content: remove <style>, <script>, <noscript>
    $clean_content = preg_replace('/<\s*(style|script|noscript)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $content);

    // 2. Use core shortcode detection
    preg_match_all('/' . get_shortcode_regex() . '/', $clean_content, $matches, PREG_SET_ORDER);
    foreach ($matches as $shortcode) {
        $tag = $shortcode[2];
        $found[$tag] = true;

        if (!empty($shortcode[5])) {
            $nested_tags = $this->find_all_shortcodes_in_content($shortcode[5]);
            foreach ($nested_tags as $nested_tag) {
                $found[$nested_tag] = true;
            }
        }
    }

    // 3. Gutenberg block types to scan
    $block_types = [
        'core/code',
        'core/preformatted',
        'core/html',
        'core/shortcode'
    ];

   foreach ($block_types as $block_type) {
    $pattern = '/<!--\s+wp:' . preg_quote($block_type, '/') . '[^>]*-->(.*?)<!--\s+\/wp:' . preg_quote($block_type, '/') . '\s+-->/is';
    if (preg_match_all($pattern, $content, $block_matches)) {
        foreach ($block_matches[1] as $block_content) {
            $block_content = html_entity_decode($block_content);

            // Special handling for <code> blocks (core/code)
            if (strpos($block_type, 'code') !== false) {
                if (preg_match_all('/<code[^>]*>(.*?)<\/code>/is', $block_content, $code_matches)) {
                    foreach ($code_matches[1] as $code_text) {
                        if (preg_match_all('/\[(\w[\w-]*)\b[^\]]*(?:\]|\s|$)/', $code_text, $shortcode_matches)) {
                            foreach ($shortcode_matches[1] as $tag) {
                                $found[$tag] = true;
                            }
                        }
                    }
                }
            } else {
                if (preg_match_all('/\[(\w[\w-]*)\b[^\]]*(?:\]|\s|$)/', $block_content, $shortcode_matches)) {
                    foreach ($shortcode_matches[1] as $tag) {
                        $found[$tag] = true;
                    }
                }
            }
        }
    }
}

// Scan raw <pre><code> blocks (e.g., wp-block-code)
if (preg_match_all('/<pre[^>]*class="[^"]*wp-block-code[^"]*"[^>]*>\s*<code[^>]*>(.*?)<\/code>\s*<\/pre>/is', $content, $code_blocks)) {
    foreach ($code_blocks[1] as $code_content) {
        $code_content = html_entity_decode($code_content);
        if (preg_match_all('/\[(\w[\w-]*)\b[^\]]*(?:\]|\s|$)/', $code_content, $shortcode_matches)) {
            foreach ($shortcode_matches[1] as $tag) {
                $found[$tag] = true;
            }
        }
    }
}



    // 4. Fallback global pattern for unmatched shortcodes
    if (preg_match_all('/\[(\w[\w-]*)(?![\w-]*\s*=)[^\]]*(?:\]|\s|$)/', $clean_content, $simple_matches)) {
        foreach ($simple_matches[1] as $tag) {
            if (!isset($found[$tag])) {
                $found[$tag] = true;
            }
        }
    }

    return array_keys($found);
}
    
    /**
     * Find all orphaned shortcodes in all content
     * @param bool $force_rescan Whether to ignore cached results
     * @return array Orphaned shortcodes data
     */
    public function find_orphaned_shortcodes($force_rescan = false) {
        if (!empty($this->orphaned_shortcodes) && !$force_rescan) {
            return $this->orphaned_shortcodes;
        }

        global $shortcode_tags;
        $registered_shortcodes = array_keys($shortcode_tags);

        $posts = get_posts(array(
            'post_type' => $this->content_types,
            'post_status' => 'any',
            'numberposts' => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ));

        $orphans = array();
        
        foreach ($posts as $post) {
            $content_shortcodes = $this->find_all_shortcodes_in_content($post->post_content);
            
            foreach ($content_shortcodes as $shortcode) {
                // Skip registered shortcodes
                if (in_array($shortcode, $registered_shortcodes)) {
                    continue;
                }
                
                if (!isset($orphans[$shortcode])) {
                    $orphans[$shortcode] = array(
                        'count' => 0,
                        'posts' => array()
                    );
                }
                
                $orphans[$shortcode]['posts'][] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'edit_url' => get_edit_post_link($post->ID, ''),
                    'status' => $post->post_status,
                    'content_sample' => substr($post->post_content, 0, 200)
                );
                $orphans[$shortcode]['count']++;
            }
        }

        $this->orphaned_shortcodes = $orphans;
        return $orphans;
    }
    
    /**
     * Get statistics about orphaned shortcodes
     * @return array Shortcode usage counts
     */
    public function get_shortcode_stats() {
        $stats = array();
        
        foreach ($this->orphaned_shortcodes as $shortcode => $data) {
            $stats[$shortcode] = $data['count'];
        }
        
        return $stats;
    }
    
    /**
     * Check if a shortcode is orphaned
     * @param string $shortcode The shortcode to check
     * @return bool Whether the shortcode is orphaned
     */
    public function is_shortcode_orphaned($shortcode) {
        $this->find_orphaned_shortcodes();
        return isset($this->orphaned_shortcodes[$shortcode]);
    }
    
    /**
     * Add custom post type to scan
     * @param string $post_type Post type to add
     */
    public function add_content_type($post_type) {
        if (!in_array($post_type, $this->content_types)) {
            $this->content_types[] = $post_type;
        }
    }
    
    /**
     * Debug function to test shortcode detection
     */
    public function debug_shortcode_detection($content) {
        return $this->find_all_shortcodes_in_content($content);
    }
}