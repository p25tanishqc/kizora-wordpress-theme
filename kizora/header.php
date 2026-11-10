<?php
/** Site header (prototype: src/components/chrome.tsx Header). */
defined('ABSPATH') || exit;
$site = kz_data('site');
$groups = ['Face', 'Body', 'Lips', 'Hair', 'Sets'];
$cols = array_values(array_filter(kz_data('collections'), fn($c) => $c['slug'] !== 'aloe-vera-essentials'));
$path = '/' . ltrim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
if ($home_path && $home_path !== '/' && strpos($path, $home_path) === 0) $path = '/' . substr($path, strlen($home_path));
$active = function (array $prefixes) use ($path) {
    foreach ($prefixes as $p) if (strpos($path, $p) === 0) return 'after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:bg-gold';
    return '';
};
$announce = get_theme_mod('kz_announcement', '') ?: 'Free delivery on orders above ' . kz_inr(kz_free_delivery());
$logo = KZ_URI . '/assets/images/logo.webp';
$shop_count = count(array_filter(kz_data('products'), fn($p) => $p['kind'] === 'product'));
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#F6F1E8">
<?php wp_head(); ?>
</head>
<body <?php body_class('bg-cream text-ink font-sans antialiased'); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:bg-cream focus:p-3">Skip to content</a>
<header class="sticky top-0 z-50 border-b border-line bg-cream/95 backdrop-blur supports-[backdrop-filter]:bg-cream/85">
  <div class="bg-ink text-center text-[12.5px] tracking-[0.04em] text-cream">
    <div class="wrap py-2"><span><?php echo esc_html($announce); ?></span></div>
  </div>
  <div class="wrap flex h-[68px] items-center justify-between md:h-[78px]">
    <button type="button" class="-ml-2 p-2 md:hidden" aria-label="Open menu" aria-expanded="false" data-kz-menu-open>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
    </button>
    <a href="<?php echo esc_url(kz_url('/')); ?>" aria-label="Kizora Skincare, home" class="shrink-0">
      <img src="<?php echo esc_url($logo); ?>" alt="Kizora Skincare" width="671" height="315" class="kz-logo h-[38px] w-auto md:h-[48px]">
    </a>
    <nav aria-label="Main" class="hidden items-center gap-9 text-[13px] font-medium uppercase tracking-[0.14em] md:flex">
      <div class="relative" data-kz-shop>
        <a href="<?php echo esc_url(kz_url('/shop/')); ?>" class="relative <?php echo esc_attr($active(['/shop', '/collections', '/product'])); ?>" aria-haspopup="true" aria-expanded="false">Shop</a>
        <div class="absolute left-1/2 top-full z-50 hidden -translate-x-1/2 pt-6" data-kz-shop-panel>
          <div class="grid w-[640px] grid-cols-5 gap-6 border border-line bg-cream p-7 normal-case tracking-normal shadow-[0_20px_40px_-20px_rgba(58,47,42,.25)]">
            <?php foreach ($groups as $g) : ?>
              <div>
                <p class="eyebrow mb-3 text-[11px]"><?php echo esc_html($g); ?></p>
                <ul class="space-y-2 text-[14.5px] font-normal">
                  <?php foreach ($cols as $c) if ($c['group'] === $g) : ?>
                    <li><a href="<?php echo esc_url(kz_url("/collections/{$c['slug']}/")); ?>" class="hover:underline"><?php echo esc_html($c['name']); ?></a></li>
                  <?php endif; ?>
                </ul>
              </div>
            <?php endforeach; ?>
            <div class="col-span-5 flex items-center justify-between border-t border-line pt-4 text-[14px] font-normal">
              <a href="<?php echo esc_url(kz_url('/shop/')); ?>" class="link">Shop all <?php echo (int) $shop_count; ?> products</a>
              <span class="text-ink2">Or start with a <a href="<?php echo esc_url(kz_url('/rituals/')); ?>" class="link">ritual</a></span>
            </div>
          </div>
        </div>
      </div>
      <a href="<?php echo esc_url(kz_url('/rituals/')); ?>" class="relative <?php echo esc_attr($active(['/rituals'])); ?>">Rituals</a>
      <a href="<?php echo esc_url(kz_url('/ingredients/')); ?>" class="relative <?php echo esc_attr($active(['/ingredients'])); ?>">Ingredients</a>
      <a href="<?php echo esc_url(kz_url('/about/')); ?>" class="relative <?php echo esc_attr($active(['/about'])); ?>">Our story</a>
      <a href="<?php echo esc_url(kz_url('/journal/')); ?>" class="relative <?php echo esc_attr($active(['/journal'])); ?>">Journal</a>
    </nav>
    <div class="flex items-center gap-1 md:gap-3">
      <button type="button" class="p-2" aria-label="Search" aria-expanded="false" data-kz-search-toggle>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
      </button>
      <a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : kz_url('/cart/')); ?>" class="relative -mr-2 p-2" aria-label="Cart" data-kz-open-cart>
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M5 8h14l-1.2 12H6.2L5 8Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
        <?php echo function_exists('kz_cart_count_badge') ? kz_cart_count_badge() : ''; ?>
      </a>
    </div>
  </div>
  <div class="hidden border-t border-line bg-cream" data-kz-search>
    <form class="wrap flex items-center gap-3 py-4" action="<?php echo esc_url(kz_url('/shop/')); ?>" method="get" role="search">
      <label for="site-search" class="sr-only">Search products and ingredients</label>
      <input id="site-search" name="q" placeholder="Search: aloe, rose, moringa, soap, heels…" class="flex-1 border-b border-ink bg-transparent py-2 text-lg outline-none placeholder:text-ink2/60">
      <button class="btn">Search</button>
    </form>
  </div>
</header>
  <div class="pointer-events-none fixed inset-0 z-[70] md:hidden" aria-hidden="true" inert data-kz-menu>
  <div class="absolute inset-0 bg-ink/30 opacity-0 transition-opacity" data-kz-menu-close></div>
  <nav aria-label="Mobile" class="absolute left-0 top-0 h-full w-[86%] max-w-[360px] -translate-x-full overflow-y-auto bg-cream px-6 pb-10 pt-5 transition-transform duration-300" data-kz-menu-panel>
    <div class="mb-6 flex items-center justify-between">
      <img src="<?php echo esc_url($logo); ?>" alt="Kizora Skincare" class="kz-logo-sm h-9 w-auto">
      <button type="button" data-kz-menu-close aria-label="Close menu" class="text-3xl leading-none">×</button>
    </div>
    <ul class="divide-y divide-line border-y border-line font-serif text-[22px]">
      <li><a href="<?php echo esc_url(kz_url('/rituals/morning/')); ?>" class="block py-3.5">Morning ritual</a></li>
      <li><a href="<?php echo esc_url(kz_url('/rituals/evening/')); ?>" class="block py-3.5">Evening ritual</a></li>
      <li><a href="<?php echo esc_url(kz_url('/shop/')); ?>" class="block py-3.5">Shop all</a></li>
      <li><a href="<?php echo esc_url(kz_url('/ingredients/')); ?>" class="block py-3.5">Ingredients</a></li>
      <li><a href="<?php echo esc_url(kz_url('/about/')); ?>" class="block py-3.5">Our story</a></li>
      <li><a href="<?php echo esc_url(kz_url('/journal/')); ?>" class="block py-3.5">Journal</a></li>
    </ul>
    <p class="eyebrow mb-3 mt-8">Shop by type</p>
    <ul class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-[15px]">
      <?php foreach ($cols as $c) : ?><li><a href="<?php echo esc_url(kz_url("/collections/{$c['slug']}/")); ?>"><?php echo esc_html($c['name']); ?></a></li><?php endforeach; ?>
    </ul>
    <a href="https://wa.me/<?php echo esc_attr($site['whatsapp']); ?>" class="btn-ghost mt-10 w-full" target="_blank" rel="noopener noreferrer">Ask us on WhatsApp</a>
  </nav>
</div>
<main id="main">
