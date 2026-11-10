<?php
/** Ingredient library (prototype: app/ingredients/page.tsx + components/ingredient-browser.tsx). */
defined('ABSPATH') || exit;
get_header();
$groups = ['All', 'Hydrating', 'Plant waters', 'Oils & butters', 'Traditional Indian'];
$posts = get_posts(['post_type' => 'kz_ingredient', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']);
$items = [];
foreach ($posts as $p) {
    $slug = $p->post_name;
    $d = kz_ingredient($slug) ?: [];
    $items[] = ['url' => get_permalink($p), 'name' => get_the_title($p), 'group' => $d['group'] ?? '', 'local' => $d['localName'] ?? '',
        'what' => $d['what'] ?? get_the_excerpt($p), 'listed' => $d['listedAs'] ?? '', 'products' => $d['products'] ?? []];
}
?>
<section class="bg-sage-mist">
  <div class="wrap pb-14 pt-8">
    <?php echo kz_crumbs([['name' => 'Ingredient library']]); ?>
    <div class="grid gap-8 pt-10 md:grid-cols-[1.2fr_1fr] md:items-end">
      <div><p class="eyebrow">Ingredient library · <?php echo count($items); ?> profiles</p><h1 class="mt-3 text-[46px] md:text-[64px]">Know your ingredients</h1></div>
      <p class="lead">What each ingredient is, how it has been used in Indian homes, what the research does and does not say, and which of our products contain it.</p>
    </div>
  </div>
</section>
<section class="wrap py-12" data-kz-ingredients>
  <div class="flex flex-wrap items-center gap-2 border-b border-line pb-6">
    <?php foreach ($groups as $g) : ?><button type="button" class="chip" aria-pressed="<?php echo $g === 'All' ? 'true' : 'false'; ?>" data-group="<?php echo esc_attr($g); ?>"><?php echo esc_html($g); ?></button><?php endforeach; ?>
    <label for="ing-q" class="sr-only">Search ingredients</label>
    <input id="ing-q" placeholder="Search, e.g. haldi, mulethi" class="ml-auto w-full border-b border-ink/40 bg-transparent py-2 outline-none focus:border-ink sm:w-64" data-kz-ing-q>
  </div>
  <ul class="mt-2 divide-y divide-line">
    <?php foreach ($items as $i) : ?>
      <li data-group="<?php echo esc_attr($i['group']); ?>" data-text="<?php echo esc_attr(strtolower($i['name'] . ' ' . $i['local'] . ' ' . $i['listed'] . ' ' . $i['what'])); ?>">
        <a href="<?php echo esc_url($i['url']); ?>" class="group grid gap-4 py-7 md:grid-cols-[1.2fr_2fr_auto] md:items-center md:gap-10">
          <div>
            <p class="text-[12px] uppercase tracking-[0.14em] text-sage-deep"><?php echo esc_html($i['group']); ?></p>
            <h2 class="mt-1 text-[28px] group-hover:underline group-hover:decoration-gold group-hover:underline-offset-4 md:text-[32px]"><?php echo esc_html($i['name']); ?></h2>
            <?php if ($i['local']) : ?><p class="text-[13.5px] italic text-ink2"><?php echo esc_html($i['local']); ?></p><?php endif; ?>
          </div>
          <p class="text-[15.5px] text-ink2"><?php echo esc_html($i['what']); ?></p>
          <div class="flex items-center gap-2">
            <?php foreach (array_slice($i['products'], 0, 4) as $s) : ?><img src="<?php echo esc_url(kz_img($s, 'card')); ?>" alt="<?php echo esc_attr(kz_name($s)); ?>" title="<?php echo esc_attr(kz_name($s)); ?>" loading="lazy" class="h-16 w-[52px] rounded-sm object-cover"><?php endforeach; ?>
            <?php if (count($i['products']) > 4) : ?><span class="text-[13px] text-ink2">+<?php echo count($i['products']) - 4; ?></span><?php endif; ?>
          </div>
        </a>
      </li>
    <?php endforeach; ?>
    <li class="hidden py-12 text-center text-ink2" data-kz-ing-empty>No ingredient matches that search.</li>
  </ul>
  <div id="all" class="mt-20 scroll-mt-28">
    <h2 class="text-[28px]">Every other ingredient, and where to find it</h2>
    <p class="mt-2 text-ink2">Taken from the ingredients published on each product page.</p>
    <dl class="mt-6 grid gap-x-10 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach (kz_data('other-ingredients') as $o) : ?>
        <div class="border-b border-line py-3">
          <dt class="font-medium"><?php echo esc_html($o['name']); ?></dt>
          <dd class="text-[14px] text-ink2"><?php echo implode(', ', array_map(fn($s) => '<a href="' . esc_url(kz_product_url($s)) . '" class="hover:text-ink hover:underline">' . esc_html(kz_name($s)) . '</a>', $o['products'])); ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>
<?php get_footer();
