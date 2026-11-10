<?php
/** WooCommerce integration: cart drawer, AJAX cart, ritual kits, styles. */
defined('ABSPATH') || exit;

/* ------------------------------------------------------------ styles */

/** WooCommerce's own CSS is only needed for checkout and account forms; the theme styles the rest. */
add_filter('woocommerce_enqueue_styles', function ($styles) {
    return (is_checkout() || is_account_page()) ? $styles : [];
});
add_action('wp_enqueue_scripts', function () {
    if (is_checkout() || is_account_page() || is_cart()) {
        $f = KZ_DIR . '/assets/css/woo.css';
        wp_enqueue_style('kizora-woo', KZ_URI . '/assets/css/woo.css', ['kizora'], KZ_VERSION . '.' . (file_exists($f) ? filemtime($f) : 0));
    }
}, 30);

/** The theme prints its own Product schema (inc/seo.php), so WooCommerce's copy would be a duplicate. */
add_action('init', function () {
    if (function_exists('WC') && WC()->structured_data) {
        remove_action('wp_footer', [WC()->structured_data, 'output_structured_data'], 10);
    }
});

/* ------------------------------------------------------------- kits */

/** Kits are bundles of real products: the kit itself is never added, its products are. */
add_filter('woocommerce_is_purchasable', function ($ok, $product) {
    return get_post_meta($product->get_id(), '_kz_kit', true) ? false : $ok;
}, 10, 2);

/** No-JavaScript fallback for "Add" buttons: ?kz_add=12,34 adds each product, then opens the cart. */
add_action('template_redirect', function () {
    if (empty($_GET['kz_add']) || !function_exists('WC')) return;
    if (!wp_verify_nonce($_GET['_kz'] ?? '', 'kz_add')) return;
    $qty = max(1, (int) ($_GET['quantity'] ?? 1));
    foreach (array_filter(array_map('absint', explode(',', (string) $_GET['kz_add']))) as $id) {
        WC()->cart->add_to_cart($id, $qty);
    }
    wp_safe_redirect(wc_get_cart_url());
    exit;
});

/** "Empty cart" link on the cart page. */
add_action('template_redirect', function () {
    if (!empty($_GET['kz_empty']) && function_exists('WC') && wp_verify_nonce($_GET['_kz'] ?? '', 'kz_empty')) {
        WC()->cart->empty_cart();
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
});

/* ------------------------------------------------------- cart drawer */

function kz_cart_count(): int { return function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; }
function kz_cart_subtotal(): float { return function_exists('WC') && WC()->cart ? (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax() : 0.0; }

/** Delivery progress (cart.tsx DeliveryMeter). */
function kz_delivery_meter(float $subtotal): string {
    $free = max(1, kz_free_delivery());
    $left = max(0, $free - $subtotal);
    $pct = min(100, (int) round($subtotal / $free * 100));
    $msg = $left > 0
        ? 'Add <b class="text-ink">' . esc_html(kz_inr($left)) . '</b> more for free delivery.'
        : 'Your order qualifies for free delivery.';
    return '<div><p class="text-[13.5px] text-ink2">' . $msg . '</p><div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-line"><div class="h-full bg-sage-deep transition-all" style="width:' . $pct . '%"></div></div></div>';
}

/** Cart lines (cart.tsx CartLines), driven by the WooCommerce cart. */
function kz_cart_lines(bool $compact = false): string {
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return '<p class="py-10 text-center text-ink2">Your cart is empty.</p>';
    }
    $out = '';
    foreach (WC()->cart->get_cart() as $key => $line) {
        $p = $line['data'];
        if (!$p) continue;
        $slug = kz_slug_of($p->get_parent_id() ?: $p->get_id());
        $item = kz_item($slug);
        $name = $item['name'] ?? $p->get_name();
        $size = $item['size'] ?? '';
        $url = $p->get_permalink();
        $img = $item ? kz_img($slug, 'card') : (wp_get_attachment_image_url($p->get_image_id(), 'kz-card') ?: wc_placeholder_img_src());
        $qty = (int) $line['quantity'];
        $total = (float) $line['line_subtotal'] + (float) $line['line_subtotal_tax'];
        $out .= '<li class="flex gap-4 py-4">'
            . '<a href="' . esc_url($url) . '" class="block h-24 w-20 shrink-0 overflow-hidden rounded-sm bg-paper"><img src="' . esc_url($img) . '" alt="' . esc_attr($name) . '" class="h-full w-full object-cover"></a>'
            . '<div class="flex min-w-0 flex-1 flex-col"><div class="flex justify-between gap-3">'
            . '<a href="' . esc_url($url) . '" class="font-serif text-[17px] leading-snug hover:underline">' . esc_html($name) . '</a>'
            . '<span class="shrink-0 font-medium">' . esc_html(kz_inr($total)) . '</span></div>'
            . ($size ? '<span class="text-[13px] text-ink2">' . esc_html($size) . '</span>' : '')
            . '<div class="mt-auto flex items-center gap-3 pt-2"><div class="flex items-center border border-line">'
            . '<button type="button" aria-label="' . esc_attr("Decrease {$name}") . '" class="h-8 w-8" data-kz-qty="' . esc_attr($key) . '" data-q="' . ($qty - 1) . '">−</button>'
            . '<span class="w-8 text-center text-sm" aria-live="polite">' . $qty . '</span>'
            . '<button type="button" aria-label="' . esc_attr("Increase {$name}") . '" class="h-8 w-8" data-kz-qty="' . esc_attr($key) . '" data-q="' . ($qty + 1) . '">+</button></div>'
            . (!$compact ? '<button type="button" class="text-[13px] text-ink2 underline" data-kz-qty="' . esc_attr($key) . '" data-q="0">Remove</button>' : '')
            . '</div></div></li>';
    }
    return '<ul class="divide-y divide-line">' . $out . '</ul>';
}

/** Drawer body, refreshed through WooCommerce cart fragments. */
function kz_drawer_body(): string {
    $sub = kz_cart_subtotal();
    return '<div id="kz-drawer-body" class="flex min-h-0 flex-1 flex-col">'
        . '<div class="flex items-center justify-between border-b border-line px-6 py-5"><h2 class="text-2xl">Your cart <span class="text-base text-ink2">(' . kz_cart_count() . ')</span></h2>'
        . '<button type="button" data-kz-close-cart aria-label="Close cart" class="text-2xl leading-none">×</button></div>'
        . '<div class="flex-1 overflow-y-auto px-6">' . kz_cart_lines(true) . '</div>'
        . '<div class="space-y-4 border-t border-line px-6 py-5">' . kz_delivery_meter($sub)
        . '<div class="flex justify-between font-medium"><span>Subtotal</span><span>' . esc_html(kz_inr($sub)) . '</span></div>'
        . '<a href="' . esc_url(wc_get_cart_url()) . '" class="btn w-full">View cart</a>'
        . '<a href="' . esc_url(wc_get_checkout_url()) . '" class="btn-ghost w-full">Checkout</a>'
        . '</div></div>';
}

function kz_cart_count_badge(): string {
    $n = kz_cart_count();
    return '<span class="kz-cart-count absolute right-0 top-0.5 flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-gold px-1 text-[10.5px] font-semibold text-ink' . ($n ? '' : ' hidden') . '">' . $n . '</span>';
}

add_filter('woocommerce_add_to_cart_fragments', function ($f) {
    $f['span.kz-cart-count'] = kz_cart_count_badge();
    $f['#kz-drawer-body'] = kz_drawer_body();
    $f['#kz-cart-page'] = kz_cart_page_body();
    return $f;
});

/** Change a line's quantity from the drawer or cart page (0 removes it). */
function kz_ajax_cart_set() {
    check_ajax_referer('kz_cart', 'nonce');
    $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
    $qty = max(0, (int) ($_POST['qty'] ?? 0));
    if ($key && WC()->cart->get_cart_item($key)) {
        $qty ? WC()->cart->set_quantity($key, $qty, true) : WC()->cart->remove_cart_item($key);
    }
    WC_AJAX::get_refreshed_fragments();
}
add_action('wp_ajax_kz_cart_set', 'kz_ajax_cart_set');
add_action('wp_ajax_nopriv_kz_cart_set', 'kz_ajax_cart_set');

/* --------------------------------------------------------- cart page */

/** Cart page body (cart-view.tsx), with the real WooCommerce checkout. */
function kz_cart_page_body(): string {
    if (!function_exists('WC') || !WC()->cart) return '';
    $sub = kz_cart_subtotal();
    $free = kz_free_delivery();
    $empty = WC()->cart->is_empty();
    $in = [];
    foreach (WC()->cart->get_cart() as $l) $in[] = kz_slug_of((int) $l['product_id']);
    $suggest = array_slice(array_values(array_filter(['morning-ritual-kit', 'evening-ritual-kit', 'winter-night-care-set', 'pure-aloe-vera-gel'], fn($s) => !in_array($s, $in, true) && kz_item($s))), 0, 3);
    $html = '<div id="kz-cart-page" class="grid gap-12 lg:grid-cols-[1.6fr_1fr]"><div>' . kz_cart_lines(false);
    if (!$empty) {
        $html .= '<a href="' . esc_url(add_query_arg(['kz_empty' => 1, '_kz' => wp_create_nonce('kz_empty')], wc_get_cart_url())) . '" class="mt-4 inline-block text-[14px] text-ink2 underline">Empty cart</a>';
    }
    $html .= '</div><aside class="self-start rounded-[3px] bg-paper p-7">' . kz_delivery_meter($sub)
        . '<dl class="mt-6 space-y-2 text-[15px]"><div class="flex justify-between"><dt>Subtotal</dt><dd>' . esc_html(kz_inr($sub)) . '</dd></div>'
        . '<div class="flex justify-between text-ink2"><dt>Delivery</dt><dd>' . ($empty ? '—' : ($sub >= $free ? 'Free' : 'Calculated at checkout')) . '</dd></div>'
        . '<div class="flex justify-between border-t border-line pt-3 font-serif text-[22px]"><dt>Total</dt><dd>' . esc_html(kz_inr($sub)) . '</dd></div></dl>'
        . ($empty ? '<span class="btn mt-6 w-full opacity-50" aria-disabled="true">Checkout</span>'
                  : '<a href="' . esc_url(wc_get_checkout_url()) . '" class="btn mt-6 w-full">Checkout</a>')
        . '<p class="mt-3 text-center text-[12.5px] text-ink2">Taxes included. Delivery and payment options are shown at checkout.</p></aside>';
    if ($suggest) {
        $html .= '<div class="lg:col-span-2"><h2 class="mb-6 mt-6 text-[28px]">' . ($sub < $free ? 'Kits that clear the free-delivery line' : 'You might also like') . '</h2>'
            . '<div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-6">' . implode('', array_map(fn($s) => kz_card($s), $suggest)) . '</div>'
            . '<p class="mt-10"><a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="link">Continue shopping</a></p></div>';
    }
    return $html . '</div>';
}

/* ------------------------------------------------- product-page tweaks */

/** Product categories and tags pages use the theme's collection view; keep all products on one page. */
add_action('pre_get_posts', function (WP_Query $q) {
    if (!is_admin() && $q->is_main_query() && (is_shop() || is_product_category())) {
        $q->set('posts_per_page', -1);
    }
});
