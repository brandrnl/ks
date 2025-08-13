<?php
/**
 * Admin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 1000); // Late priority om na post type registratie te komen
        add_action('admin_init', array($this, 'register_settings'));
        
        // Category custom fields
        add_action('kleurstaal_category_add_form_fields', array($this, 'add_category_fields'));
        add_action('kleurstaal_category_edit_form_fields', array($this, 'edit_category_fields'));
        add_action('created_kleurstaal_category', array($this, 'save_category_fields'));
        add_action('edited_kleurstaal_category', array($this, 'save_category_fields'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-kleurstaal', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-kleurstaal', array($this, 'handle_bulk_actions'), 10, 3);
    }
    
    /**
     * Add admin menu - onder het Kleurstalen hoofdmenu
     */
    public function add_admin_menu() {
        // Instellingen pagina onder Kleurstalen menu
        add_submenu_page(
            'edit.php?post_type=kleurstaal', // Parent menu
            __('Kleurstalen Instellingen', 'kleurstalen-manager'),
            __('Instellingen', 'kleurstalen-manager'),
            'manage_options',
            'ksm-settings',
            array($this, 'settings_page')
        );
        
        // Statistieken pagina
        add_submenu_page(
            'edit.php?post_type=kleurstaal',
            __('Kleurstalen Statistieken', 'kleurstalen-manager'),
            __('Statistieken', 'kleurstalen-manager'),
            'manage_options',
            'ksm-statistics',
            array($this, 'statistics_page')
        );
        
        // Import/Export pagina
        add_submenu_page(
            'edit.php?post_type=kleurstaal',
            __('Kleurstalen Import/Export', 'kleurstalen-manager'),
            __('Import/Export', 'kleurstalen-manager'),
            'manage_options',
            'ksm-import-export',
            array($this, 'import_export_page')
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Kleurstalen Instellingen', 'kleurstalen-manager'); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('ksm_settings_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ksm_price_per_sample">
                                <?php _e('Prijs per kleurstaal set', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <?php echo get_woocommerce_currency_symbol(); ?>
                            <input type="number" 
                                   id="ksm_price_per_sample" 
                                   name="ksm_price_per_sample" 
                                   value="<?php echo esc_attr(get_option('ksm_price_per_sample', 4)); ?>" 
                                   min="0" 
                                   step="0.01" />
                            <p class="description">
                                <?php _e('De prijs die klanten betalen voor een set kleurstalen', 'kleurstalen-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ksm_max_samples">
                                <?php _e('Maximum aantal kleurstalen', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="ksm_max_samples" 
                                   name="ksm_max_samples" 
                                   value="<?php echo esc_attr(get_option('ksm_max_samples', 10)); ?>" 
                                   min="1" 
                                   max="50" />
                            <p class="description">
                                <?php _e('Maximum aantal kleurstalen dat per bestelling geselecteerd kan worden', 'kleurstalen-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ksm_delivery_days">
                                <?php _e('Levertijd', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ksm_delivery_days" 
                                   name="ksm_delivery_days" 
                                   value="<?php echo esc_attr(get_option('ksm_delivery_days', '3-4')); ?>" />
                            <span><?php _e('werkdagen', 'kleurstalen-manager'); ?></span>
                            <p class="description">
                                <?php _e('Bijvoorbeeld: 3-4 of 2-3', 'kleurstalen-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Kortingscode', 'kleurstalen-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="ksm_discount_enabled" 
                                       value="1" 
                                       <?php checked(get_option('ksm_discount_enabled', 1), 1); ?> />
                                <?php _e('Genereer kortingscode na bestelling', 'kleurstalen-manager'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Klanten ontvangen een kortingscode ter waarde van het aankoopbedrag', 'kleurstalen-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ksm_discount_validity_days">
                                <?php _e('Geldigheid kortingscode', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="ksm_discount_validity_days" 
                                   name="ksm_discount_validity_days" 
                                   value="<?php echo esc_attr(get_option('ksm_discount_validity_days', 90)); ?>" 
                                   min="1" />
                            <span><?php _e('dagen', 'kleurstalen-manager'); ?></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Email instellingen', 'kleurstalen-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="ksm_send_confirmation_email" 
                                       value="1" 
                                       <?php checked(get_option('ksm_send_confirmation_email', 1), 1); ?> />
                                <?php _e('Stuur bevestigingsemail voor kleurstalen bestelling', 'kleurstalen-manager'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ksm_email_from_name">
                                <?php _e('Email afzender naam', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ksm_email_from_name" 
                                   name="ksm_email_from_name" 
                                   value="<?php echo esc_attr(get_option('ksm_email_from_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ksm_email_from_address">
                                <?php _e('Email afzender adres', 'kleurstalen-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email" 
                                   id="ksm_email_from_address" 
                                   name="ksm_email_from_address" 
                                   value="<?php echo esc_attr(get_option('ksm_email_from_address', get_option('admin_email'))); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Statistics page
     */
    public function statistics_page() {
        global $wpdb;
        
        // Get statistics
        $total_samples = wp_count_posts('kleurstaal');
        $active_samples = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'kleurstaal'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_ksm_active'
            AND pm.meta_value = '1'
        ");
        
        // Get order statistics
        $product_id = get_option('ksm_sample_product_id');
        $total_orders = 0;
        $total_revenue = 0;
        
        if ($product_id && class_exists('WC_Product')) {
            $product = wc_get_product($product_id);
            if ($product) {
                $total_orders = $product->get_total_sales();
                $total_revenue = $total_orders * $product->get_price();
            }
        }
        
        // Get popular samples
        $popular_samples = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as selections
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'kleurstaal'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_ksm_selection_count'
            ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Kleurstalen Statistieken', 'kleurstalen-manager'); ?></h1>
            
            <div class="ksm-stats-grid">
                <div class="ksm-stat-box">
                    <h3><?php _e('Totaal Kleurstalen', 'kleurstalen-manager'); ?></h3>
                    <p class="ksm-stat-number"><?php echo $total_samples->publish; ?></p>
                    <p class="ksm-stat-sub"><?php echo $active_samples; ?> <?php _e('actief', 'kleurstalen-manager'); ?></p>
                </div>
                
                <div class="ksm-stat-box">
                    <h3><?php _e('Totaal Bestellingen', 'kleurstalen-manager'); ?></h3>
                    <p class="ksm-stat-number"><?php echo $total_orders; ?></p>
                    <p class="ksm-stat-sub">
                        <?php 
                        if (function_exists('wc_price')) {
                            echo wc_price($total_revenue);
                        } else {
                            echo '€' . number_format($total_revenue, 2, ',', '.');
                        }
                        ?> <?php _e('omzet', 'kleurstalen-manager'); ?>
                    </p>
                </div>
                
                <div class="ksm-stat-box">
                    <h3><?php _e('Categorieën', 'kleurstalen-manager'); ?></h3>
                    <p class="ksm-stat-number"><?php echo wp_count_terms('kleurstaal_category'); ?></p>
                </div>
                
                <div class="ksm-stat-box">
                    <h3><?php _e('Gemiddeld per bestelling', 'kleurstalen-manager'); ?></h3>
                    <p class="ksm-stat-number">
                        <?php
                        $avg = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;
                        if (function_exists('wc_price')) {
                            echo wc_price($avg);
                        } else {
                            echo '€' . number_format($avg, 2, ',', '.');
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($popular_samples)) : ?>
                <h2><?php _e('Populairste Kleurstalen', 'kleurstalen-manager'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Kleurstaal', 'kleurstalen-manager'); ?></th>
                            <th><?php _e('Aantal selecties', 'kleurstalen-manager'); ?></th>
                            <th><?php _e('Acties', 'kleurstalen-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_samples as $sample) : ?>
                            <tr>
                                <td><?php echo esc_html($sample->post_title); ?></td>
                                <td><?php echo intval($sample->selections); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($sample->ID); ?>">
                                        <?php _e('Bewerken', 'kleurstalen-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <h2><?php _e('Recent Kortingscodes', 'kleurstalen-manager'); ?></h2>
            <?php
            $coupons = get_posts(array(
                'post_type' => 'shop_coupon',
                'posts_per_page' => 10,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_ksm_generated',
                        'value' => '1',
                        'compare' => '='
                    )
                )
            ));
            
            if ($coupons && class_exists('WC_Coupon')) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'kleurstalen-manager'); ?></th>
                            <th><?php _e('Waarde', 'kleurstalen-manager'); ?></th>
                            <th><?php _e('Status', 'kleurstalen-manager'); ?></th>
                            <th><?php _e('Vervaldatum', 'kleurstalen-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon_post) :
                            $coupon = new WC_Coupon($coupon_post->ID);
                            ?>
                            <tr>
                                <td><code><?php echo $coupon->get_code(); ?></code></td>
                                <td>
                                    <?php 
                                    if (function_exists('wc_price')) {
                                        echo wc_price($coupon->get_amount());
                                    } else {
                                        echo '€' . number_format($coupon->get_amount(), 2, ',', '.');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($coupon->get_usage_count() >= $coupon->get_usage_limit()) {
                                        echo '<span style="color: green;">✓ ' . __('Gebruikt', 'kleurstalen-manager') . '</span>';
                                    } else {
                                        echo '<span style="color: orange;">⏳ ' . __('Actief', 'kleurstalen-manager') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $expiry = $coupon->get_date_expires();
                                    echo $expiry ? $expiry->date('d-m-Y') : __('Geen', 'kleurstalen-manager');
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('Nog geen kortingscodes gegenereerd.', 'kleurstalen-manager'); ?></p>
            <?php endif; ?>
        </div>
        
        <style>
            .ksm-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .ksm-stat-box {
                background: white;
                border: 1px solid #ddd;
                padding: 20px;
                text-align: center;
                border-radius: 5px;
            }
            .ksm-stat-box h3 {
                margin: 0 0 10px 0;
                color: #555;
                font-size: 14px;
                font-weight: 600;
            }
            .ksm-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #333;
                margin: 10px 0;
            }
            .ksm-stat-sub {
                color: #888;
                font-size: 14px;
            }
        </style>
        <?php
    }
    
    /**
     * Import/Export page
     */
    public function import_export_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export Kleurstalen', 'kleurstalen-manager'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Export Kleurstalen', 'kleurstalen-manager'); ?></h2>
                <p><?php _e('Download alle kleurstalen als CSV bestand.', 'kleurstalen-manager'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('ksm_export', 'ksm_export_nonce'); ?>
                    <input type="hidden" name="ksm_action" value="export">
                    <button type="submit" class="button button-primary">
                        <?php _e('Export CSV', 'kleurstalen-manager'); ?>
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('Import Kleurstalen', 'kleurstalen-manager'); ?></h2>
                <p><?php _e('Upload een CSV bestand met kleurstalen.', 'kleurstalen-manager'); ?></p>
                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('ksm_import', 'ksm_import_nonce'); ?>
                    <input type="hidden" name="ksm_action" value="import">
                    <input type="file" name="ksm_import_file" accept=".csv" required>
                    <br><br>
                    <button type="submit" class="button button-primary">
                        <?php _e('Import CSV', 'kleurstalen-manager'); ?>
                    </button>
                </form>
                
                <h3><?php _e('CSV Format', 'kleurstalen-manager'); ?></h3>
                <p><?php _e('Het CSV bestand moet de volgende kolommen bevatten:', 'kleurstalen-manager'); ?></p>
                <code>title,sku,description,category,material,colors,active,popular,sort_order</code>
                <p class="description">
                    <?php _e('Colors: gescheiden door | (bijvoorbeeld: #FF0000|#00FF00)', 'kleurstalen-manager'); ?><br>
                    <?php _e('Active/Popular: 1 of 0', 'kleurstalen-manager'); ?>
                </p>
            </div>
        </div>
        <?php
        
        // Handle export/import
        if (isset($_POST['ksm_action'])) {
            if ($_POST['ksm_action'] === 'export' && wp_verify_nonce($_POST['ksm_export_nonce'], 'ksm_export')) {
                $this->export_samples();
            } elseif ($_POST['ksm_action'] === 'import' && wp_verify_nonce($_POST['ksm_import_nonce'], 'ksm_import')) {
                $this->import_samples();
            }
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ksm_settings_group', 'ksm_price_per_sample');
        register_setting('ksm_settings_group', 'ksm_max_samples');
        register_setting('ksm_settings_group', 'ksm_delivery_days');
        register_setting('ksm_settings_group', 'ksm_discount_enabled');
        register_setting('ksm_settings_group', 'ksm_discount_validity_days');
        register_setting('ksm_settings_group', 'ksm_send_confirmation_email');
        register_setting('ksm_settings_group', 'ksm_email_from_name');
        register_setting('ksm_settings_group', 'ksm_email_from_address');
    }
    
    /**
     * Add category fields
     */
    public function add_category_fields() {
        ?>
        <div class="form-field">
            <label for="category_icon"><?php _e('Icon Class', 'kleurstalen-manager'); ?></label>
            <input type="text" name="category_icon" id="category_icon" value="">
            <p class="description"><?php _e('FontAwesome of Dashicons class (bijv: dashicons-admin-appearance)', 'kleurstalen-manager'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="category_image"><?php _e('Afbeelding URL', 'kleurstalen-manager'); ?></label>
            <input type="text" name="category_image" id="category_image" value="">
            <button type="button" class="button ksm-upload-image"><?php _e('Upload', 'kleurstalen-manager'); ?></button>
        </div>
        
        <div class="form-field">
            <label for="category_order"><?php _e('Sorteer volgorde', 'kleurstalen-manager'); ?></label>
            <input type="number" name="category_order" id="category_order" value="0" min="0">
            <p class="description"><?php _e('Lager nummer = hoger in lijst', 'kleurstalen-manager'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit category fields
     */
    public function edit_category_fields($term) {
        $icon = get_term_meta($term->term_id, 'category_icon', true);
        $image = get_term_meta($term->term_id, 'category_image', true);
        $order = get_term_meta($term->term_id, 'category_order', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="category_icon"><?php _e('Icon Class', 'kleurstalen-manager'); ?></label></th>
            <td>
                <input type="text" name="category_icon" id="category_icon" value="<?php echo esc_attr($icon); ?>">
                <p class="description"><?php _e('FontAwesome of Dashicons class (bijv: dashicons-admin-appearance)', 'kleurstalen-manager'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="category_image"><?php _e('Afbeelding URL', 'kleurstalen-manager'); ?></label></th>
            <td>
                <input type="text" name="category_image" id="category_image" value="<?php echo esc_attr($image); ?>">
                <button type="button" class="button ksm-upload-image"><?php _e('Upload', 'kleurstalen-manager'); ?></button>
                <?php if ($image) : ?>
                    <br><br>
                    <img src="<?php echo esc_url($image); ?>" style="max-width: 200px; height: auto;">
                <?php endif; ?>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="category_order"><?php _e('Sorteer volgorde', 'kleurstalen-manager'); ?></label></th>
            <td>
                <input type="number" name="category_order" id="category_order" value="<?php echo esc_attr($order ?: 0); ?>" min="0">
                <p class="description"><?php _e('Lager nummer = hoger in lijst', 'kleurstalen-manager'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save category fields
     */
    public function save_category_fields($term_id) {
        if (isset($_POST['category_icon'])) {
            update_term_meta($term_id, 'category_icon', sanitize_text_field($_POST['category_icon']));
        }
        
        if (isset($_POST['category_image'])) {
            update_term_meta($term_id, 'category_image', esc_url_raw($_POST['category_image']));
        }
        
        if (isset($_POST['category_order'])) {
            update_term_meta($term_id, 'category_order', intval($_POST['category_order']));
        }
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'ksm_dashboard_widget',
            __('📦 Kleurstalen Overzicht', 'kleurstalen-manager'),
            array($this, 'dashboard_widget')
        );
    }
    
    /**
     * Dashboard widget content
     */
    public function dashboard_widget() {
        $product_id = get_option('ksm_sample_product_id');
        $total_orders = 0;
        
        if ($product_id && class_exists('WC_Product')) {
            $product = wc_get_product($product_id);
            if ($product) {
                $total_orders = $product->get_total_sales();
            }
        }
        
        $active_samples = get_posts(array(
            'post_type' => 'kleurstaal',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_ksm_active',
                    'value' => '1'
                )
            ),
            'posts_per_page' => -1
        ));
        
        ?>
        <ul>
            <li><?php _e('Actieve kleurstalen:', 'kleurstalen-manager'); ?> <strong><?php echo count($active_samples); ?></strong></li>
            <li><?php _e('Totaal bestellingen:', 'kleurstalen-manager'); ?> <strong><?php echo $total_orders; ?></strong></li>
            <li><?php _e('Deze maand:', 'kleurstalen-manager'); ?> <strong><?php echo $this->get_monthly_orders(); ?></strong></li>
        </ul>
        <p>
            <a href="<?php echo admin_url('admin.php?page=ksm-statistics'); ?>" class="button">
                <?php _e('Bekijk statistieken', 'kleurstalen-manager'); ?>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=kleurstaal'); ?>" class="button">
                <?php _e('Nieuwe kleurstaal', 'kleurstalen-manager'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Get monthly orders
     */
    private function get_monthly_orders() {
        global $wpdb;
        
        $product_id = get_option('ksm_sample_product_id');
        if (!$product_id) {
            return 0;
        }
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT order_items.order_id)
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-processing', 'wc-completed')
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = %s
            AND posts.post_date >= %s
        ", $product_id, date('Y-m-01')));
        
        return $count ?: 0;
    }
    
    /**
     * Add bulk actions
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['ksm_activate'] = __('Activeren', 'kleurstalen-manager');
        $bulk_actions['ksm_deactivate'] = __('Deactiveren', 'kleurstalen-manager');
        $bulk_actions['ksm_mark_popular'] = __('Markeer als populair', 'kleurstalen-manager');
        $bulk_actions['ksm_unmark_popular'] = __('Verwijder populair markering', 'kleurstalen-manager');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        switch ($action) {
            case 'ksm_activate':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_ksm_active', '1');
                }
                $redirect_to = add_query_arg('ksm_activated', count($post_ids), $redirect_to);
                break;
                
            case 'ksm_deactivate':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_ksm_active', '0');
                }
                $redirect_to = add_query_arg('ksm_deactivated', count($post_ids), $redirect_to);
                break;
                
            case 'ksm_mark_popular':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_ksm_popular', '1');
                }
                $redirect_to = add_query_arg('ksm_marked_popular', count($post_ids), $redirect_to);
                break;
                
            case 'ksm_unmark_popular':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_ksm_popular', '0');
                }
                $redirect_to = add_query_arg('ksm_unmarked_popular', count($post_ids), $redirect_to);
                break;
        }
        
        return $redirect_to;
    }
    
    /**
     * Export samples to CSV
     */
    private function export_samples() {
        // Set headers voor CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=kleurstalen-export-' . date('Y-m-d') . '.csv');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM voor Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array(
            'title',
            'sku',
            'description',
            'category',
            'material',
            'colors',
            'active',
            'popular',
            'sort_order'
        ));
        
        // Get samples
        $samples = get_posts(array(
            'post_type' => 'kleurstaal',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($samples as $sample) {
            $categories = wp_get_post_terms($sample->ID, 'kleurstaal_category', array('fields' => 'names'));
            $colors = get_post_meta($sample->ID, '_ksm_colors', true);
            
            fputcsv($output, array(
                $sample->post_title,
                get_post_meta($sample->ID, '_ksm_sku', true),
                get_post_meta($sample->ID, '_ksm_description', true),
                implode(',', $categories),
                get_post_meta($sample->ID, '_ksm_material', true),
                is_array($colors) ? implode('|', $colors) : '',
                get_post_meta($sample->ID, '_ksm_active', true) ? '1' : '0',
                get_post_meta($sample->ID, '_ksm_popular', true) ? '1' : '0',
                get_post_meta($sample->ID, '_ksm_sort_order', true)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Import samples from CSV
     */
    private function import_samples() {
        if (!isset($_FILES['ksm_import_file'])) {
            add_settings_error('ksm_messages', 'ksm_message', __('Geen bestand geüpload', 'kleurstalen-manager'), 'error');
            return;
        }
        
        $file = $_FILES['ksm_import_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('ksm_messages', 'ksm_message', __('Upload fout', 'kleurstalen-manager'), 'error');
            return;
        }
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            add_settings_error('ksm_messages', 'ksm_message', __('Kon bestand niet openen', 'kleurstalen-manager'), 'error');
            return;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $imported = 0;
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 9) continue;
            
            // Create post
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($data[0]),
                'post_type' => 'kleurstaal',
                'post_status' => 'publish'
            ));
            
            if ($post_id) {
                // Set meta
                update_post_meta($post_id, '_ksm_sku', sanitize_text_field($data[1]));
                update_post_meta($post_id, '_ksm_description', sanitize_textarea_field($data[2]));
                update_post_meta($post_id, '_ksm_material', sanitize_text_field($data[4]));
                
                // Colors
                if (!empty($data[5])) {
                    $colors = array_map('sanitize_hex_color', explode('|', $data[5]));
                    update_post_meta($post_id, '_ksm_colors', $colors);
                }
                
                update_post_meta($post_id, '_ksm_active', $data[6] === '1' ? '1' : '0');
                update_post_meta($post_id, '_ksm_popular', $data[7] === '1' ? '1' : '0');
                update_post_meta($post_id, '_ksm_sort_order', intval($data[8]));
                
                // Category
                if (!empty($data[3])) {
                    $categories = array_map('trim', explode(',', $data[3]));
                    $term_ids = array();
                    foreach ($categories as $cat_name) {
                        $term = term_exists($cat_name, 'kleurstaal_category');
                        if (!$term) {
                            $term = wp_insert_term($cat_name, 'kleurstaal_category');
                        }
                        if (!is_wp_error($term)) {
                            $term_ids[] = is_array($term) ? $term['term_id'] : $term;
                        }
                    }
                    if (!empty($term_ids)) {
                        wp_set_post_terms($post_id, $term_ids, 'kleurstaal_category');
                    }
                }
                
                $imported++;
            }
        }
        
        fclose($handle);
        
        add_settings_error('ksm_messages', 'ksm_message', 
            sprintf(__('%d kleurstalen geïmporteerd', 'kleurstalen-manager'), $imported), 
            'success'
        );
    }
}
