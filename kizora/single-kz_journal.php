<?php
/** Journal article: content imported from the prototype (edit in Journal → All articles). */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post(); the_content(); endwhile;
get_footer();
