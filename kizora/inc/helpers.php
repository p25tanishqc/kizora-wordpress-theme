<?php
/**
 * Data access and shared markup. Every piece of markup here mirrors a component in the
 * prototype (src/components/ui.tsx) so the Tailwind classes, and therefore the look, match.
 */
defined('ABSPATH') || exit;

/* ------------------------------------------------------------------ data */

/** Decoded JSON from inc/data (exported from the prototype). */
function kz_data(string $name) {
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = KZ_DIR . "/inc/data/{$name}.json";
        $cache[$name] = file_exists($file) ? json_decode((string) file_get_contents($file), true) : [];
    }
    return $cache[$name];
}

/** Prototype record for a product or kit, or null. Saved product meta (_kz_data) wins over the file. */
function kz_item(string $slug): ?array {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (kz_data('products') as $p) $map[$p['slug']] = $p;
    }
    $item = $map[$slug] ?? null;
    $pid = kz_pid($slug);
    if ($pid) {
        $saved = get_post_meta($pid, '_kz_data', true);
        if (is_array($saved) && $saved) $item = array_merge($item ?? [], $saved);
        if (!$item && function_exists('wc_get_product') && ($wc = wc_get_product($pid))) {
            // A product Kizora added in WooCommerce later: build the same shape from its own fields.
            $cats = wp_get_post_terms($pid, 'product_cat', ['fields' => 'slugs']);
            $item = [
                'slug' => $slug, 'name' => $wc->get_name(), 'kind' => 'product', 'size' => (string) $wc->get_attribute('size'),
                'price' => (float) $wc->get_price(), 'oneLine' => wp_strip_all_tags($wc->get_short_description()),
                'description' => array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', wp_strip_all_tags($wc->get_description()))))),
                'howToUse' => '', 'when' => '', 'forWhom' => '', 'keyIngredients' => [], 'fullIngredients' => '', 'ingredientSlugs' => [],
                'collections' => is_array($cats) ? $cats : [], 'needs' => [], 'skin' => ['all'], 'faqs' => [], 'useWith' => [], 'sku' => $wc->get_sku(),
            ];
        }
    }
    return $item;
}

/** All items in the prototype's order (19 products, then 5 kits). */
function kz_items(): array {
    $items = array_values(array_filter(array_map(fn($p) => kz_item($p['slug']), kz_data('products'))));
    if (function_exists('wc_get_products')) {
        // Products added in WooCommerce after the import go to the end.
        $known = array_flip(array_column($items, 'slug'));
        $extra = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'fields' => 'ids']);
        foreach ($extra as $id) {
            $slug = kz_slug_of((int) $id);
            if (!isset($known[$slug]) && ($it = kz_item($slug))) $items[] = $it;
        }
    }
    return $items;
}

/**
 * WooCommerce product ID for a prototype slug, or 0. Products matched by the importer carry
 * `_kz_slug`, so an existing product keeps its own URL and still powers the prototype's pages.
 */
function kz_pid(string $slug): int {
    static $map = null;
    if ($map === null) {
        global $wpdb;
        $map = [];
        $rows = $wpdb->get_results("SELECT pm.meta_value AS s, p.ID AS id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_kz_slug' AND p.post_type = 'product' AND p.post_status = 'publish'");
        foreach ((array) $rows as $r) $map[$r->s] = (int) $r->id;
    }
    if (!array_key_exists($slug, $map)) {
        $post = get_page_by_path($slug, OBJECT, 'product');
        $map[$slug] = $post && $post->post_status === 'publish' ? (int) $post->ID : 0;
    }
    return $map[$slug];
}

/** Prototype slug for a WooCommerce product ID (the reverse of kz_pid). */
function kz_slug_of(int $pid): string {
    $s = get_post_meta($pid, '_kz_slug', true);
    return $s ?: (string) get_post_field('post_name', $pid);
}

function kz_wc(string $slug) {
    $pid = kz_pid($slug);
    return ($pid && function_exists('wc_get_product')) ? wc_get_product($pid) : null;
}

/** Component slugs for a kit, or [$slug] for a product. */
function kz_parts(string $slug): array {
    $item = kz_item($slug);
    return ($item && ($item['kind'] ?? '') === 'kit' && !empty($item['components'])) ? $item['components'] : [$slug];
}

function kz_is_kit(string $slug): bool {
    $item = kz_item($slug);
    return $item && ($item['kind'] ?? '') === 'kit';
}

/** Live price: WooCommerce first, prototype data as fallback. Kits are the sum of their products. */
function kz_price_num(string $slug): float {
    if (kz_is_kit($slug)) {
        return array_sum(array_map('kz_price_num', kz_parts($slug)));
    }
    $wc = kz_wc($slug);
    if ($wc && $wc->get_price() !== '') return (float) $wc->get_price();
    $item = kz_item($slug);
    return (float) ($item['price'] ?? 0);
}

/** ₹ with Indian digit grouping: ₹1,060 · ₹1,23,456. */
function kz_inr($n): string {
    $n = (int) round((float) $n);
    $s = (string) abs($n);
    if (strlen($s) > 3) {
        $last = substr($s, -3);
        $rest = substr($s, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $s = $rest . ',' . $last;
    }
    return ($n < 0 ? '-' : '') . '₹' . $s;
}

function kz_price(string $slug): string { return kz_inr(kz_price_num($slug)); }

/** Free-delivery threshold shown across the site (Customizer → Kizora). */
function kz_free_delivery(): int { return (int) get_theme_mod('kz_free_delivery', 499); }

/**
 * Product photo. Uses the WooCommerce product image when one is set, otherwise the photo
 * bundled with the theme. $v = 'card' (600×750) or 'main' (1600×2000).
 */
function kz_img(string $slug, string $v = 'card'): string {
    $slug = kz_parts($slug)[0];
    $pid = kz_pid($slug);
    if ($pid && ($thumb = get_post_thumbnail_id($pid))) {
        $url = wp_get_attachment_image_url($thumb, $v === 'card' ? 'kz-card' : 'kz-main');
        if ($url) return $url;
    }
    $file = "/assets/images/products/{$slug}/" . ($v === 'card' ? 'card' : '1') . '.webp';
    if (file_exists(KZ_DIR . $file)) return KZ_URI . $file;
    return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src($v === 'card' ? 'kz-card' : 'kz-main') : '';
}

/** Gallery for a product page: WooCommerce images if set, else the bundled photo. */
function kz_photos(string $slug): array {
    $wc = kz_wc($slug);
    $ids = [];
    if ($wc) {
        if ($wc->get_image_id()) $ids[] = (int) $wc->get_image_id();
        $ids = array_merge($ids, array_map('intval', $wc->get_gallery_image_ids()));
    }
    if ($ids) return array_values(array_filter(array_map(fn($id) => wp_get_attachment_image_url($id, 'kz-main'), $ids)));
    return [kz_img($slug, 'main')];
}

/** Product display name: prototype's short name, else the WooCommerce title. */
function kz_name(string $slug): string {
    $item = kz_item($slug);
    if (!empty($item['name'])) return $item['name'];
    $wc = kz_wc($slug);
    return $wc ? $wc->get_name() : $slug;
}

/**
 * Site URL for a prototype path. Shop, cart, collection and product paths resolve to the real
 * WooCommerce URLs, so links keep working whatever permalink settings the live store uses.
 */
function kz_url(string $path = '/'): string {
    $q = '';
    if (($pos = strpos($path, '?')) !== false) { $q = substr($path, $pos); $path = substr($path, 0, $pos); }
    $hash = '';
    if (($pos = strpos($path, '#')) !== false) { $hash = substr($path, $pos); $path = substr($path, 0, $pos); }
    $url = null;
    if (function_exists('wc_get_page_permalink')) {
        if (preg_match('#^/products/([a-z0-9-]+)/?$#', $path, $m) && ($pid = kz_pid($m[1]))) $url = get_permalink($pid);
        elseif (preg_match('#^/collections/([a-z0-9-]+)/?$#', $path, $m) && ($t = get_term_by('slug', $m[1], 'product_cat'))) $url = get_term_link($t);
        elseif (preg_match('#^/shop/?$#', $path)) $url = wc_get_page_permalink('shop');
        elseif (preg_match('#^/cart/?$#', $path)) $url = wc_get_cart_url();
        elseif (preg_match('#^/my-account/?$#', $path)) $url = wc_get_page_permalink('myaccount');
    }
    if (!$url || is_wp_error($url)) $url = home_url($path);
    return $url . $q . $hash;
}

function kz_product_url(string $slug): string { return kz_url("/products/{$slug}/"); }

function kz_collection(string $slug): ?array {
    foreach (kz_data('collections') as $c) if ($c['slug'] === $slug) return $c;
    return null;
}

function kz_ingredient(string $slug): ?array {
    foreach (kz_data('ingredients') as $i) if ($i['slug'] === $slug) return $i;
    return null;
}

/** WooCommerce product IDs to add for an item (kits expand to their products). */
function kz_cart_ids(string $slug): array {
    return array_values(array_filter(array_map('kz_pid', kz_parts($slug))));
}

/* ---------------------------------------------------------------- markup */

function kz_esc(string $s): string { return esc_html($s); }

function kz_json_ld(array $data): string {
    return '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/** Breadcrumbs (ui.tsx Crumbs). $items = [['name'=>..,'href'=>..], ...] with root-relative hrefs. */
function kz_crumbs(array $items): string {
    $all = array_merge([['name' => 'Home', 'href' => '/']], $items);
    $n = count($all);
    $li = '';
    foreach ($all as $i => $c) {
        $li .= '<li class="flex items-center gap-1.5">';
        if ($i > 0) $li .= '<span aria-hidden="true" class="text-line">/</span>';
        if (!empty($c['href']) && $i < $n - 1) {
            $li .= '<a href="' . esc_url(kz_url($c['href'])) . '" class="hover:text-ink">' . esc_html($c['name']) . '</a>';
        } else {
            $li .= '<span aria-current="page" class="text-ink">' . esc_html($c['name']) . '</span>';
        }
        $li .= '</li>';
    }
    $ld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
        fn($c, $i) => array_filter(['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => !empty($c['href']) ? kz_url($c['href']) : null]),
        $all, array_keys($all)
    )];
    return '<nav aria-label="Breadcrumb" class="text-[13px] text-ink2"><ol class="flex flex-wrap items-center gap-1.5">' . $li . '</ol></nav>' . kz_json_ld($ld);
}

/** Product card (ui.tsx ProductCard). */
function kz_card(string $slug, ?string $note = null, bool $priority = false, array $attrs = []): string {
    $item = kz_item($slug);
    if (!$item) return '';
    $name = kz_name($slug);
    $size = $item['size'] ?? '';
    $kit = kz_is_kit($slug);
    $alt = $size ? "{$name}, {$size}" : $name;
    $media = $kit
        ? '<div class="flex h-full items-center bg-white">' . kz_mosaic($item['components'], 'card', null, 'w-full transition-transform duration-700 group-hover:scale-[1.03]') . '</div>'
        : '<img src="' . esc_url(kz_img($slug, 'card')) . '" alt="' . esc_attr($alt) . '" loading="' . ($priority ? 'eager' : 'lazy') . '" width="600" height="750" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.03]">';
    $badge = $kit ? '<span class="absolute left-3 top-3 rounded-sm bg-cream/90 px-2 py-1 text-[11px] font-medium uppercase tracking-[0.12em]">Kit</span>' : '';
    $extra = '';
    foreach ($attrs as $k => $v) $extra .= ' ' . $k . '="' . esc_attr($v) . '"';
    return '<a href="' . esc_url(kz_product_url($slug)) . '" class="group block"' . $extra . '>'
        . '<div class="relative aspect-[4/5] overflow-hidden rounded-[3px] bg-paper">' . $media . $badge . '</div>'
        . '<div class="mt-3.5 md:flex md:items-start md:justify-between md:gap-3"><div>'
        . '<h3 class="font-serif text-[17px] leading-snug group-hover:underline group-hover:decoration-gold/70 group-hover:underline-offset-4 md:text-[18px]">' . esc_html($name) . '</h3>'
        . '<p class="mt-0.5 hidden text-[13.5px] text-ink2 sm:block">' . esc_html($note ?? ($item['oneLine'] ?? '')) . '</p></div>'
        . '<div class="mt-1 flex items-baseline gap-2 md:mt-0 md:block md:shrink-0 md:text-right"><div class="font-medium">' . esc_html(kz_price($slug)) . '</div>'
        . ($size ? '<div class="text-[12px] text-ink2">' . esc_html($size) . '</div>' : '')
        . '</div></div></a>';
}

/** Grid of cards (ui.tsx ProductGrid). */
function kz_grid(array $slugs, string $cols = 'sm:grid-cols-2 lg:grid-cols-4'): string {
    $cards = implode('', array_map(fn($s) => kz_card($s), $slugs));
    return '<div class="grid grid-cols-2 gap-x-4 gap-y-10 md:gap-x-6 ' . esc_attr($cols) . '">' . $cards . '</div>';
}

/** Photos side by side, uncropped (ui.tsx Mosaic). */
function kz_mosaic(array $slugs, string $size = 'main', ?int $cols = null, string $class = '', bool $priority = false): string {
    $parts = [];
    foreach ($slugs as $s) foreach (kz_parts($s) as $p) $parts[$p] = true;
    $parts = array_keys($parts);
    $grid = ['', 'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4', 'grid-cols-5'][$cols ?? min(count($parts), 5)] ?? 'grid-cols-5';
    $w = $size === 'card' ? 600 : 1200;
    $h = $size === 'card' ? 750 : 1500;
    $imgs = '';
    foreach ($parts as $p) {
        $imgs .= '<img src="' . esc_url(kz_img($p, $size)) . '" alt="' . esc_attr(kz_name($p)) . '" width="' . $w . '" height="' . $h . '" loading="' . ($priority ? 'eager' : 'lazy') . '" class="aspect-[4/5] w-full object-cover">';
    }
    return '<div class="grid gap-1.5 bg-white ' . $grid . ' ' . esc_attr($class) . '">' . $imgs . '</div>';
}

/** Accordion FAQ (ui.tsx Faq). */
function kz_faq(array $items): string {
    $out = '';
    foreach ($items as $f) {
        $out .= '<details class="group py-5"><summary class="flex items-start justify-between gap-6 font-serif text-[19px]"><span>' . esc_html($f['q']) . '</span><span class="chev mt-1 text-xl leading-none text-ink2 transition-transform" aria-hidden="true">+</span></summary>'
            . '<p class="mt-3 max-w-3xl text-ink2">' . esc_html($f['a']) . '</p></details>';
    }
    return '<div class="divide-y divide-line border-y border-line">' . $out . '</div>';
}

/** "Add" button that adds an item (or a kit's products) to the WooCommerce cart via AJAX. */
function kz_add_button(string $slug, string $label, string $class = 'btn w-full', int $qty = 1, string $aria = ''): string {
    $ids = kz_cart_ids($slug);
    if (!$ids) {
        return '<a href="' . esc_url(kz_product_url($slug)) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
    }
    $fallback = add_query_arg(['kz_add' => implode(',', $ids), '_kz' => wp_create_nonce('kz_add')], wc_get_cart_url());
    return '<a href="' . esc_url($fallback) . '" role="button" data-kz-add="' . esc_attr(implode(',', $ids)) . '" data-qty="' . (int) $qty . '" class="' . esc_attr($class) . '"' . ($aria ? ' aria-label="' . esc_attr($aria) . '"' : '') . '>' . esc_html($label) . '</a>';
}
