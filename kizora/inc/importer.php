<?php
/**
 * Tools → Kizora setup. One-time import of the prototype's pages, products, kits, ingredient
 * profiles and journal articles. Safe to run more than once: it matches what already exists and,
 * by default, never overwrites existing pages, product names or prices.
 *
 * WP-CLI: wp kizora import [--dry-run] [--create-products] [--update-products] [--replace-pages]
 *                          [--set-homepage] [--publish-journal] [--pretty-urls]
 */
defined('ABSPATH') || exit;

const KZ_PAGES = [
    // key => [title, parent key, view]
    'home'         => ['Home', null, null],
    'about'        => ['Our story', null, null],
    'how-its-made' => ["How it's made", null, null],
    'faq'          => ['FAQ', null, null],
    'contact'      => ['Contact', null, null],
    'shipping'     => ['Shipping and delivery', null, null],
    'returns'      => ['Returns, refunds and cancellations', null, null],
    'rituals'      => ['Rituals', null, null],
    'morning'      => ['Morning ritual', 'rituals', 'ritual-morning'],
    'evening'      => ['Evening ritual', 'rituals', 'ritual-evening'],
];

class KZ_Importer {
    public array $log = [];
    private array $o;

    public function __construct(array $options) {
        $this->o = array_merge([
            'dry_run' => false, 'collections' => true, 'products' => true, 'create_products' => true, 'update_products' => false,
            'photos' => true, 'pages' => true, 'replace_pages' => false, 'ingredients' => true, 'journal' => true,
            'publish_journal' => false, 'set_homepage' => false, 'pretty_urls' => false,
        ], $options);
    }

    private function say(string $msg): void { $this->log[] = ($this->o['dry_run'] ? '[preview] ' : '') . $msg; }
    private function dry(): bool { return (bool) $this->o['dry_run']; }

    public function run(): array {
        if (!class_exists('WooCommerce')) { $this->say('WooCommerce is not active. Activate it first, then run the import again.'); return $this->log; }
        if ($this->o['collections']) $this->collections();
        if ($this->o['products']) $this->products();
        if ($this->o['pages']) $this->pages();
        if ($this->o['ingredients']) $this->ingredients();
        if ($this->o['journal']) $this->journal();
        if ($this->o['pretty_urls']) $this->pretty_urls();
        if (!$this->dry()) { update_option('kz_imported', current_time('mysql')); flush_rewrite_rules(); }
        $this->say('Done.');
        return $this->log;
    }

    /* ----------------------------------------------------------- collections */

    private function collections(): void {
        foreach (kz_data('collections') as $c) {
            $t = get_term_by('slug', $c['slug'], 'product_cat');
            if ($t) { $this->say("Collection exists: {$c['name']}"); continue; }
            $this->say("Create collection: {$c['name']} (/{$c['slug']}/)");
            if (!$this->dry()) wp_insert_term($c['name'], 'product_cat', ['slug' => $c['slug'], 'description' => $c['intro']]);
        }
    }

    /* -------------------------------------------------------------- products */

    /** Existing product for a prototype item: by SKU, then the live title, then the slug. */
    private function match(array $p): int {
        if (!empty($p['sku']) && ($id = wc_get_product_id_by_sku($p['sku']))) return (int) $id;
        $titles = [];
        foreach (array_filter([$p['liveName'] ?? '', $p['name']]) as $t) { $titles[] = $t; $titles[] = esc_html($t); $titles[] = str_replace('&', '&amp;', $t); }
        foreach (array_unique($titles) as $title) {
            $q = get_posts(['post_type' => 'product', 'title' => $title, 'post_status' => ['publish', 'draft', 'private'], 'numberposts' => 1, 'fields' => 'ids']);
            if ($q) return (int) $q[0];
        }
        $post = get_page_by_path($p['slug'], OBJECT, 'product');
        return $post ? (int) $post->ID : 0;
    }

    private function cat_ids(array $slugs): array {
        $ids = [];
        foreach ($slugs as $s) if ($t = get_term_by('slug', $s, 'product_cat')) $ids[] = (int) $t->term_id;
        return $ids;
    }

    private function products(): void {
        $all = kz_data('products');
        // Products first, so kit prices can be summed from them.
        usort($all, fn($a, $b) => ($a['kind'] === 'kit') <=> ($b['kind'] === 'kit'));
        foreach ($all as $p) {
            $kit = $p['kind'] === 'kit';
            $label = ($kit ? 'kit ' : '') . $p['name'];
            $id = $this->match($p);
            $data = $p;
            unset($data['liveName'], $data['approvals']);
            if ($id) {
                $this->say("Match: {$label} → existing product #{$id} \"" . html_entity_decode(get_the_title($id)) . '"' . ($this->o['update_products'] ? ' (name and price will be updated)' : ' (name and price kept)'));
                if ($this->dry()) continue;
                update_post_meta($id, '_kz_slug', $p['slug']);
                update_post_meta($id, '_kz_data', $data);
                if ($kit) update_post_meta($id, '_kz_kit', $p['components']);
                wp_set_object_terms($id, $this->cat_ids($p['collections']), 'product_cat', true);
                if ($this->o['update_products'] && ($wc = wc_get_product($id))) {
                    $wc->set_name($p['name']);
                    $wc->set_regular_price((string) ($kit ? $this->kit_price($p) : $p['price']));
                    $wc->save();
                }
                if ($this->o['photos'] && !get_post_thumbnail_id($id) && !$kit) $this->attach_photo($id, $p);
                continue;
            }
            if (!$this->o['create_products']) { $this->say("No match for {$label}; skipped (creating products is switched off)."); continue; }
            $this->say("Create product: {$label}, " . kz_inr($kit ? $this->kit_price($p) : $p['price']) . ($p['size'] ? ", {$p['size']}" : ''));
            if ($this->dry()) continue;
            $wc = new WC_Product_Simple();
            $wc->set_name($p['name']);
            $wc->set_slug($p['slug']);
            $wc->set_status('publish');
            $wc->set_regular_price((string) ($kit ? $this->kit_price($p) : $p['price']));
            if (!empty($p['sku']) && !wc_get_product_id_by_sku($p['sku'])) $wc->set_sku($p['sku']);
            $wc->set_short_description($p['oneLine']);
            $wc->set_description(implode("\n\n", $p['description']));
            $wc->set_category_ids($this->cat_ids($p['collections']));
            if (!$kit && !empty($p['size'])) {
                $a = new WC_Product_Attribute();
                $a->set_name('Size'); $a->set_options([$p['size']]); $a->set_visible(true);
                $wc->set_attributes([$a]);
            }
            $id = $wc->save();
            update_post_meta($id, '_kz_slug', $p['slug']);
            update_post_meta($id, '_kz_data', $data);
            if ($kit) update_post_meta($id, '_kz_kit', $p['components']);
            if ($this->o['photos'] && !$kit) $this->attach_photo($id, $p);
        }
    }

    private function kit_price(array $kit): float {
        $map = array_column(kz_data('products'), 'price', 'slug');
        return array_sum(array_map(fn($s) => (float) ($map[$s] ?? 0), $kit['components']));
    }

    /** Copy the bundled product photo into the Media Library and set it as the product image. */
    private function attach_photo(int $id, array $p): void {
        $src = KZ_DIR . "/assets/images/products/{$p['slug']}/1.webp";
        if (!file_exists($src)) return;
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam($p['slug'] . '.webp');
        copy($src, $tmp);
        $att = media_handle_sideload(['name' => "kizora-{$p['slug']}.webp", 'tmp_name' => $tmp], $id, $p['name'], ['post_name' => "kizora-{$p['slug']}-photo"]);
        if (is_wp_error($att)) { @unlink($tmp); $this->say("  Photo not attached for {$p['name']}: " . $att->get_error_message()); return; }
        update_post_meta($att, '_wp_attachment_image_alt', $p['name'] . ($p['size'] ? ", {$p['size']}" : ''));
        set_post_thumbnail($id, $att);
        $this->say("  Photo attached for {$p['name']}");
    }

    /* ----------------------------------------------------------------- pages */

    private function content(string $key): string {
        $f = KZ_DIR . "/inc/content/{$key}.html";
        return file_exists($f) ? "<!-- wp:html -->\n" . file_get_contents($f) . "<!-- /wp:html -->" : '';
    }

    private function seo_meta(int $id, string $key): void {
        $m = json_decode((string) file_get_contents(KZ_DIR . '/inc/content/manifest.json'), true)[$key] ?? null;
        if (!$m) return;
        update_post_meta($id, '_kz_title', $m['title']);
        update_post_meta($id, '_kz_desc', $m['description']);
        if (defined('WPSEO_VERSION')) {
            update_post_meta($id, '_yoast_wpseo_title', $m['title']);
            update_post_meta($id, '_yoast_wpseo_metadesc', $m['description']);
        }
    }

    private function pages(): void {
        $ids = [];
        foreach (KZ_PAGES as $key => [$title, $parent, $view]) {
            $parent_id = $parent ? ($ids[$parent] ?? 0) : 0;
            $path = $parent ? "{$parent}/{$key}" : $key;
            $existing = get_page_by_path($path, OBJECT, 'page');
            $content = $view ? '' : $this->content($key);
            if ($existing) {
                $ids[$key] = (int) $existing->ID;
                if (!$this->o['replace_pages']) { $this->say("Page exists, left as is: /{$path}/ (\"{$existing->post_title}\")"); continue; }
                $this->say("Replace content of existing page: /{$path}/ (the old version stays in its revisions)");
                if ($this->dry()) continue;
                wp_update_post(['ID' => $existing->ID, 'post_content' => $content]);
                $id = (int) $existing->ID;
            } else {
                $this->say("Create page: /{$path}/ \"{$title}\"");
                if ($this->dry()) { $ids[$key] = 0; continue; }
                $id = (int) wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $key,
                    'post_parent' => $parent_id, 'post_content' => $content]);
                $ids[$key] = $id;
            }
            if ($view) update_post_meta($id, '_kz_view', $view); else update_post_meta($id, '_kz_converted', 1);
            $this->seo_meta($id, $view ? 'rituals' : $key);
            if ($view) {
                $r = kz_data('rituals')[str_replace('ritual-', '', $view)];
                update_post_meta($id, '_kz_title', $r['title'] . ' | Kizora');
                update_post_meta($id, '_kz_desc', $r['intro']);
            }
        }
        if ($this->o['set_homepage'] && !empty($ids['home'])) {
            $this->say('Set the Home page as the site homepage (Settings → Reading).');
            if (!$this->dry()) { update_option('show_on_front', 'page'); update_option('page_on_front', $ids['home']); }
        }
    }

    /* ------------------------------------------------- ingredients and journal */

    private function upsert(string $type, string $slug, array $post, array $meta, string $key): void {
        $existing = get_page_by_path($slug, OBJECT, $type);
        $label = $type === 'kz_ingredient' ? 'ingredient' : 'journal article';
        if ($existing && !$this->o['replace_pages']) { $this->say("Exists, left as is: {$label} \"{$existing->post_title}\""); return; }
        $this->say(($existing ? 'Update' : 'Create') . " {$label}: \"{$post['post_title']}\"" . ($post['post_status'] === 'draft' ? ' (as a draft)' : ''));
        if ($this->dry()) return;
        $post = array_merge($post, ['post_type' => $type, 'post_name' => $slug, 'post_content' => $this->content($key)]);
        if ($existing) { $post['ID'] = $existing->ID; $id = wp_update_post($post); } else { $id = wp_insert_post($post); }
        foreach ($meta as $k => $v) update_post_meta($id, $k, $v);
        update_post_meta($id, '_kz_converted', 1);
        $this->seo_meta((int) $id, $key);
    }

    private function ingredients(): void {
        foreach (kz_data('ingredients') as $i => $g) {
            $this->upsert('kz_ingredient', $g['slug'], ['post_title' => $g['name'], 'post_excerpt' => $g['what'], 'post_status' => 'publish', 'menu_order' => $i], [], "ingredient-{$g['slug']}");
        }
    }

    private function journal(): void {
        foreach (kz_data('articles') as $i => $a) {
            $this->upsert('kz_journal', $a['slug'], ['post_title' => $a['title'], 'post_excerpt' => $a['dek'], 'menu_order' => $i,
                'post_status' => $this->o['publish_journal'] ? 'publish' : 'draft'], ['_kz_photos' => $a['photos'], '_kz_read' => $a['read']], "journal-{$a['slug']}");
        }
    }

    /* --------------------------------------------------------------- URLs */

    private function pretty_urls(): void {
        $this->say('Product URLs become /products/<name>/ and categories /collections/<name>/. Old /product/ and /product-category/ links redirect automatically.');
        if ($this->dry()) return;
        $p = (array) get_option('woocommerce_permalinks', []);
        $p['product_base'] = '/products';
        $p['category_base'] = 'collections';
        update_option('woocommerce_permalinks', $p);
    }
}

/* ------------------------------------------------------------- redirects */

/** After a URL change, send old WooCommerce links to their new home (301). */
add_action('template_redirect', function () {
    if (!is_404() || !function_exists('wc_get_page_permalink')) return;
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $home = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($home && strpos($path, $home) === 0) $path = trim(substr($path, strlen($home)), '/');
    $to = null;
    if (preg_match('#^(?:product|products)/([^/]+)$#', $path, $m)) {
        $post = get_page_by_path($m[1], OBJECT, 'product');
        if ($post && $post->post_status === 'publish') $to = get_permalink($post);
        elseif ($id = kz_pid($m[1])) $to = get_permalink($id);
    } elseif (preg_match('#^(?:product-category|collections)/([^/]+)$#', $path, $m)) {
        $slug = $m[1];
        foreach (kz_data('collections') as $c) if (!empty($c['replaces']) && trim($c['replaces'], '/') === "product-category/{$slug}") { $slug = $c['slug']; break; }
        $t = get_term_by('slug', $slug, 'product_cat');
        if ($t) $to = get_term_link($t);
    } elseif ($path === 'cart') {
        $to = wc_get_cart_url();
    }
    if ($to && !is_wp_error($to)) { wp_safe_redirect($to, 301); exit; }
}, 1);

/* ------------------------------------------------------------- admin page */

add_action('admin_menu', function () {
    add_management_page('Kizora setup', 'Kizora setup', 'manage_options', 'kizora-setup', 'kz_setup_page');
});

function kz_setup_page() {
    if (!current_user_can('manage_options')) return;
    $fields = [
        'collections' => ['Create the collections (WooCommerce product categories)', true],
        'products' => ['Import products and ritual kits (matches existing products by SKU, then name)', true],
        'create_products' => ['Create products that don\'t exist yet', true],
        'update_products' => ['Overwrite names and prices of matched products with the prototype\'s', false],
        'photos' => ['Add the bundled product photo where a product has no image', true],
        'pages' => ['Create the pages (Home, Our story, How it\'s made, FAQ, Contact, Shipping, Returns, Rituals)', true],
        'replace_pages' => ['Replace content of existing pages, ingredients and articles with the same address', false],
        'ingredients' => ['Import the 12 ingredient profiles', true],
        'journal' => ['Import the 3 journal articles', true],
        'publish_journal' => ['Publish the journal articles (otherwise they are saved as drafts for review)', false],
        'set_homepage' => ['Use the imported Home page as the site homepage', false],
        'pretty_urls' => ['Use /products/ and /collections/ addresses (old links redirect)', false],
    ];
    $log = [];
    if (!empty($_POST['kz_go']) && check_admin_referer('kz_setup')) {
        $opts = ['dry_run' => $_POST['kz_go'] === 'preview'];
        foreach ($fields as $k => $f) $opts[$k] = !empty($_POST[$k]);
        $log = (new KZ_Importer($opts))->run();
    }
    $done = get_option('kz_imported');
    ?>
    <div class="wrap">
      <h1>Kizora setup</h1>
      <p style="max-width:720px">Imports the redesign's pages, products, ritual kits, ingredient profiles and journal articles. Run <b>Preview</b> first: it lists every change without making any. Existing pages and product names or prices are left alone unless you tick the options that say otherwise.<?php echo $done ? ' Last import: ' . esc_html($done) . '.' : ''; ?></p>
      <form method="post">
        <?php wp_nonce_field('kz_setup'); ?>
        <table class="form-table" role="presentation"><tbody>
          <?php foreach ($fields as $k => [$label, $default]) :
              $checked = isset($_POST['kz_go']) ? !empty($_POST[$k]) : $default; ?>
            <tr><td style="padding:6px 0"><label><input type="checkbox" name="<?php echo esc_attr($k); ?>" value="1" <?php checked($checked); ?>> <?php echo esc_html($label); ?></label></td></tr>
          <?php endforeach; ?>
        </tbody></table>
        <p><button class="button" name="kz_go" value="preview">Preview changes</button> <button class="button button-primary" name="kz_go" value="run" onclick="return confirm('Run the import now?');">Run import</button></p>
      </form>
      <?php if ($log) : ?>
        <h2>Result</h2>
        <pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:480px;overflow:auto;white-space:pre-wrap"><?php echo esc_html(implode("\n", $log)); ?></pre>
      <?php endif; ?>
    </div>
    <?php
}

/* ------------------------------------------------------------------ WP-CLI */

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('kizora import', function ($args, $assoc) {
        $o = ['dry_run' => isset($assoc['dry-run'])];
        foreach (['create-products' => 'create_products', 'update-products' => 'update_products', 'replace-pages' => 'replace_pages', 'set-homepage' => 'set_homepage',
                  'publish-journal' => 'publish_journal', 'pretty-urls' => 'pretty_urls', 'no-photos' => 'photos'] as $flag => $key) {
            if (isset($assoc[$flag])) $o[$key] = $flag !== 'no-photos';
        }
        foreach ((new KZ_Importer($o))->run() as $line) WP_CLI::log($line);
    });
}
