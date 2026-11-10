<?php
/**
 * Titles and meta descriptions from the prototype. When Yoast SEO is active, Yoast stays in charge
 * (the importer copies the same titles and descriptions into Yoast's fields instead).
 */
defined('ABSPATH') || exit;

function kz_seo_active(): bool { return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION'); }

/** [title, description] for the current request, or [null, null]. */
function kz_seo_meta(): array {
    if (is_singular()) {
        $id = get_queried_object_id();
        $t = get_post_meta($id, '_kz_title', true);
        $d = get_post_meta($id, '_kz_desc', true);
        if (!$t && is_singular('product')) {
            $p = kz_item(kz_slug_of($id));
            if ($p) {
                $t = $p['name'] . (($p['kind'] ?? '') === 'product' && !empty($p['size']) ? ", {$p['size']}" : '') . ' | Kizora';
                $d = mb_substr(trim(($p['oneLine'] ?? '') . ' ' . ($p['forWhom'] ?? '') . '. ' . kz_price($p['slug']) . '.'), 0, 158);
            }
        }
        return [$t ?: null, $d ?: null];
    }
    if (function_exists('is_product_category') && is_product_category() && ($c = kz_collection(get_queried_object()->slug))) {
        return [$c['title'] ?? null, $c['description'] ?? null];
    }
    if (function_exists('is_shop') && is_shop()) {
        return ['Shop all Kizora skincare | Gels, mists, oils and soaps', 'Every Kizora product and ritual kit: aloe gels, face mists, moringa oil, natural soaps, foot butter, lip balms and hair oils.'];
    }
    if (is_post_type_archive('kz_ingredient')) {
        return ['Know your ingredients | Kizora ingredient library', 'What aloe, rose, moringa, turmeric, ghee, kuppaimeni and more are, what they can do on skin, and which Kizora products contain them.'];
    }
    if (is_post_type_archive('kz_journal')) {
        return ['Journal | Notes from Kizora', 'How our soaps are made, how to use moringa oil, and simple seasonal rituals.'];
    }
    return [null, null];
}

add_filter('pre_get_document_title', function ($title) {
    if (kz_seo_active()) return $title;
    [$t] = kz_seo_meta();
    return $t ?: $title;
}, 20);

add_action('wp_head', function () {
    if (kz_seo_active()) return;
    [$t, $d] = kz_seo_meta();
    if ($d) echo '<meta name="description" content="' . esc_attr($d) . '">' . "\n";
    if ($t) echo '<meta property="og:title" content="' . esc_attr($t) . '">' . "\n";
    if ($d) echo '<meta property="og:description" content="' . esc_attr($d) . '">' . "\n";
    echo '<meta property="og:site_name" content="Kizora Skincare"><meta property="og:locale" content="en_IN">' . "\n";
}, 2);
