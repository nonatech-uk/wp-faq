<?php
/**
 * FAQ Accordion Template
 *
 * Variables available:
 * - $filtered_posts: Array of FAQ posts (already filtered by date)
 * - $atts: Shortcode attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

$collapsed = ($atts['collapsed'] === 'true' || $atts['collapsed'] === true);
$per_page = intval($atts['per_page']);
$total_faqs = count($filtered_posts);
$total_pages = $per_page > 0 ? ceil($total_faqs / $per_page) : 1;
$show_pagination = $per_page > 0 && $total_faqs > $per_page;
?>

<div class="parish-faq-container"
     data-collapsed="<?php echo $collapsed ? 'true' : 'false'; ?>"
     data-per-page="<?php echo esc_attr($per_page); ?>"
     data-total="<?php echo esc_attr($total_faqs); ?>">

    <?php if (!empty($filtered_posts)): ?>
        <?php if ($show_pagination): ?>
        <div class="parish-faq-controls">
            <div class="parish-faq-per-page">
                <label for="parish-faq-per-page-select">Show:</label>
                <select id="parish-faq-per-page-select" class="parish-faq-per-page-select">
                    <option value="5" <?php selected($per_page, 5); ?>>5</option>
                    <option value="10" <?php selected($per_page, 10); ?>>10</option>
                    <option value="25" <?php selected($per_page, 25); ?>>25</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="parish-faq-page-info">
                Showing <span class="parish-faq-showing-start">1</span>-<span class="parish-faq-showing-end"><?php echo min($per_page, $total_faqs); ?></span> of <span class="parish-faq-total"><?php echo $total_faqs; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="parish-faq-accordion">
            <?php foreach ($filtered_posts as $index => $faq): ?>
                <div class="parish-faq-item" data-index="<?php echo $index; ?>">
                    <button class="parish-faq-question" aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>">
                        <span class="parish-faq-question-text"><?php echo esc_html($faq->post_title); ?></span>
                        <span class="parish-faq-toggle" aria-hidden="true"></span>
                    </button>
                    <div class="parish-faq-answer" <?php echo $collapsed ? 'hidden' : ''; ?>>
                        <?php echo apply_filters('the_content', $faq->post_content); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($show_pagination): ?>
        <div class="parish-faq-pagination">
            <button class="parish-faq-page-btn parish-faq-prev" disabled>&laquo; Previous</button>
            <span class="parish-faq-page-numbers"></span>
            <button class="parish-faq-page-btn parish-faq-next">Next &raquo;</button>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <p class="parish-faq-no-results">No FAQs found.</p>
    <?php endif; ?>
</div>
