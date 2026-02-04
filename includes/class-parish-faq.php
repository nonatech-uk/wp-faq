<?php
/**
 * Main Parish FAQ class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parish_FAQ {

    private $meilisearch;

    public function __construct() {
        $this->meilisearch = new Parish_FAQ_Meilisearch();
    }

    public function init() {
        // Register CPT and taxonomy
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));

        // Meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_parish_faq', array($this, 'save_meta'), 10, 2);

        // Admin columns
        add_filter('manage_parish_faq_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_parish_faq_posts_custom_column', array($this, 'render_admin_columns'), 10, 2);
        add_filter('manage_edit-parish_faq_sortable_columns', array($this, 'sortable_columns'));

        // Admin query modifications
        add_action('pre_get_posts', array($this, 'admin_order_by_priority'));

        // Shortcodes
        add_shortcode('parish_faq', array($this, 'render_faq_shortcode'));

        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Meilisearch hooks
        add_action('save_post_parish_faq', array($this->meilisearch, 'index_faq'), 20, 2);
        add_action('before_delete_post', array($this->meilisearch, 'delete_faq'));
        add_action('trashed_post', array($this->meilisearch, 'delete_faq'));
    }

    /**
     * Register the FAQ custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => 'FAQs',
            'singular_name'      => 'FAQ',
            'menu_name'          => 'Parish FAQs',
            'add_new'            => 'Add New FAQ',
            'add_new_item'       => 'Add New FAQ',
            'edit_item'          => 'Edit FAQ',
            'new_item'           => 'New FAQ',
            'view_item'          => 'View FAQ',
            'search_items'       => 'Search FAQs',
            'not_found'          => 'No FAQs found',
            'not_found_in_trash' => 'No FAQs found in trash',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 2.2,  // Just below Dashboard, alphabetical
            'menu_icon'           => 'dashicons-editor-help',
            'supports'            => array('title', 'editor', 'revisions'),
            'show_in_rest'        => true,
        );

        register_post_type('parish_faq', $args);
    }

    /**
     * Register FAQ category taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => 'FAQ Categories',
            'singular_name'     => 'FAQ Category',
            'search_items'      => 'Search FAQ Categories',
            'all_items'         => 'All FAQ Categories',
            'parent_item'       => 'Parent FAQ Category',
            'parent_item_colon' => 'Parent FAQ Category:',
            'edit_item'         => 'Edit FAQ Category',
            'update_item'       => 'Update FAQ Category',
            'add_new_item'      => 'Add New FAQ Category',
            'new_item_name'     => 'New FAQ Category Name',
            'menu_name'         => 'Categories',
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'show_in_rest'      => true,
        );

        register_taxonomy('faq_category', array('parish_faq'), $args);

        // Register FAQ tags taxonomy
        $tag_labels = array(
            'name'              => 'FAQ Tags',
            'singular_name'     => 'FAQ Tag',
            'search_items'      => 'Search FAQ Tags',
            'all_items'         => 'All FAQ Tags',
            'edit_item'         => 'Edit FAQ Tag',
            'update_item'       => 'Update FAQ Tag',
            'add_new_item'      => 'Add New FAQ Tag',
            'new_item_name'     => 'New FAQ Tag Name',
            'menu_name'         => 'Tags',
        );

        $tag_args = array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'show_in_rest'      => true,
        );

        register_taxonomy('faq_tag', array('parish_faq'), $tag_args);
    }

    /**
     * Add meta boxes for FAQ settings
     */
    public function add_meta_boxes() {
        add_meta_box(
            'parish_faq_settings',
            'FAQ Settings',
            array($this, 'render_meta_box'),
            'parish_faq',
            'side',
            'default'
        );
    }

    /**
     * Render the FAQ settings meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('parish_faq_meta', 'parish_faq_meta_nonce');

        $priority = get_post_meta($post->ID, '_parish_faq_priority', true);
        $start_date = get_post_meta($post->ID, '_parish_faq_start_date', true);
        $end_date = get_post_meta($post->ID, '_parish_faq_end_date', true);

        if ($priority === '') {
            $priority = 10;
        }
        ?>
        <p>
            <label for="parish_faq_priority"><strong>Priority</strong></label><br>
            <input type="number" id="parish_faq_priority" name="parish_faq_priority"
                   value="<?php echo esc_attr($priority); ?>" min="1" max="100" style="width: 80px;">
            <br><span class="description">Lower number = shown first (1-100)</span>
        </p>

        <p>
            <label for="parish_faq_start_date"><strong>Start Date</strong></label><br>
            <input type="date" id="parish_faq_start_date" name="parish_faq_start_date"
                   value="<?php echo esc_attr($start_date); ?>" style="width: 100%;">
            <br><span class="description">Optional: FAQ hidden before this date</span>
        </p>

        <p>
            <label for="parish_faq_end_date"><strong>End Date</strong></label><br>
            <input type="date" id="parish_faq_end_date" name="parish_faq_end_date"
                   value="<?php echo esc_attr($end_date); ?>" style="width: 100%;">
            <br><span class="description">Optional: FAQ hidden after this date</span>
        </p>
        <?php
    }

    /**
     * Save FAQ meta data
     */
    public function save_meta($post_id, $post) {
        if (!isset($_POST['parish_faq_meta_nonce']) ||
            !wp_verify_nonce($_POST['parish_faq_meta_nonce'], 'parish_faq_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save priority
        if (isset($_POST['parish_faq_priority'])) {
            $priority = intval($_POST['parish_faq_priority']);
            $priority = max(1, min(100, $priority));
            update_post_meta($post_id, '_parish_faq_priority', $priority);
        }

        // Save start date
        if (isset($_POST['parish_faq_start_date'])) {
            $start_date = sanitize_text_field($_POST['parish_faq_start_date']);
            update_post_meta($post_id, '_parish_faq_start_date', $start_date);
        }

        // Save end date
        if (isset($_POST['parish_faq_end_date'])) {
            $end_date = sanitize_text_field($_POST['parish_faq_end_date']);
            update_post_meta($post_id, '_parish_faq_end_date', $end_date);
        }
    }

    /**
     * Add custom admin columns
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['priority'] = 'Priority';
                $new_columns['date_range'] = 'Active Dates';
            }
        }
        return $new_columns;
    }

    /**
     * Render custom admin columns
     */
    public function render_admin_columns($column, $post_id) {
        switch ($column) {
            case 'priority':
                $priority = get_post_meta($post_id, '_parish_faq_priority', true);
                echo esc_html($priority ?: '10');
                break;
            case 'date_range':
                $start = get_post_meta($post_id, '_parish_faq_start_date', true);
                $end = get_post_meta($post_id, '_parish_faq_end_date', true);
                if ($start || $end) {
                    $parts = array();
                    if ($start) {
                        $parts[] = 'From: ' . date('j M Y', strtotime($start));
                    }
                    if ($end) {
                        $parts[] = 'Until: ' . date('j M Y', strtotime($end));
                    }
                    echo esc_html(implode(' | ', $parts));
                } else {
                    echo '<span style="color: #999;">Always shown</span>';
                }
                break;
        }
    }

    /**
     * Make priority column sortable
     */
    public function sortable_columns($columns) {
        $columns['priority'] = 'priority';
        return $columns;
    }

    /**
     * Handle admin sorting by priority
     */
    public function admin_order_by_priority($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'parish_faq') {
            return;
        }

        // Default order by priority if no orderby specified, with ID as secondary sort
        $orderby = $query->get('orderby');
        if (empty($orderby) || $orderby === 'priority') {
            $query->set('meta_key', '_parish_faq_priority');
            $query->set('orderby', array(
                'meta_value_num' => 'ASC',
                'ID'             => 'ASC',
            ));
        }
    }

    /**
     * Render the FAQ shortcode
     */
    public function render_faq_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category'  => '',
            'tags'      => '',
            'limit'     => -1,
            'collapsed' => 'true',
            'per_page'  => 10,
            'randomize' => 'false',
        ), $atts);

        $today = date('Y-m-d');

        $query_args = array(
            'post_type'      => 'parish_faq',
            'post_status'    => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_key'       => '_parish_faq_priority',
            'orderby'        => array(
                'meta_value_num' => 'ASC',
                'title'          => 'ASC',
            ),
            // Date filtering via meta_query
            'meta_query'     => array(
                'relation' => 'AND',
                // Priority must exist for ordering
                array(
                    'key'     => '_parish_faq_priority',
                    'compare' => 'EXISTS',
                ),
                // Start date check: either not set or in the past
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_parish_faq_start_date',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_parish_faq_start_date',
                        'value'   => '',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_parish_faq_start_date',
                        'value'   => $today,
                        'compare' => '<=',
                        'type'    => 'DATE',
                    ),
                ),
            ),
        );

        // Add end date filter separately (WordPress meta_query can be complex)
        // We'll filter in PHP for simplicity

        // Build tax_query for category and/or tags
        $tax_queries = array();

        if (!empty($atts['category'])) {
            $categories = array_map('trim', explode(',', $atts['category']));
            $categories = array_map('sanitize_text_field', $categories);
            $tax_queries[] = array(
                'taxonomy' => 'faq_category',
                'field'    => 'slug',
                'terms'    => $categories,
            );
        }

        if (!empty($atts['tags'])) {
            $tags = array_map('trim', explode(',', $atts['tags']));
            $tags = array_map('sanitize_text_field', $tags);
            $tax_queries[] = array(
                'taxonomy' => 'faq_tag',
                'field'    => 'slug',
                'terms'    => $tags,
            );
        }

        if (!empty($tax_queries)) {
            if (count($tax_queries) > 1) {
                $tax_queries['relation'] = 'AND';
            }
            $query_args['tax_query'] = $tax_queries;
        }

        $faqs = new WP_Query($query_args);

        // Filter by end date in PHP
        $filtered_posts = array();
        if ($faqs->have_posts()) {
            while ($faqs->have_posts()) {
                $faqs->the_post();
                $end_date = get_post_meta(get_the_ID(), '_parish_faq_end_date', true);
                if (empty($end_date) || $end_date >= $today) {
                    $filtered_posts[] = get_post();
                }
            }
        }
        wp_reset_postdata();

        // Randomize order within each priority group if requested
        if (filter_var($atts['randomize'], FILTER_VALIDATE_BOOLEAN) && !empty($filtered_posts)) {
            $grouped = array();
            foreach ($filtered_posts as $post) {
                $priority = get_post_meta($post->ID, '_parish_faq_priority', true) ?: 10;
                $grouped[$priority][] = $post;
            }
            ksort($grouped, SORT_NUMERIC);
            foreach ($grouped as &$group) {
                shuffle($group);
            }
            $filtered_posts = array_merge(...array_values($grouped));
        }

        ob_start();
        include PARISH_FAQ_PLUGIN_DIR . 'templates/faq-accordion.php';
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'parish-faq',
            PARISH_FAQ_PLUGIN_URL . 'assets/css/parish-faq.css',
            array(),
            PARISH_FAQ_VERSION
        );

        wp_enqueue_script(
            'parish-faq',
            PARISH_FAQ_PLUGIN_URL . 'assets/js/parish-faq.js',
            array(),
            PARISH_FAQ_VERSION,
            true
        );
    }
}
