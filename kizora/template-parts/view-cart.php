<?php
/** Cart page (prototype: app/cart/page.tsx + components/cart-view.tsx). */
defined('ABSPATH') || exit;
?>
<section class="wrap pb-10 pt-8">
  <?php echo kz_crumbs([['name' => 'Cart']]); ?>
  <h1 class="mb-10 mt-10 text-[46px]">Your cart</h1>
  <?php if (function_exists('woocommerce_output_all_notices')) { echo '<div class="kz-notices mb-6">'; woocommerce_output_all_notices(); echo '</div>'; } ?>
  <?php echo kz_cart_page_body(); ?>
</section>
