# Kizora Skincare: WordPress theme

This repository holds the **WordPress and WooCommerce version** of the Kizora redesign. The look and page layouts are the same as the Next.js prototype (repository `kizora`, preview at kizora-six.vercel.app). Products, cart, checkout and orders run on WooCommerce.

The prototype repository is not touched by anything here.

## What is in this repository

| Path | What it is |
| --- | --- |
| `kizora/` | The theme itself. This folder is what gets installed on WordPress. |
| `kizora/inc/data/*.json` | Product, collection, ingredient and ritual data exported from the prototype. |
| `kizora/inc/content/*.html` | Page content converted from the prototype (home, about, FAQ, rituals, ingredient and journal pages). |
| `src/kizora.css`, `tailwind.config.js` | Style source. Same design tokens as the prototype. |
| `tools/` | Scripts that rebuild the data, the page content, the CSS and the zip. |
| `dist/kizora-theme.zip` | Built theme, ready to upload (created by `npm run build`; not stored in git). |

## Install (on a staging copy first)

1. **Back up the site**, or ask the host for a staging copy of kizoraskincare.com. Never test on the live store first.
2. **Install the theme.** In WordPress, go to *Appearance → Themes → Add New → Upload Theme*, choose `kizora-theme.zip`, then select *Install* and *Activate*. WooCommerce must already be active.
3. **Preview the import.** Go to *Tools → Kizora setup* and select **Preview changes**. It lists what it would create or match, and changes nothing.
4. **Run the import.** Then select **Run import**. By default it:
   - creates the collections (WooCommerce product categories);
   - matches existing products by SKU, then by their current name, and attaches the new product details without changing their name, price or address;
   - creates any product or ritual kit that doesn't exist yet, with its bundled photo;
   - creates the pages (Home, Our story, How it's made, FAQ, Contact, Shipping, Returns, Rituals, Morning and Evening ritual), but leaves existing pages with the same address alone;
   - imports the 12 ingredient profiles, and the 3 journal articles **as drafts** for review.
5. **Set the homepage.** Tick *Use the imported Home page as the site homepage*, or set it in *Settings → Reading*.
6. **Set free delivery.** Go to *Appearance → Customize → Kizora* and set the free-delivery amount. Default is ₹499, the figure on today's shipping policy page. Keep it the same as the WooCommerce free-shipping rule.
7. **Check every page against the prototype,** then repeat the same steps on the live site.

Optional tick-boxes on the setup page:

- **Replace existing pages.** Overwrites pages that already exist with the same address. Old versions stay in each page's *Revisions*.
- **Overwrite names and prices.** Puts the prototype's short product names and prices on matched products.
- **Use /products/ and /collections/ addresses.** Changes WooCommerce's URL bases to match the prototype. Old `/product/...` and `/product-category/...` links redirect automatically with a 301.

The same import also runs from the command line:

```
wp kizora import --dry-run
wp kizora import --set-homepage
```

## How it works

| Prototype feature | WordPress version |
| --- | --- |
| Product pages | `woocommerce/single-product.php`. Price, stock, photos and reviews come from WooCommerce. The extra details (for whom, when to use, ingredients, FAQ) are saved on each product by the import. |
| Shop with filters and search | `woocommerce/archive-product.php` plus `assets/js/kizora.js`. Same filters, synonyms (haldi, mulethi…) and sorting. |
| Collections | WooCommerce product categories, with the prototype's intro text and photos. |
| Ritual kits | WooCommerce products that are not sold on their own. *Add the kit* adds the real products to the cart, so stock stays correct. Kit prices are always the sum of their products. |
| Cart drawer and cart page | Real WooCommerce cart, updated without reloading. |
| Checkout and My account | WooCommerce's own checkout (classic or block), styled in Kizora's colours (`assets/css/woo.css`). |
| Morning and Evening ritual | `template-parts/view-ritual.php`, built from `inc/data/rituals.json`. |
| Ingredient library | Post type *Ingredients* (`/ingredients/`), with the same filter and search. |
| Journal | Post type *Journal* (`/journal/`). |
| Other pages | Normal WordPress pages. Their content is the prototype's markup inside an HTML block, so the layout is identical. |
| SEO | Each page gets the prototype's title and description. If Yoast is active, the import writes them into Yoast instead. Product, breadcrumb, HowTo and organisation schema are included. |

Products added later in WooCommerce appear in the shop and their collection automatically, using their WooCommerce name, price, description and image.

## Editing content

- **Prices, stock, product photos:** edit in WooCommerce as usual. Every page picks up the change, including cards on the home and ritual pages.
- **Product details** (for whom, how to use, FAQ): saved in each product's `_kz_data` field. A developer can change these, or they can be moved into the product description.
- **Page text:** *Pages → edit page*. The text sits in a *Custom HTML* block. Change words carefully and leave the `class="…"` parts as they are.
- **Ingredients and Journal:** in their own menus in the dashboard. Journal articles stay drafts until someone publishes them.
- **Top bar text:** *Appearance → Customize → Kizora*.

## Developing

```
npm install
npm run build:css    # rebuild kizora/assets/css/kizora.css after changing classes
npm run build        # CSS + dist/kizora-theme.zip
```

Tailwind scans the PHP templates, the page content and the JavaScript, so any class used in them is included.

To refresh data or page content from the prototype (repository `kizora`, after `npm run build` there):

```
npx tsx tools/export-data.ts kizora/inc/data
python3 tools/convert-pages.py ../kizora/out kizora/inc/content
```

## Tested

Tested on 10 Nov 2026 on a local install: WordPress 6.7, WooCommerce 9.3.3, PHP 8.3.

- Every page was compared side by side with the prototype at desktop and phone widths.
- Add to cart, kit quantities, the cart drawer, the cart page, shop filters and search, the ingredient search, old-URL redirects and the product import (including matching an existing product) all work.
- A test order was placed through the classic checkout with cash on delivery.

Still to check on Kizora's staging site:

- The **block checkout**, which couldn't run in the local test setup.
- Kizora's **payment gateway and shipping rules**.
- The site's actual **WordPress and WooCommerce versions** (kizoraskincare.com runs a newer WordPress than the test setup).

## Not included

- The prototype's review-mode toggle and "Prototype preview" bar. They were for the review only.
- Newsletter sign-up. The prototype had no working form; connect Kizora's email tool if needed.
