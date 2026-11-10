<?php
/** Product page (prototype: app/products/[slug]/page.tsx + components/product-bits.tsx). */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post();
    global $product;
    $slug = kz_slug_of(get_the_ID());
    $p = kz_item($slug);
    $kit = kz_is_kit($slug);
    $col = !empty($p['collections']) ? kz_collection($p['collections'][0]) : null;
    if (!$col && !empty($p['collections'])) {
        $t = get_term_by('slug', $p['collections'][0], 'product_cat');
        if ($t) $col = ['slug' => $t->slug, 'name' => $t->name];
    }
    $price = kz_price_num($slug);
    $free = kz_free_delivery();
    $images = $kit
        ? array_map(fn($s) => ['src' => kz_img($s, 'main'), 'alt' => kz_name($s), 'label' => kz_name($s)], $p['components'])
        : array_map(fn($src, $i) => ['src' => $src, 'alt' => $i === 0 ? $p['name'] : "{$p['name']}, photo " . ($i + 1), 'label' => 'Photo ' . ($i + 1)], kz_photos($slug), array_keys(kz_photos($slug)));
    $ings = array_values(array_filter(array_map('kz_ingredient', $p['ingredientSlugs'] ?? [])));
    $ritual = !empty($p['ritual']) ? ' · ' . implode(' & ', array_map('ucfirst', $p['ritual'])) . ' ritual' : '';
    $reviews = $product ? (int) $product->get_review_count() : 0;
    $facts = array_filter([['For', $p['forWhom'] ?? ''], ['When', $p['when'] ?? ''], ['Key ingredients', implode(', ', $p['keyIngredients'] ?? [])], ['Made', 'Small batch, near Kanha, MP']], fn($f) => $f[1] !== '');
    $section = fn($title, $body, $open = true) => '<details' . ($open ? ' open' : '') . ' class="border-b border-line py-5"><summary class="flex justify-between font-serif text-[20px]">' . $title . '<span class="chev transition-transform">+</span></summary>' . $body . '</details>';
    ?>
    <div class="wrap pt-6"><?php echo kz_crumbs([['name' => $col['name'] ?? 'Shop', 'href' => $col ? "/collections/{$col['slug']}/" : '/shop/'], ['name' => $p['name']]]); ?></div>
    <?php if (function_exists('woocommerce_output_all_notices')) { echo '<div class="wrap kz-notices">'; woocommerce_output_all_notices(); echo '</div>'; } ?>
    <section class="wrap grid gap-10 pb-16 pt-6 md:grid-cols-[1.05fr_1fr] md:gap-14 lg:gap-20">
      <div class="md:sticky md:top-28 md:self-start" data-kz-gallery>
        <div class="relative aspect-[4/5] overflow-hidden rounded-[3px] bg-white">
          <?php foreach ($images as $k => $im) : ?>
            <img src="<?php echo esc_url($im['src']); ?>" alt="<?php echo esc_attr($im['alt']); ?>" width="1200" height="1500" <?php echo $k ? 'loading="lazy"' : 'fetchpriority="high"'; ?> class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500 <?php echo $k === 0 ? 'opacity-100' : 'opacity-0'; ?>" data-kz-slide="<?php echo (int) $k; ?>">
          <?php endforeach; ?>
        </div>
        <?php if (count($images) > 1) : ?>
          <div class="mt-3 flex flex-wrap gap-2.5" role="tablist" aria-label="Product images">
            <?php foreach ($images as $k => $im) : ?>
              <button type="button" role="tab" aria-selected="<?php echo $k === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($im['label']); ?>" data-kz-thumb="<?php echo (int) $k; ?>"
                class="h-[90px] w-[72px] overflow-hidden rounded-[2px] border bg-white <?php echo $k === 0 ? 'border-ink' : 'border-line opacity-80 hover:opacity-100'; ?>">
                <img src="<?php echo esc_url($im['src']); ?>" alt="" class="h-full w-full object-cover" loading="lazy">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <p class="eyebrow"><?php echo esc_html(($kit ? 'Ritual kit' : ($col['name'] ?? '')) . $ritual); ?></p>
        <h1 class="mt-3 text-[38px] leading-tight md:text-[48px]"><?php echo esc_html($p['name']); ?></h1>
        <p class="mt-2 text-[15px] text-ink2"><?php echo $p['size'] ? esc_html($p['size']) . ' · ' : ''; ?><a href="#reviews" class="underline decoration-line underline-offset-4"><?php echo $reviews ? esc_html(sprintf(_n('%d review', '%d reviews', $reviews), $reviews)) : 'No reviews yet'; ?></a></p>
        <p class="mt-5 font-serif text-[30px]"><?php echo esc_html(kz_inr($price)); ?> <span class="font-sans text-[13px] text-ink2">incl. of all taxes<?php echo $kit ? ' · ' . count($p['components']) . ' products at their listed prices' : ''; ?></span></p>
        <?php if (!empty($p['oneLine'])) : ?><p class="mt-5 text-[17px] leading-relaxed"><?php echo esc_html($p['oneLine']); ?></p><?php endif; ?>

        <?php if ($facts) : ?>
          <dl class="mt-7 grid grid-cols-2 gap-px overflow-hidden rounded-[3px] border border-line bg-line text-[14.5px]">
            <?php foreach ($facts as [$k, $v]) : ?><div class="bg-paper p-4"><dt class="text-[11.5px] font-medium uppercase tracking-[0.14em] text-sage-deep"><?php echo esc_html($k); ?></dt><dd class="mt-1"><?php echo esc_html($v); ?></dd></div><?php endforeach; ?>
          </dl>
        <?php endif; ?>
        <?php if (!empty($p['contains'])) : ?><p class="mt-4 rounded-[3px] bg-blush/50 px-4 py-3 text-[14px]"><b class="font-medium">Contains:</b> <?php echo esc_html(str_replace('Contains ', '', $p['contains'])); ?></p><?php endif; ?>

        <div class="mt-7">
          <?php if ($kit && ($kit_ids = kz_cart_ids($slug))) : ?>
            <form class="flex gap-3" method="get" action="<?php echo esc_url(wc_get_cart_url()); ?>" data-kz-buy data-ids="<?php echo esc_attr(implode(',', $kit_ids)); ?>" data-price="<?php echo esc_attr($price); ?>">
              <input type="hidden" name="kz_add" value="<?php echo esc_attr(implode(',', $kit_ids)); ?>">
              <input type="hidden" name="_kz" value="<?php echo esc_attr(wp_create_nonce('kz_add')); ?>">
              <input type="hidden" name="quantity" value="1" data-kz-qty-input>
              <div class="flex items-center border border-ink/60">
                <button type="button" aria-label="Decrease quantity" class="h-12 w-11 text-lg" data-kz-step="-1">−</button>
                <span class="w-8 text-center" aria-live="polite" data-kz-qty-label>1</span>
                <button type="button" aria-label="Increase quantity" class="h-12 w-11 text-lg" data-kz-step="1">+</button>
              </div>
              <button type="submit" class="btn flex-1" data-kz-buy-label>Add to cart · <?php echo esc_html(kz_inr($price)); ?></button>
            </form>
          <?php elseif ($product && $product->is_purchasable() && $product->is_in_stock()) : ?>
            <form class="flex gap-3" method="post" action="<?php echo esc_url(get_permalink()); ?>" data-kz-buy data-price="<?php echo esc_attr($price); ?>">
              <input type="hidden" name="add-to-cart" value="<?php echo (int) $product->get_id(); ?>">
              <input type="hidden" name="quantity" value="1" data-kz-qty-input>
              <div class="flex items-center border border-ink/60">
                <button type="button" aria-label="Decrease quantity" class="h-12 w-11 text-lg" data-kz-step="-1">−</button>
                <span class="w-8 text-center" aria-live="polite" data-kz-qty-label>1</span>
                <button type="button" aria-label="Increase quantity" class="h-12 w-11 text-lg" data-kz-step="1">+</button>
              </div>
              <button type="submit" class="btn flex-1" data-kz-buy-label>Add to cart · <?php echo esc_html(kz_inr($price)); ?></button>
            </form>
          <?php else : ?>
            <p class="rounded-[3px] bg-paper p-4 text-ink2">Out of stock right now. <a class="link" href="https://wa.me/<?php echo esc_attr(kz_data('site')['whatsapp']); ?>" target="_blank" rel="noopener noreferrer">Ask us on WhatsApp</a> when it is back.</p>
          <?php endif; ?>
        </div>
        <p class="mt-5 text-[13.5px] text-ink2">✓ Free delivery on orders above <?php echo esc_html(kz_inr($free)); ?><?php if ($price < $free) : ?> · <a href="<?php echo esc_url(kz_url('/collections/sets-hampers/')); ?>" class="underline">kits qualify</a><?php endif; ?></p>

        <div class="mt-9 border-t border-line">
          <?php
          $about = !empty($p['description']) ? implode('', array_map(fn($d) => '<p>' . esc_html($d) . '</p>', $p['description'])) : wp_kses_post(wpautop($product ? $product->get_description() : ''));
          echo $section('About this ' . ($kit ? 'kit' : 'product'), '<div class="mt-3 space-y-3 text-ink/90">' . $about . '</div>');
          if (!empty($p['howToUse'])) echo $section('How to use', '<p class="mt-3 text-ink/90">' . esc_html($p['howToUse']) . '</p>');
          if ($kit) {
              $li = '';
              foreach ($p['components'] as $s) {
                  $c = kz_item($s);
                  $li .= '<li class="flex items-center gap-4 py-3"><img src="' . esc_url(kz_img($s, 'card')) . '" alt="" class="h-16 w-14 rounded-sm object-cover" loading="lazy">'
                      . '<a href="' . esc_url(kz_product_url($s)) . '" class="flex-1 hover:underline">' . esc_html(kz_name($s)) . ' <span class="text-[13px] text-ink2">· ' . esc_html($c['size'] ?? '') . '</span></a><span>' . esc_html(kz_price($s)) . '</span></li>';
              }
              echo $section("What's in the kit", '<ul class="mt-3 divide-y divide-line">' . $li . '</ul>');
          } elseif (!empty($p['fullIngredients'])) {
              $learn = $ings ? '<p class="mt-3 text-[14px] text-ink2">Learn more: ' . implode(', ', array_map(fn($g) => '<a href="' . esc_url(kz_url("/ingredients/{$g['slug']}/")) . '" class="link">' . esc_html($g['name']) . '</a>', $ings)) . '</p>' : '';
              echo $section(($p['ingredientsComplete'] ?? true) === false ? 'Key ingredients' : 'Ingredients', '<p class="mt-3 text-[15px] text-ink/90">' . esc_html($p['fullIngredients']) . '</p>' . $learn, false);
          }
          echo $section("How it's made", '<p class="mt-3 text-ink/90">Made in small batches at our partner\'s unit near Kanha National Park, Madhya Pradesh, with few machines. <a href="' . esc_url(kz_url('/how-its-made/')) . '" class="link">Read more</a>.</p>', false);
          ?>
        </div>
      </div>
    </section>

    <?php if (!empty($p['faqs'])) : ?>
      <section class="wrap max-w-[900px] py-10"><h2 class="mb-6 text-[30px]">Questions</h2><?php echo kz_faq($p['faqs']); ?></section>
    <?php endif; ?>
    <?php if (!empty($p['useWith'])) : ?>
      <section class="wrap py-14"><h2 class="mb-8 text-[30px]">Use with</h2><?php echo kz_grid(array_slice($p['useWith'], 0, 4)); ?></section>
    <?php endif; ?>

    <section id="reviews" class="wrap py-10">
      <div class="rounded-[3px] border border-line bg-paper p-8 md:p-10">
        <h2 class="text-[28px]">Reviews</h2>
        <?php if ($reviews && !$kit) : ?>
          <div class="kz-reviews mt-4"><?php comments_template(); ?></div>
        <?php else : ?>
          <p class="mt-2 max-w-2xl text-ink2">No reviews yet. Questions before choosing? <a href="https://wa.me/<?php echo esc_attr(kz_data('site')['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="link">Speak with our team on WhatsApp</a>.</p>
          <?php if (!$kit && comments_open()) : ?><div class="kz-reviews mt-6"><?php comments_template(); ?></div><?php endif; ?>
        <?php endif; ?>
      </div>
    </section>

    <div class="fixed inset-x-0 bottom-0 z-40 translate-y-full border-t border-line bg-cream/95 p-3 pr-20 backdrop-blur transition-transform md:hidden" data-kz-sticky-buy>
      <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1"><p class="truncate font-serif text-[16px]"><?php echo esc_html($p['name']); ?></p><p class="text-[13px] text-ink2"><?php echo esc_html(kz_inr($price)); ?></p></div>
        <?php echo kz_add_button($slug, 'Add to cart', 'btn px-5'); ?>
      </div>
    </div>
    <?php
    $ld = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'sku' => ($product ? $product->get_sku() : '') ?: ($p['sku'] ?? $slug),
        'image' => array_column($images, 'src'), 'description' => trim(($p['oneLine'] ?? '') . ' ' . implode(' ', $p['description'] ?? [])),
        'brand' => ['@type' => 'Brand', 'name' => 'Kizora Skincare'],
        'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'INR', 'url' => get_permalink(),
            'availability' => ($product && $product->is_in_stock()) || $kit ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']];
    if ($product && $reviews && $product->get_average_rating()) {
        $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $product->get_average_rating(), 'reviewCount' => $reviews];
    }
    echo kz_json_ld($ld);
endwhile;
get_footer();
