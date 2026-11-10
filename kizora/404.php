<?php
/** Not found (prototype: app/not-found.tsx). */
defined('ABSPATH') || exit;
get_header(); ?>
<section class="wrap py-28 text-center">
  <p class="eyebrow">Page not found</p>
  <h1 class="mt-4 text-[44px]">This page has moved, or never existed.</h1>
  <p class="lead mx-auto mt-4 max-w-lg">Try the shop, or start with a ritual.</p>
  <div class="mt-8 flex justify-center gap-3"><a href="<?php echo esc_url(kz_url('/shop/')); ?>" class="btn">Shop all</a><a href="<?php echo esc_url(kz_url('/rituals/')); ?>" class="btn-ghost">Rituals</a></div>
</section>
<?php get_footer();
