<?php
/** Fallback listing (blog posts, search results, anything without a dedicated template). */
defined('ABSPATH') || exit;
get_header(); ?>
<section class="wrap pb-10 pt-8">
  <?php echo kz_crumbs([['name' => is_search() ? 'Search' : wp_strip_all_tags(get_the_archive_title() ?: 'Posts')]]); ?>
  <h1 class="mt-10 text-[46px]"><?php echo is_search() ? esc_html(sprintf('Results for “%s”', get_search_query())) : (is_singular() ? esc_html(get_the_title()) : esc_html(wp_strip_all_tags(get_the_archive_title() ?: 'Posts'))); ?></h1>
  <?php if (is_singular()) : while (have_posts()) : the_post(); ?>
    <div class="prose-k mt-10 max-w-[760px]"><?php the_content(); ?></div>
  <?php endwhile; elseif (have_posts()) : ?>
    <ul class="mt-10 divide-y divide-line border-y border-line">
      <?php while (have_posts()) : the_post(); ?>
        <li class="py-6"><a href="<?php the_permalink(); ?>" class="font-serif text-[24px] hover:underline"><?php the_title(); ?></a><p class="mt-1 text-ink2"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></p></li>
      <?php endwhile; ?>
    </ul>
    <div class="mt-8"><?php the_posts_pagination(); ?></div>
  <?php else : ?>
    <p class="lead mt-6">Nothing here yet. <a class="link" href="<?php echo esc_url(kz_url('/shop/')); ?>">Shop all products</a>.</p>
  <?php endif; ?>
</section>
<?php get_footer();
