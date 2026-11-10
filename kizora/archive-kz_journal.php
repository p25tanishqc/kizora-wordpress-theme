<?php
/** Journal listing (prototype: app/journal/page.tsx). */
defined('ABSPATH') || exit;
get_header();
$posts = get_posts(['post_type' => 'kz_journal', 'numberposts' => -1, 'orderby' => 'menu_order date', 'order' => 'ASC']);
$photos = function ($p) {
    $s = get_post_meta($p->ID, '_kz_photos', true);
    return is_array($s) ? $s : [];
};
?>
<div class="wrap pb-10 pt-8">
  <?php echo kz_crumbs([['name' => 'Journal']]); ?>
  <h1 class="mt-10 text-[46px] md:text-[60px]">Journal</h1>
  <p class="lead mt-3 max-w-xl">Notes on how things are made and how to use them.</p>
  <?php if (!$posts) : ?>
    <p class="mt-12 rounded-[3px] bg-paper p-10 text-ink2">New articles are on their way.</p>
  <?php else : $first = array_shift($posts); ?>
    <a href="<?php echo esc_url(get_permalink($first)); ?>" class="group mt-12 grid gap-8 border-y border-line py-10 md:grid-cols-2 md:items-center">
      <?php echo kz_mosaic($photos($first), 'card', null, 'overflow-hidden rounded-[3px]'); ?>
      <div><p class="eyebrow"><?php echo esc_html(get_post_meta($first->ID, '_kz_read', true)); ?> read</p><h2 class="mt-3 text-[34px] group-hover:underline group-hover:decoration-gold group-hover:underline-offset-4 md:text-[42px]"><?php echo esc_html(get_the_title($first)); ?></h2><p class="lead mt-4"><?php echo esc_html(get_the_excerpt($first)); ?></p></div>
    </a>
    <div class="grid gap-10 py-12 md:grid-cols-2">
      <?php foreach ($posts as $a) : ?>
        <a href="<?php echo esc_url(get_permalink($a)); ?>" class="group">
          <?php echo kz_mosaic($photos($a), 'card', null, 'overflow-hidden rounded-[3px]'); ?>
          <p class="eyebrow mt-5"><?php echo esc_html(get_post_meta($a->ID, '_kz_read', true)); ?> read</p>
          <h2 class="mt-2 text-[26px] group-hover:underline group-hover:decoration-gold group-hover:underline-offset-4"><?php echo esc_html(get_the_title($a)); ?></h2>
          <p class="mt-2 text-ink2"><?php echo esc_html(get_the_excerpt($a)); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php get_footer();
