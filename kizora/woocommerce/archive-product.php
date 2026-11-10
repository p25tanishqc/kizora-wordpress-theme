<?php
/**
 * Shop and collections (prototype: app/shop/page.tsx + components/shop-browser.tsx,
 * app/collections/[slug]/page.tsx). Collections are WooCommerce product categories.
 */
defined('ABSPATH') || exit;
get_header();

if (is_product_category()) :
    $term = get_queried_object();
    $c = kz_collection($term->slug) ?: ['slug' => $term->slug, 'name' => $term->name, 'tone' => 'bg-paper', 'intro' => wp_strip_all_tags(term_description($term)), 'hero' => ''];
    $order = array_flip(array_column(kz_data('products'), 'slug'));
    $slugs = [];
    while (have_posts()) { the_post(); $slugs[] = kz_slug_of(get_the_ID()); }
    usort($slugs, fn($a, $b) => ($order[$a] ?? 999) <=> ($order[$b] ?? 999));
    $n = count($slugs);
    $products_only = array_values(array_filter($slugs, fn($s) => !kz_is_kit($s)));
    ?>
    <section class="<?php echo esc_attr($c['tone'] ?? 'bg-paper'); ?> border-b border-line">
      <div class="wrap grid items-center gap-8 pb-0 pt-8 md:grid-cols-[1fr_1.25fr] md:pb-0">
        <div class="pb-6 md:pb-14">
          <?php echo kz_crumbs([['name' => 'Shop', 'href' => '/shop/'], ['name' => $c['name']]]); ?>
          <p class="eyebrow mt-8"><?php echo $n . ' ' . ($n === 1 ? 'product' : 'products'); ?></p>
          <h1 class="mt-3 text-[46px] md:text-[60px]"><?php echo esc_html($c['name']); ?></h1>
          <p class="lead mt-5 max-w-md"><?php echo esc_html($c['intro'] ?? ''); ?></p>
        </div>
        <?php if (!empty($c['hero'])) : ?>
          <img src="<?php echo esc_url(KZ_URI . '/assets' . $c['hero']); ?>" alt="<?php echo esc_attr($c['name'] . ': aloe vera leaves'); ?>" width="1800" height="1500" fetchpriority="high" class="mb-8 h-auto w-full rounded-[3px] md:my-10">
        <?php elseif ($slugs) : ?>
          <?php echo kz_mosaic(array_slice($products_only ?: array_slice($slugs, 0, 1), 0, 3), 'main', null, 'mb-8 overflow-hidden rounded-[3px] md:my-10', true); ?>
        <?php endif; ?>
      </div>
    </section>
    <section class="wrap py-14">
      <?php echo kz_grid($slugs, 'md:grid-cols-3'); ?>
      <?php if ($term->slug === 'face-gels') :
          $g = ['pure-aloe-vera-gel', 'aloe-vera-gel-with-vitamin-e', 'aloe-vera-gel-with-rose-oil'];
          $rows = [
              ['Best for', 'Everyday hydration, all skin types', 'Skin that feels a little drier', 'If you like a soft rose scent'],
              ["What's in it", '99% aloe leaf gel + preservative', '+ vitamin E', '+ rose essential oil'],
              array_merge(['Size · price'], array_map(fn($s) => (kz_item($s)['size'] ?? '') . ' · ' . kz_price($s), $g)),
              array_merge(['Use'], array_map(fn($s) => kz_item($s)['when'] ?? '', $g)),
          ]; ?>
        <div class="mt-20">
          <h2 class="text-[30px]">Which aloe gel is for you?</h2>
          <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[620px] border-collapse bg-paper text-left text-[14.5px]">
              <thead><tr class="bg-[#F1EADD] text-[12px] uppercase tracking-[0.12em] text-sage-deep">
                <th class="p-3.5"></th>
                <th class="p-3.5"><a href="<?php echo esc_url(kz_product_url($g[0])); ?>" class="link">Pure Aloe Vera Gel</a></th>
                <th class="p-3.5"><a href="<?php echo esc_url(kz_product_url($g[1])); ?>" class="link">With Vitamin E</a></th>
                <th class="p-3.5"><a href="<?php echo esc_url(kz_product_url($g[2])); ?>" class="link">With Rose Oil</a></th></tr></thead>
              <tbody>
                <?php foreach ($rows as $r) : ?><tr class="border-t border-line"><?php foreach ($r as $i => $cell) : ?><td class="p-3.5 align-top <?php echo $i === 0 ? 'font-medium' : ''; ?>"><?php echo esc_html($cell); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="mt-6 rounded-[3px] bg-butter/50 p-5 text-[15px]">Pair any gel with Rose Hydrating Mist for the <a href="<?php echo esc_url(kz_product_url('morning-ritual-kit')); ?>" class="link">Morning Ritual Kit (<?php echo esc_html(kz_price('morning-ritual-kit')); ?>)</a>. Looking for lips or heels? See <a href="<?php echo esc_url(kz_url('/collections/lip-care/')); ?>" class="link">Lip care</a> and <a href="<?php echo esc_url(kz_url('/collections/hands-feet/')); ?>" class="link">Hands &amp; feet</a>.</p>
        </div>
      <?php endif; ?>
    </section>
    <?php
    echo kz_json_ld(['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $c['name'], 'description' => $c['description'] ?? '',
        'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => array_map(fn($s, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => kz_product_url($s), 'name' => kz_name($s)], $slugs, array_keys($slugs))]]);

else : /* ---------------------------------------------------------------- shop */
    $items = kz_items();
    $needs = kz_data('needs');
    $cols = array_values(array_filter(kz_data('collections'), fn($c) => $c['slug'] !== 'aloe-vera-essentials'));
    $n_products = count(array_filter($items, fn($p) => ($p['kind'] ?? '') === 'product'));
    $n_kits = count($items) - $n_products;
    $ing_names = [];
    foreach (kz_data('ingredients') as $i) $ing_names[$i['slug']] = $i['name'];
    $chip = fn($attr, $val, $label, $pressed = false, $extra = '') => '<button type="button" class="chip' . $extra . '" aria-pressed="' . ($pressed ? 'true' : 'false') . '" data-' . $attr . '="' . esc_attr($val) . '">' . esc_html($label) . '</button>';
    ob_start(); ?>
    <div class="space-y-8 text-[14.5px]">
      <fieldset><legend class="eyebrow mb-3">Type</legend><div class="flex flex-wrap gap-2">
        <?php echo $chip('col', 'all', 'All', true); foreach ($cols as $c) echo $chip('col', $c['slug'], $c['name']); ?>
      </div></fieldset>
      <fieldset><legend class="eyebrow mb-3">Need</legend><div class="flex flex-wrap gap-2">
        <?php foreach ($needs as $k => $label) echo $chip('need', $k, $label); ?>
      </div></fieldset>
      <fieldset><legend class="eyebrow mb-3">Skin type</legend><div class="flex flex-wrap gap-2">
        <?php foreach (['dry', 'oily', 'normal', 'combination', 'sensitive'] as $s) echo $chip('skin', $s, $s, false, ' capitalize'); ?>
      </div></fieldset>
      <fieldset><legend class="eyebrow mb-3">Price</legend><div class="flex flex-wrap gap-2">
        <?php echo $chip('price', 'u400', 'Under ₹400') . $chip('price', '400-800', '₹400 to ₹800') . $chip('price', 'o800', 'Kits above ₹800'); ?>
      </div></fieldset>
      <button type="button" class="link hidden text-[14px]" data-kz-reset>Clear all filters</button>
    </div>
    <?php $filters = ob_get_clean(); ?>
    <div class="wrap pb-10 pt-8">
      <?php echo kz_crumbs([['name' => 'Shop all']]); ?>
      <div class="grid gap-6 border-b border-line pb-10 pt-8 md:grid-cols-2 md:items-end">
        <div><p class="eyebrow"><?php echo (int) $n_products; ?> products · <?php echo (int) $n_kits; ?> kits</p><h1 class="mt-3 text-[44px] md:text-[56px]">Shop all</h1></div>
        <p class="lead">Every product we make, with its size and price. Filter by what your skin needs, or search for an ingredient you know, like haldi, mulethi or moringa.</p>
      </div>
      <div class="pt-10" data-kz-shop-browser>
        <div class="grid gap-10 lg:grid-cols-[260px_1fr]">
          <aside class="hidden lg:block" data-kz-filters><?php echo $filters; ?></aside>
          <div>
            <div class="mb-8 flex flex-wrap items-center gap-3 border-b border-line pb-5">
              <label for="shop-q" class="sr-only">Search products</label>
              <input id="shop-q" value="<?php echo esc_attr(wp_unslash($_GET['q'] ?? '')); ?>" placeholder="Search by product or ingredient" class="min-w-[200px] flex-1 border-b border-ink/40 bg-transparent py-2 outline-none focus:border-ink" data-kz-q>
              <button type="button" class="btn-ghost py-2.5 lg:hidden" aria-expanded="false" data-kz-filters-toggle>Filters</button>
              <label class="flex items-center gap-2 text-[14px] text-ink2">Sort
                <select class="border border-line bg-paper px-2 py-2 text-ink" data-kz-sort>
                  <option value="recommended">Recommended</option><option value="low">Price: low to high</option><option value="high">Price: high to low</option>
                </select>
              </label>
            </div>
            <div class="mb-10 hidden lg:hidden" data-kz-filters-mobile><?php echo $filters; ?></div>
            <p class="mb-6 text-[14px] text-ink2" aria-live="polite" data-kz-count><?php echo count($items); ?> items</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-6" data-kz-results>
              <?php foreach ($items as $i => $p) :
                  $ing = implode(' ', array_map(fn($s) => $ing_names[$s] ?? '', $p['ingredientSlugs'] ?? []));
                  echo kz_card($p['slug'], null, $i < 3, [
                      'data-order' => $i, 'data-price' => kz_price_num($p['slug']),
                      'data-cols' => implode(' ', $p['collections'] ?? []), 'data-needs' => implode(' ', $p['needs'] ?? []), 'data-skin' => implode(' ', $p['skin'] ?? []),
                      'data-name' => strtolower($p['name'] ?? ''), 'data-key' => strtolower(implode(' ', $p['keyIngredients'] ?? [])), 'data-ing' => strtolower($ing),
                      'data-rest' => strtolower(($p['oneLine'] ?? '') . ' ' . ($p['fullIngredients'] ?? '') . ' ' . ($p['forWhom'] ?? '')),
                  ]);
              endforeach; ?>
            </div>
            <div class="hidden rounded-[3px] bg-paper p-10 text-center" data-kz-empty>
              <p class="font-serif text-2xl">Nothing matches that yet.</p>
              <p class="mt-2 text-ink2">Try “aloe”, “rose”, “soap” or “heels”, or <button type="button" class="link" data-kz-reset>clear the filters</button>.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php endif;
get_footer();
