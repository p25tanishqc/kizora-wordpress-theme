<?php
/** Theme support, assets, content types, shortcodes and page routing. */
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce', ['thumbnail_image_width' => 600, 'single_image_width' => 1200]);
    add_image_size('kz-card', 600, 750, true);
    add_image_size('kz-main', 1600, 2000, true);
});

/* ---------------------------------------------------------------- assets */

add_action('wp_enqueue_scripts', function () {
    $css = KZ_DIR . '/assets/css/kizora.css';
    wp_enqueue_style('kizora', KZ_URI . '/assets/css/kizora.css', [], KZ_VERSION . '.' . (file_exists($css) ? filemtime($css) : 0));
    $js = KZ_DIR . '/assets/js/kizora.js';
    wp_enqueue_script('kizora', KZ_URI . '/assets/js/kizora.js', [], KZ_VERSION . '.' . (file_exists($js) ? filemtime($js) : 0), ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('kizora', 'KZ', [
        'wcAjax'  => class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('%%endpoint%%') : '',
        'ajax'    => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('kz_cart'),
        'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
    ]);
}, 20);

/** Preload the two fonts used above the fold. */
add_action('wp_head', function () {
    foreach (['playfair-display-latin-500-normal', 'jost-latin-400-normal'] as $f) {
        echo '<link rel="preload" href="' . esc_url(KZ_URI . "/assets/fonts/{$f}.woff2") . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
}, 1);

/* ------------------------------------------------------------ customizer */

add_action('customize_register', function (WP_Customize_Manager $c) {
    $c->add_section('kizora', ['title' => 'Kizora', 'priority' => 30]);
    $c->add_setting('kz_free_delivery', ['default' => 499, 'sanitize_callback' => 'absint']);
    $c->add_control('kz_free_delivery', ['section' => 'kizora', 'type' => 'number', 'label' => 'Free delivery above (₹)',
        'description' => 'Shown in the top bar, cart and product pages. Keep it the same as the WooCommerce free shipping rule.']);
    $c->add_setting('kz_announcement', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $c->add_control('kz_announcement', ['section' => 'kizora', 'type' => 'text', 'label' => 'Top bar text (optional)',
        'description' => 'Leave empty to show the free-delivery line.']);
});

/* --------------------------------------------------------- content types */

add_action('init', function () {
    register_post_type('kz_ingredient', [
        'labels' => ['name' => 'Ingredients', 'singular_name' => 'Ingredient', 'add_new_item' => 'Add ingredient', 'edit_item' => 'Edit ingredient'],
        'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-carrot', 'menu_position' => 21,
        'has_archive' => 'ingredients', 'rewrite' => ['slug' => 'ingredients', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'],
    ]);
    register_post_type('kz_journal', [
        'labels' => ['name' => 'Journal', 'singular_name' => 'Journal article', 'add_new_item' => 'Add article', 'edit_item' => 'Edit article'],
        'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-book-alt', 'menu_position' => 22,
        'has_archive' => 'journal', 'rewrite' => ['slug' => 'journal', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'],
    ]);
});

add_action('after_switch_theme', function () { flush_rewrite_rules(); });

/* ------------------------------------------------------------ shortcodes */

add_shortcode('kz_card', fn($a) => kz_card((string) ($a['slug'] ?? '')));
add_shortcode('kz_price', fn($a) => esc_html(kz_price((string) ($a['slug'] ?? ''))));
add_shortcode('kz_free_delivery', fn() => esc_html(kz_inr(kz_free_delivery())));
add_shortcode('kz_grid', fn($a) => kz_grid(array_filter(array_map('trim', explode(',', (string) ($a['slugs'] ?? '')))), (string) ($a['cols'] ?? 'sm:grid-cols-2 lg:grid-cols-4')));

/**
 * Converted pages store links and images root-relative (/about/, /images/...). Map them at render
 * time so the site also works in a sub-folder, and product photos follow WooCommerce images.
 */
function kz_map_paths(string $html): string {
    $html = preg_replace_callback('#(src|srcset)="/images/products/([a-z0-9-]+)/(card|1)\.webp"#', function ($m) {
        return $m[1] . '="' . esc_url(kz_img($m[2], $m[3] === 'card' ? 'card' : 'main')) . '"';
    }, $html);
    $html = str_replace(['src="/images/', 'srcset="/images/'], ['src="' . KZ_URI . '/assets/images/', 'srcset="' . KZ_URI . '/assets/images/'], $html);
    $html = preg_replace_callback('#href="(/(?!/)[^"]*)"#', fn($m) => 'href="' . esc_url(kz_url(html_entity_decode($m[1]))) . '"', $html);
    return $html;
}
add_filter('the_content', function ($html) {
    return (is_admin() || !is_string($html)) ? $html : kz_map_paths($html);
}, 20);

/* --------------------------------------------------------------- routing */

/**
 * Pages created by the importer carry a `_kz_view` meta that picks a PHP view
 * (e.g. ritual-morning). Pages without it use page.php and their editor content.
 */
add_filter('template_include', function ($template) {
    if (is_page()) {
        $view = get_post_meta(get_queried_object_id(), '_kz_view', true);
        if ($view && file_exists($f = KZ_DIR . "/template-parts/view-{$view}.php")) return $f;
    }
    return $template;
});

/** Body class hook for pages that need the dark header treatment, etc. */
add_filter('body_class', function ($c) {
    if (is_page() && ($v = get_post_meta(get_queried_object_id(), '_kz_view', true))) $c[] = 'kz-view-' . sanitize_html_class($v);
    return $c;
});

/** Remove the WordPress emoji script and block-library CSS on the front end (the theme has its own styles). */
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
});
add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !has_blocks()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }
}, 100);
