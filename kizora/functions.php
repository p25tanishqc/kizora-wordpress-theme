<?php
/**
 * Kizora theme bootstrap.
 *
 * inc/helpers.php     data access, prices, images, shared markup (cards, crumbs, FAQ)
 * inc/setup.php       theme support, assets, content types, URL structure
 * inc/woocommerce.php cart drawer, AJAX add to cart, kits, WooCommerce tweaks
 * inc/seo.php         titles, descriptions and structured data (skipped where Yoast covers it)
 * inc/importer.php    Tools → Kizora setup: one-time import of pages, products and content
 */
defined('ABSPATH') || exit;

define('KZ_VERSION', '1.0.0');
define('KZ_DIR', get_template_directory());
define('KZ_URI', get_template_directory_uri());

require KZ_DIR . '/inc/helpers.php';
require KZ_DIR . '/inc/setup.php';
require KZ_DIR . '/inc/seo.php';
if (class_exists('WooCommerce')) {
    require KZ_DIR . '/inc/woocommerce.php';
}
require KZ_DIR . '/inc/importer.php';
