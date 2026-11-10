<?php
/** Site footer, cart drawer and WhatsApp button (prototype: chrome.tsx Footer, cart.tsx CartDrawer). */
defined('ABSPATH') || exit;
$site = kz_data('site');
$col = function (string $title, array $links) {
    $li = '';
    foreach ($links as [$t, $h]) $li .= '<li><a href="' . esc_url(kz_url($h)) . '" class="hover:text-cream">' . esc_html($t) . '</a></li>';
    return '<div><h2 class="mb-4 font-sans text-[12px] font-medium uppercase tracking-eyebrow text-gold">' . esc_html($title) . '</h2><ul class="space-y-2.5">' . $li . '</ul></div>';
};
?>
</main>
<footer class="mt-24 bg-ink text-[14.5px] text-cream/80">
  <div class="wrap grid gap-12 py-16 md:grid-cols-[1.5fr_1fr_1fr_1.2fr]">
    <div>
      <img src="<?php echo esc_url(KZ_URI . '/assets/images/logo.webp'); ?>" alt="Kizora Skincare" class="kz-logo-f h-12 w-auto" loading="lazy">
      <p class="mt-5 max-w-[300px] leading-relaxed">Small-batch skincare made near Kanha, Madhya Pradesh. Simple daily rituals with ingredients you can name.</p>
      <p class="mt-6 font-serif text-xl italic text-cream">Care, when done consistently, becomes ritual.</p>
    </div>
    <?php
    echo $col('Shop', [['Face gels', '/collections/face-gels/'], ['Face mists', '/collections/face-mists/'], ['Oils', '/collections/oils/'], ['Soaps & bath', '/collections/bath-body/'], ['Lip care', '/collections/lip-care/'], ['Hands & feet', '/collections/hands-feet/'], ['Hair', '/collections/hair/'], ['Sets & kits', '/collections/sets-hampers/']]);
    echo $col('Learn', [['Morning ritual', '/rituals/morning/'], ['Evening ritual', '/rituals/evening/'], ['Ingredient library', '/ingredients/'], ["How it's made", '/how-its-made/'], ['Journal', '/journal/'], ['FAQ', '/faq/']]);
    ?>
    <div>
      <?php echo $col('Help', [['Contact us', '/contact/'], ['Shipping', '/shipping/'], ['Returns', '/returns/'], ['Your cart', '/cart/'], ['My account', '/my-account/']]); ?>
      <p class="mt-6">WhatsApp <a class="link text-cream" href="https://wa.me/<?php echo esc_attr($site['whatsapp']); ?>"><?php echo esc_html($site['phone']); ?></a><br><a class="link text-cream" href="mailto:<?php echo esc_attr($site['email']); ?>"><?php echo esc_html($site['email']); ?></a></p>
    </div>
  </div>
  <div class="border-t border-cream/15">
    <div class="wrap flex flex-col gap-2 py-6 text-[12.5px] text-cream/55 md:flex-row md:justify-between">
      <span>© <?php echo esc_html(wp_date('Y')); ?> Kizora Skincare, a brand of <?php echo esc_html($site['legal']); ?>. Information on this site is general guidance, not medical advice.</span>
      <span class="flex gap-5"><a href="https://www.instagram.com/kizora.skincare/" target="_blank" rel="noopener noreferrer">Instagram</a><a href="https://www.facebook.com/kizora.life" target="_blank" rel="noopener noreferrer">Facebook</a></span>
    </div>
  </div>
</footer>

<?php if (function_exists('kz_drawer_body')) : ?>
<div class="pointer-events-none fixed inset-0 z-[60]" aria-hidden="true" inert data-kz-cart>
  <div class="absolute inset-0 bg-ink/30 opacity-0 transition-opacity" data-kz-close-cart></div>
  <aside role="dialog" aria-label="Cart" class="absolute right-0 top-0 flex h-full w-full max-w-[420px] translate-x-full flex-col bg-cream shadow-xl transition-transform duration-300" data-kz-cart-panel>
    <?php echo kz_drawer_body(); ?>
  </aside>
</div>
<?php endif; ?>

<a href="https://wa.me/<?php echo esc_attr($site['whatsapp']); ?>?text=<?php echo rawurlencode('Hi Kizora, I have a question about'); ?>" target="_blank" rel="noopener noreferrer" aria-label="Ask us on WhatsApp" class="fixed bottom-4 right-4 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-sage-deep text-cream shadow-lg transition hover:bg-ink md:bottom-6 md:right-6">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3a.4.4 0 0 0 0-.4l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>
</a>
<?php
echo kz_json_ld([
    '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $site['name'], 'legalName' => $site['legal'], 'url' => home_url('/'),
    'logo' => KZ_URI . '/assets/images/logo.webp', 'email' => $site['email'], 'telephone' => $site['phone'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'WW-24, Malibu Town', 'addressLocality' => 'Gurugram', 'addressRegion' => 'Haryana', 'addressCountry' => 'IN'],
    'sameAs' => ['https://www.instagram.com/kizora.skincare/', 'https://www.facebook.com/kizora.life'],
]);
wp_footer();
?>
</body>
</html>
