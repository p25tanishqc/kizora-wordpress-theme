<?php
/**
 * Morning / evening ritual (prototype: components/ritual-view.tsx).
 * Loaded through view-ritual-morning.php / view-ritual-evening.php, which set $which.
 */
defined('ABSPATH') || exit;
$which = $which ?? 'morning';
$r = kz_data('rituals')[$which];
$kit = kz_item($r['kit']);
$dark = $which === 'evening';
$other = $which === 'morning' ? 'evening' : 'morning';
$alt = 'morning-ritual-kit-oily-skin';
$full = 'complete-evening-ritual';
$in_kit = fn($s) => $s && in_array($s, $kit['components'], true);
get_header();
?>
<section class="<?php echo $dark ? 'bg-dusk text-cream' : 'bg-butter/50'; ?>">
  <div class="wrap grid items-center gap-8 pt-6 md:grid-cols-[1fr_1.2fr] md:gap-12">
    <div class="pb-4 md:pb-16">
      <div class="<?php echo $dark ? '[&_*]:!text-cream/80' : ''; ?>"><?php echo kz_crumbs([['name' => 'Rituals', 'href' => '/rituals/'], ['name' => ucfirst($which)]]); ?></div>
      <p class="eyebrow mt-8 <?php echo $dark ? '!text-gold' : ''; ?>"><?php echo esc_html($r['eyebrow']); ?></p>
      <h1 class="mt-3 text-[46px] md:text-[62px] <?php echo $dark ? '!text-cream' : ''; ?>"><?php echo esc_html($r['title']); ?></h1>
      <p class="mt-5 max-w-md text-[17px] leading-relaxed <?php echo $dark ? 'text-cream/80' : 'text-ink2'; ?>"><?php echo esc_html($r['intro']); ?></p>
      <?php if ($dark) : ?>
        <div class="mt-8 flex flex-wrap gap-3">
          <a href="#kit" class="btn-gold">Evening essentials · 2 products · <?php echo esc_html(kz_price($r['kit'])); ?></a>
          <a href="#kit" class="inline-flex items-center justify-center rounded-[2px] border border-cream/60 px-6 py-3.5 text-[13px] font-medium uppercase tracking-[0.12em] text-cream hover:bg-cream hover:text-ink">Complete ritual · 5 products · <?php echo esc_html(kz_price($full)); ?></a>
        </div>
      <?php else : ?>
        <div class="mt-8">
          <div class="flex flex-wrap gap-3">
            <a href="#kit" class="btn">Add the kit · <?php echo esc_html(kz_price($r['kit'])); ?></a>
            <a href="<?php echo esc_url(kz_url("/rituals/{$other}/")); ?>" class="btn-ghost">See the <?php echo esc_html($other); ?> ritual</a>
          </div>
          <p class="mt-3 text-[14px] text-ink2">Includes mist + gel. The lip balm is optional.</p>
        </div>
      <?php endif; ?>
    </div>
    <?php echo kz_mosaic(array_slice(array_values(array_filter(array_column($r['steps'], 'slug'))), 0, 3), 'main', null, 'mb-6 overflow-hidden rounded-[3px] md:my-12', true); ?>
  </div>
</section>

<section class="wrap grid gap-14 py-16 lg:grid-cols-[1.7fr_1fr] lg:gap-16">
  <div>
    <h2 class="text-[32px]">The steps, in order</h2>
    <ol class="mt-4">
      <?php foreach ($r['steps'] as $s) :
          $slug = $s['slug'] ?? '';
          $p = $slug ? kz_item($slug) : null;
          if (!$p) : ?>
            <li class="my-6 rounded-[3px] bg-blush/50 p-6"><p class="eyebrow"><?php echo esc_html($s['n'] . ' · ' . $s['role']); ?></p><p class="mt-2 font-serif text-[22px]"><?php echo esc_html($s['title']); ?></p><p class="mt-1 text-ink2"><?php echo esc_html($s['how'] . ' ' . $s['why']); ?></p></li>
          <?php continue; endif; ?>
          <li class="grid grid-cols-[34px_96px_1fr] items-start gap-4 border-b border-line py-7 sm:grid-cols-[48px_150px_1fr_auto] sm:gap-6">
            <span class="font-serif text-[34px] italic leading-none text-gold sm:text-[44px]"><?php echo esc_html($s['n']); ?></span>
            <a href="<?php echo esc_url(kz_product_url($slug)); ?>" class="block aspect-[4/5] overflow-hidden rounded-[3px] bg-paper"><img src="<?php echo esc_url(kz_img($slug, 'card')); ?>" alt="<?php echo esc_attr(kz_name($slug)); ?>" loading="lazy" class="h-full w-full object-cover"></a>
            <div class="col-span-3 sm:col-span-1 sm:col-start-3">
              <p class="eyebrow"><?php echo esc_html($s['role']); ?><?php echo $in_kit($slug)
                  ? '<span class="ml-2 rounded-sm bg-butter px-1.5 py-0.5 text-[10.5px] text-ink">' . ($which === 'morning' ? 'In the kit' : 'In evening essentials') . '</span>'
                  : '<span class="text-ink2"> · ' . ($which === 'morning' ? 'optional, not in the kit' : 'in the complete ritual') . '</span>'; ?></p>
              <h3 class="mt-1.5 text-[24px]"><a href="<?php echo esc_url(kz_product_url($slug)); ?>" class="hover:underline hover:decoration-gold hover:underline-offset-4"><?php echo esc_html(kz_name($slug)); ?></a></h3>
              <p class="mt-2"><?php echo esc_html($s['how']); ?></p>
              <p class="mt-1.5 text-[14.5px] text-ink2"><?php echo esc_html($s['why']); ?></p>
            </div>
            <div class="col-span-3 flex items-center justify-between gap-4 sm:col-span-1 sm:block sm:text-right">
              <p class="font-medium sm:mb-3"><?php echo esc_html(kz_price($slug)); ?></p><?php echo kz_add_button($slug, 'Add', 'btn-ghost px-4 py-2 text-[12px]', 1, 'Add ' . kz_name($slug) . ' to cart'); ?>
            </div>
          </li>
      <?php endforeach; ?>
    </ol>
    <p class="mt-8 rounded-[3px] bg-paper p-5 text-[15px] text-ink2"><?php echo esc_html($r['gaps']); ?></p>
    <h3 class="mt-12 text-[26px]">Make it yours</h3>
    <ul class="mt-4 divide-y divide-line border-y border-line">
      <?php foreach ($r['swaps'] as $sw) : ?>
        <li class="flex flex-wrap items-baseline justify-between gap-2 py-4">
          <span><b class="font-medium"><?php echo esc_html($sw['label']); ?>.</b> <span class="text-ink2"><?php echo esc_html($sw['text']); ?></span></span>
          <a href="<?php echo esc_url(kz_product_url($sw['slug'])); ?>" class="link text-[14px]"><?php echo esc_html(kz_name($sw['slug']) . ' · ' . kz_price($sw['slug'])); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <aside id="kit" class="scroll-mt-28 lg:sticky lg:top-28 lg:self-start">
    <div class="overflow-hidden rounded-[3px] bg-ink text-cream">
      <?php echo kz_mosaic($kit['components'], 'card'); ?>
      <div class="p-7">
        <p class="eyebrow !text-gold"><?php echo $which === 'morning' ? 'The kit · 2 essentials' : 'Option 1 · 2 products'; ?></p>
        <h2 class="mt-2 text-[28px] !text-cream"><?php echo esc_html($kit['name']); ?></h2>
        <ul class="mt-4 divide-y divide-cream/15 text-[15px]">
          <?php foreach ($kit['components'] as $s) : ?><li class="flex justify-between py-2.5"><span><?php echo esc_html(kz_name($s)); ?></span><span><?php echo esc_html(kz_price($s)); ?></span></li><?php endforeach; ?>
        </ul>
        <div class="my-5 flex justify-between font-serif text-[24px]"><span>Total</span><span><?php echo esc_html(kz_price($r['kit'])); ?></span></div>
        <?php echo kz_add_button($r['kit'], 'Add the kit · ' . kz_price($r['kit']), 'btn-gold w-full'); ?>
        <p class="mt-4 text-[13px] text-cream/70"><?php echo $which === 'morning' ? 'Add a lip balm separately if you like. ' : ''; ?><?php echo kz_price_num($r['kit']) >= kz_free_delivery() ? 'Qualifies for free delivery on orders above ' . esc_html(kz_inr(kz_free_delivery())) . '.' : ''; ?></p>
        <?php if ($which === 'evening' && ($f = kz_item($full))) : ?>
          <div class="mt-7 border-t border-cream/20 pt-6">
            <p class="eyebrow !text-gold">Option 2 · 5 products</p>
            <h3 class="mt-2 text-[22px] !text-cream"><?php echo esc_html($f['name']); ?></h3>
            <p class="mt-2 text-[14px] text-cream/75">Every step above: <?php echo esc_html(implode(', ', array_map('kz_name', $f['components']))); ?>.</p>
            <div class="my-4 flex justify-between font-serif text-[20px]"><span>Total</span><span><?php echo esc_html(kz_price($full)); ?></span></div>
            <?php echo kz_add_button($full, 'Add all 5 · ' . kz_price($full), 'btn-gold w-full'); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($which === 'morning' && kz_item($alt)) : ?>
      <a href="<?php echo esc_url(kz_product_url($alt)); ?>" class="mt-4 flex items-center justify-between rounded-[3px] border border-line bg-paper p-5 text-[15px] hover:border-ink">
        <span><span class="block text-[12px] uppercase tracking-[0.12em] text-ink2">Oily skin?</span><?php echo esc_html(kz_name($alt)); ?></span><span><?php echo esc_html(kz_price($alt)); ?> →</span>
      </a>
    <?php endif; ?>
  </aside>
</section>
<?php
echo kz_json_ld(['@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => $r['title'],
    'step' => array_map(fn($s) => ['@type' => 'HowToStep', 'name' => "{$s['role']}: {$s['title']}", 'text' => $s['how']], $r['steps'])]);
get_footer();
