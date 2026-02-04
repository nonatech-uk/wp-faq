<?php
/**
 * Parish FAQ Meilisearch integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parish_FAQ_Meilisearch {

    private $index_name = 'parish_search';

    /**
     * Get Meilisearch connection settings
     * Reuses parish-search plugin settings if available
     */
    private function get_connection() {
        // Try parish-search settings first (shared config)
        $api_url = get_option('parish_search_api_url', '');
        $admin_key = get_option('parish_search_admin_key', '');

        // Fall back to plugin-specific settings if needed
        if (empty($api_url)) {
            $api_url = get_option('parish_faq_api_url', '');
        }
        if (empty($admin_key)) {
            $admin_key = get_option('parish_faq_admin_key', '');
        }

        return array(
            'url' => rtrim($api_url, '/'),
            'key' => $admin_key,
        );
    }

    /**
     * Index a single FAQ
     */
    public function index_faq($post_id, $post = null) {
        if (!$post) {
            $post = get_post($post_id);
        }

        // Only index published FAQs
        if ($post->post_status !== 'publish' || $post->post_type !== 'parish_faq') {
            return;
        }

        $connection = $this->get_connection();
        if (empty($connection['url']) || empty($connection['key'])) {
            return;
        }

        $categories = wp_get_post_terms($post_id, 'faq_category', array('fields' => 'names'));
        $priority = get_post_meta($post_id, '_parish_faq_priority', true);

        $document = array(
            'id'            => 'faq_' . $post_id,
            'type'          => 'faq',
            'title'         => $post->post_title,
            'content'       => wp_strip_all_tags($post->post_content),
            'categories'    => is_array($categories) ? $categories : array(),
            'priority'      => intval($priority) ?: 10,
            'date_modified' => $post->post_modified,
        );

        $this->send_to_meilisearch($connection, $document);
    }

    /**
     * Delete FAQ from index
     */
    public function delete_faq($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'parish_faq') {
            return;
        }

        $connection = $this->get_connection();
        if (empty($connection['url']) || empty($connection['key'])) {
            return;
        }

        $this->delete_from_meilisearch($connection, 'faq_' . $post_id);
    }

    /**
     * Sync all FAQs to Meilisearch
     */
    public function sync_all() {
        $faqs = get_posts(array(
            'post_type'      => 'parish_faq',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ));

        foreach ($faqs as $faq) {
            $this->index_faq($faq->ID, $faq);
        }

        return count($faqs);
    }

    /**
     * Send document to Meilisearch
     */
    private function send_to_meilisearch($connection, $document) {
        $url = $connection['url'] . '/indexes/' . $this->index_name . '/documents';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $connection['key'],
            ),
            'body'    => json_encode(array($document)),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            error_log('Parish FAQ: Failed to index FAQ - ' . $response->get_error_message());
        }
    }

    /**
     * Delete document from Meilisearch
     */
    private function delete_from_meilisearch($connection, $document_id) {
        $url = $connection['url'] . '/indexes/' . $this->index_name . '/documents/' . $document_id;

        $response = wp_remote_request($url, array(
            'method'  => 'DELETE',
            'headers' => array(
                'Authorization' => 'Bearer ' . $connection['key'],
            ),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            error_log('Parish FAQ: Failed to delete FAQ - ' . $response->get_error_message());
        }
    }
}
