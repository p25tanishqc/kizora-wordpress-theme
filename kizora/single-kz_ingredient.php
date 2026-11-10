<?php
/** Ingredient profile: content imported from the prototype's /ingredients/<slug>/ page. */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post(); the_content(); endwhile;
get_footer();
