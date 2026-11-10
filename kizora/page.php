<?php
/**
 * Pages. Converted pages (flag _kz_converted) print their content exactly as imported, because the
 * content already carries the prototype's full layout. Other pages get a simple branded wrapper.
 */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post();
    $id = get_the_ID();
    if (function_exists('is_cart') && is_cart()) {
        get_template_part('template-parts/view', 'cart');
    } elseif (get_post_meta($id, '_kz_converted', true)) {
        the_content();
    } else {
        $woo = function_exists('is_checkout') && (is_checkout() || is_account_page());
        ?>
        <section class="wrap pb-10 pt-8 <?php echo $woo ? 'kz-woo' : ''; ?>">
          <?php echo kz_crumbs([['name' => get_the_title()]]); ?>
          <h1 class="mb-10 mt-10 text-[46px]"><?php the_title(); ?></h1>
          <div class="<?php echo $woo ? '' : 'prose-k max-w-[760px]'; ?>"><?php the_content(); ?></div>
        </section>
        <?php
    }
endwhile;
get_footer();
