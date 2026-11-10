<?php
/** Home: the page set under Settings → Reading → Homepage (imported from the prototype). */
defined('ABSPATH') || exit;
if (get_option('show_on_front') === 'page') { require __DIR__ . '/page.php'; return; }
require __DIR__ . '/index.php';
