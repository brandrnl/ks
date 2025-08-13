<?php
/**
 * Plugin Name: Kleurstalen Manager Pro
 * Plugin URI: https://jaloezieopmaat.com
 * Description: Professionele kleurstalen manager met popup flow en volledige beheer mogelijkheden
 * Version: 2.0.2
 * Author: Jaloezie op Maat
 * Text Domain: kleurstalen-manager
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Voorkom directe toegang
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constanten
define('KSM_VERSION', '2.0.2');
define('KSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KSM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if class already exists to prevent conflicts
if (!class_exists('Kleurstalen_Manager')) {

// Hoofdklasse
class Kleurstalen_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Load translations at the right time
        add_action('init', array($this, 'load_textdomain'), 1);
        
        $this->check_dependencies();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('kleurstalen-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check of WooCommerce actief is
     */
    private function check_dependencies() {
        add_action('admin_notices', array($this, 'check_woocommerce'));
    }
    
    /**
     * Toon notice als WooCommerce niet actief is
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-error">
                <p><strong>Kleurstalen Manager Pro:</strong> Deze plugin vereist WooCommerce om te functioneren. Activeer eerst WooCommerce.</p>
            </div>
            <?php
        }
    }
    
    private function load_dependencies() {
        // Check if files exist before including
        $required_files = array(
            'includes/class-core.php',
            'includes/class-post-types.php',
            'includes/class-admin.php',
            'includes/class-frontend.php',
            'includes/class-ajax.php',
            'includes/class-cart.php',
            'includes/class-order.php',
            'includes/class-email.php',
            'includes/class-shortcodes.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = KSM_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('KSM: Missing required file: ' . $file);
            }
        }
    }
    
    private function init_hooks() {
        // Init action - priority 5 for early but not too early
        add_action('init', array($this, 'init'), 5);
        
        // Activatie/Deactivatie hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin scripts en styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Frontend scripts en styles
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . KSM_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }
    
    public function init() {
        // Check of WooCommerce beschikbaar is
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Init modules only if classes exist
        if (class_exists('KSM_Post_Types')) {
            $post_types = new KSM_Post_Types();
            $post_types->init();
        }
        
        if (class_exists('KSM_Shortcodes')) {
            $shortcodes = new KSM_Shortcodes();
            $shortcodes->init();
        }
        
        if (class_exists('KSM_Ajax')) {
            $ajax = new KSM_Ajax();
            $ajax->init();
        }
        
        if (class_exists('KSM_Cart')) {
            $cart = new KSM_Cart();
            $cart->init();
        }
        
        if (class_exists('KSM_Order')) {
            $order = new KSM_Order();
            $order->init();
        }
        
        // Admin alleen in admin
        if (is_admin() && class_exists('KSM_Admin')) {
            new KSM_Admin();
        }
        
        // Frontend alleen op frontend
        if (!is_admin() && class_exists('KSM_Frontend')) {
            new KSM_Frontend();
        }
    }
    
    /**
     * Plugin action links
     */
    public function plugin_action_links($links) {
        $action_links = array(
            '<a href="' . admin_url('edit.php?post_type=kleurstaal') . '">Kleurstalen</a>',
            '<a href="' . admin_url('admin.php?page=ksm-settings') . '">Instellingen</a>',
        );
        
        return array_merge($action_links, $links);
    }
    
    public function admin_scripts($hook) {
        global $post_type;
        
        // Laad op alle KSM pagina's
        if ('kleurstaal' === $post_type || strpos($hook, 'ksm-') !== false || strpos($hook, 'kleurstaal') !== false) {
            
            // Enqueue media uploader
            wp_enqueue_media();
            
            // jQuery UI for sortable
            wp_enqueue_script('jquery-ui-sortable');
            
            // Color picker voor WordPress
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Admin CSS
            wp_enqueue_style('ksm-admin', KSM_PLUGIN_URL . 'assets/css/admin.css', array(), KSM_VERSION);
            
            // Admin JS
            wp_enqueue_script('ksm-admin', KSM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker', 'jquery-ui-sortable'), KSM_VERSION, true);
            
            // Localize script for admin
            wp_localize_script('ksm-admin', 'ksm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ksm_admin_nonce'),
                'strings' => array(
                    'select_image' => __('Selecteer afbeelding', 'kleurstalen-manager'),
                    'use_image' => __('Gebruik deze afbeelding', 'kleurstalen-manager'),
                    'remove_confirm' => __('Weet je zeker dat je deze afbeelding wilt verwijderen?', 'kleurstalen-manager')
                )
            ));
            
            // Media uploader voor afbeeldingen
            if ($hook == 'edit-tags.php' || $hook == 'term.php') {
                wp_enqueue_media();
                
                // Inline script voor media uploader
                wp_add_inline_script('ksm-admin', '
                    jQuery(document).ready(function($) {
                        $(".ksm-upload-image").on("click", function(e) {
                            e.preventDefault();
                            var button = $(this);
                            var input = button.prev("input");
                            
                            var frame = wp.media({
                                title: "Selecteer afbeelding",
                                button: {
                                    text: "Gebruik deze afbeelding"
                                },
                                multiple: false
                            });
                            
                            frame.on("select", function() {
                                var attachment = frame.state().get("selection").first().toJSON();
                                input.val(attachment.url);
                            });
                            
                            frame.open();
                        });
                    });
                ');
            }
        }
    }
    
    public function frontend_scripts() {
        // jQuery dependency
        wp_enqueue_script('jquery');
        
        // Frontend CSS
        wp_enqueue_style('ksm-frontend', KSM_PLUGIN_URL . 'assets/css/frontend.css', array(), KSM_VERSION);
        
        // Frontend JS - gebruik de gefixte versie
        wp_enqueue_script('ksm-frontend', KSM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), KSM_VERSION, true);
        
        // Localize script
        wp_localize_script('ksm-frontend', 'ksm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ksm_nonce'),
            'cart_url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
            'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
            'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
            'price_per_sample' => get_option('ksm_price_per_sample', 4),
            'max_samples' => get_option('ksm_max_samples', 10),
            'strings' => array(
                'select_category' => __('Selecteer een categorie', 'kleurstalen-manager'),
                'select_samples' => __('Selecteer kleurstalen', 'kleurstalen-manager'),
                'added_to_cart' => __('Toegevoegd aan winkelwagen', 'kleurstalen-manager'),
                'error' => __('Er ging iets mis. Probeer het opnieuw.', 'kleurstalen-manager'),
                'loading' => __('Laden...', 'kleurstalen-manager'),
                'close' => __('Sluiten', 'kleurstalen-manager'),
                'no_samples' => __('Geen kleurstalen geselecteerd', 'kleurstalen-manager'),
                'max_reached' => __('Maximum aantal kleurstalen bereikt', 'kleurstalen-manager')
            )
        ));
        
        // Add inline CSS fix for scroll issues and centering
        wp_add_inline_style('ksm-frontend', '
            /* Fix for body scroll when popup is open */
            body.ksm-popup-open {
                overflow: hidden !important;
                position: fixed !important;
                width: 100% !important;
            }
            
            /* Ensure popup is properly positioned and centered */
            .ksm-popup {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .ksm-popup.active {
                display: flex !important;
            }
            
            .ksm-popup:not(.active) {
                display: none !important;
            }
            
            .ksm-popup-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0, 0, 0, 0.6) !important;
                z-index: 1 !important;
            }
            
            .ksm-popup-container {
                position: relative !important;
                z-index: 2 !important;
                background: white !important;
                border-radius: 12px !important;
                max-width: 900px !important;
                width: 90% !important;
                max-height: 90vh !important;
                overflow-y: auto !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            }
            
            /* Fix notification z-index */
            .ksm-notification {
                z-index: 9999999 !important;
            }
            
            /* Ensure samples grid displays properly */
            .ksm-samples-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
                gap: 15px !important;
                padding: 20px !important;
            }
            
            /* Price display in samples */
            .ksm-sample-price {
                font-weight: bold;
                color: #2ecc71;
                margin-top: 5px;
            }
        ');
        
        // Also add AJAX handlers for cart
        add_action('wp_ajax_ksm_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_ksm_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_ksm_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_ksm_get_cart', array($this, 'ajax_get_cart'));
    }
    
    /**
     * AJAX handler for add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is niet actief');
            return;
        }
        
        $samples = isset($_POST['samples']) ? $_POST['samples'] : array();
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        if (empty($samples)) {
            wp_send_json_error(__('Geen kleurstalen geselecteerd', 'kleurstalen-manager'));
            return;
        }
        
        // Get or create sample product - check if class exists first
        if (!class_exists('KSM_Core')) {
            wp_send_json_error(__('Core class niet gevonden', 'kleurstalen-manager'));
            return;
        }
        
        $core = new KSM_Core();
        $product_id = $core->get_or_create_sample_product();
        
        if (!$product_id) {
            wp_send_json_error(__('Kan product niet aanmaken', 'kleurstalen-manager'));
            return;
        }
        
        // Calculate total price based on selected samples
        $total_price = 0;
        $valid_samples = array();
        
        foreach ($samples as $sample_id) {
            $sample_id = intval($sample_id);
            if (get_post_type($sample_id) === 'kleurstaal') {
                $valid_samples[] = $sample_id;
                
                // Get individual price or use default
                $sample_price = get_post_meta($sample_id, '_ksm_price', true);
                if (!$sample_price) {
                    $sample_price = get_option('ksm_price_per_sample', 4);
                }
                $total_price += floatval($sample_price);
            }
        }
        
        if (empty($valid_samples)) {
            wp_send_json_error(__('Ongeldige kleurstalen', 'kleurstalen-manager'));
            return;
        }
        
        // Prepare cart item data
        $cart_item_data = array(
            'ksm_samples' => $valid_samples,
            'ksm_category' => $category_id,
            'ksm_is_sample' => true,
            'ksm_total_price' => $total_price
        );
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        if (!$cart_item_key) {
            wp_send_json_error(__('Kon niet toevoegen aan winkelwagen', 'kleurstalen-manager'));
            return;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d kleurstalen toegevoegd aan winkelwagen', 'kleurstalen-manager'), count($valid_samples)),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'samples_count' => count($valid_samples)
        ));
    }
    
    /**
     * AJAX handler for get cart
     */
    public function ajax_get_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is niet actief');
            return;
        }
        
        $cart_items = array();
        $total = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
                $samples = isset($cart_item['ksm_samples']) ? $cart_item['ksm_samples'] : array();
                
                foreach ($samples as $sample_id) {
                    $sample = get_post($sample_id);
                    if ($sample) {
                        $colors = get_post_meta($sample_id, '_ksm_colors', true);
                        $price = get_post_meta($sample_id, '_ksm_price', true);
                        if (!$price) {
                            $price = get_option('ksm_price_per_sample', 4);
                        }
                        
                        $cart_items[] = array(
                            'id' => $sample_id,
                            'title' => $sample->post_title,
                            'sku' => get_post_meta($sample_id, '_ksm_sku', true),
                            'colors' => $colors,
                            'price' => floatval($price),
                            'cart_key' => $cart_item_key
                        );
                        
                        $total += floatval($price);
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'items' => $cart_items,
            'total' => function_exists('wc_price') ? wc_price($total) : '€' . number_format($total, 2, ',', '.'),
            'raw_total' => $total,
            'count' => count($cart_items)
        ));
    }
    
    public function activate() {
        // Load dependencies first
        $this->load_dependencies();
        
        // Registreer post types en taxonomies
        if (class_exists('KSM_Post_Types')) {
            $post_types = new KSM_Post_Types();
            $post_types->register_post_types();
            $post_types->register_taxonomies();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Maak database tabellen
        $this->create_tables();
        
        // Maak standaard categorieën
        $this->create_default_categories();
        
        // Maak sample product alleen als WooCommerce actief is
        if (class_exists('WooCommerce') && class_exists('KSM_Core')) {
            $core = new KSM_Core();
            $core->create_sample_product();
        }
        
        // Set default options
        add_option('ksm_price_per_sample', 4);
        add_option('ksm_max_samples', 10);
        add_option('ksm_delivery_days', '3-4');
        add_option('ksm_discount_enabled', 1);
        add_option('ksm_discount_validity_days', 90);
        add_option('ksm_send_confirmation_email', 1);
        add_option('ksm_email_from_name', get_bloginfo('name'));
        add_option('ksm_email_from_address', get_option('admin_email'));
        
        // Voeg capabilities toe aan administrator
        $this->add_capabilities();
        
        // Set transient voor welkomstbericht
        set_transient('ksm_activation_notice', true, 5);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('ksm_daily_cleanup');
    }
    
    /**
     * Voeg capabilities toe
     */
    private function add_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            // Post type capabilities
            $role->add_cap('edit_post');
            $role->add_cap('read_post');
            $role->add_cap('delete_post');
            $role->add_cap('edit_posts');
            $role->add_cap('edit_others_posts');
            $role->add_cap('publish_posts');
            $role->add_cap('read_private_posts');
            
            // Custom capabilities
            $role->add_cap('manage_kleurstalen');
            $role->add_cap('edit_kleurstalen');
            $role->add_cap('delete_kleurstalen');
            $role->add_cap('publish_kleurstalen');
        }
        
        // Ook voor shop_manager als die bestaat
        $shop_manager = get_role('shop_manager');
        if ($shop_manager) {
            $shop_manager->add_cap('edit_post');
            $shop_manager->add_cap('read_post');
            $shop_manager->add_cap('delete_post');
            $shop_manager->add_cap('edit_posts');
            $shop_manager->add_cap('edit_others_posts');
            $shop_manager->add_cap('publish_posts');
            $shop_manager->add_cap('read_private_posts');
            
            $shop_manager->add_cap('manage_kleurstalen');
            $shop_manager->add_cap('edit_kleurstalen');
            $shop_manager->add_cap('delete_kleurstalen');
            $shop_manager->add_cap('publish_kleurstalen');
        }
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabel voor selectie tracking
        $table_name = $wpdb->prefix . 'ksm_selections';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(100) DEFAULT '',
            sample_ids text NOT NULL,
            category_id bigint(20) DEFAULT 0,
            order_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update database version
        update_option('ksm_db_version', '1.0');
    }
    
    public function create_default_categories() {
        $categories = array(
            'aluminium' => array(
                'name' => 'Aluminium Jaloezieën',
                'description' => 'Hoogwaardige aluminium jaloezieën in diverse kleuren'
            ),
            'houten' => array(
                'name' => 'Houten Jaloezieën',
                'description' => 'Natuurlijke houten jaloezieën voor een warme uitstraling'
            ),
            'bamboe' => array(
                'name' => 'Bamboe Jaloezieën',
                'description' => 'Duurzame bamboe jaloezieën voor een natuurlijke look'
            ),
            'kunststof' => array(
                'name' => 'Kunststof Jaloezieën',
                'description' => 'Onderhoudsvrije kunststof jaloezieën'
            )
        );
        
        foreach ($categories as $slug => $category) {
            if (!term_exists($slug, 'kleurstaal_category')) {
                $term = wp_insert_term(
                    $category['name'],
                    'kleurstaal_category',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
                
                // Voeg standaard icon toe
                if (!is_wp_error($term)) {
                    update_term_meta($term['term_id'], 'category_icon', 'dashicons-admin-appearance');
                }
            }
        }
    }
}

} // End class_exists check

// Start de plugin only once
if (!function_exists('ksm_init')) {
    function ksm_init() {
        return Kleurstalen_Manager::get_instance();
    }
    add_action('plugins_loaded', 'ksm_init');
}

// Toon welkomstbericht na activatie
add_action('admin_notices', function() {
    if (get_transient('ksm_activation_notice')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <h3>🎉 Kleurstalen Manager Pro is geactiveerd!</h3>
            <p>Bedankt voor het installeren van Kleurstalen Manager Pro.</p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=kleurstaal'); ?>" class="button button-primary">
                    Naar Kleurstalen
                </a>
                <a href="<?php echo admin_url('admin.php?page=ksm-settings'); ?>" class="button">
                    Instellingen configureren
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=kleurstaal'); ?>" class="button">
                    Eerste kleurstaal toevoegen
                </a>
            </p>
        </div>
        <?php
        delete_transient('ksm_activation_notice');
    }
});

// AJAX handler voor het aanmaken van standaard categorieën
add_action('wp_ajax_ksm_create_default_categories', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Geen rechten');
    }
    
    check_ajax_referer('ksm_admin', 'nonce');
    
    $manager = Kleurstalen_Manager::get_instance();
    $manager->create_default_categories();
    
    wp_send_json_success('Categorieën succesvol aangemaakt!');
});

// AJAX handler voor het ophalen van categorieën (frontend)
add_action('wp_ajax_ksm_get_categories', 'ksm_ajax_get_categories');
add_action('wp_ajax_nopriv_ksm_get_categories', 'ksm_ajax_get_categories');

function ksm_ajax_get_categories() {
    check_ajax_referer('ksm_nonce', 'nonce');
    
    $categories = get_terms(array(
        'taxonomy' => 'kleurstaal_category',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    $result = array();
    
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            // Get category image
            $image = get_term_meta($category->term_id, 'category_image', true);
            $icon = get_term_meta($category->term_id, 'category_icon', true);
            
            $result[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->count,
                'image' => $image,
                'icon' => $icon
            );
        }
    }
    
    wp_send_json_success($result);
}

// AJAX handler voor het ophalen van samples
add_action('wp_ajax_ksm_get_samples', 'ksm_ajax_get_samples');
add_action('wp_ajax_nopriv_ksm_get_samples', 'ksm_ajax_get_samples');

function ksm_ajax_get_samples() {
    check_ajax_referer('ksm_nonce', 'nonce');
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    if (!$category_id) {
        wp_send_json_error(__('Geen categorie geselecteerd', 'kleurstalen-manager'));
        return;
    }
    
    $args = array(
        'post_type' => 'kleurstaal',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => '_ksm_sort_order',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'kleurstaal_category',
                'field' => 'term_id',
                'terms' => $category_id
            )
        ),
        'meta_query' => array(
            array(
                'key' => '_ksm_active',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    $samples = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get sample images
            $sample_images = get_post_meta($post_id, '_ksm_sample_images', true);
            $image_urls = array();
            
            if (is_array($sample_images) && !empty($sample_images)) {
                foreach ($sample_images as $image_id) {
                    $url = wp_get_attachment_image_url($image_id, 'medium');
                    if ($url) {
                        $image_urls[] = $url;
                    }
                }
            }
            
            // Get display type
            $display_type = get_post_meta($post_id, '_ksm_display_type', true);
            if (!$display_type) {
                $display_type = 'colors'; // Default
            }
            
            // Get individual price
            $sample_price = get_post_meta($post_id, '_ksm_price', true);
            if (!$sample_price) {
                $sample_price = get_option('ksm_price_per_sample', 4);
            }
            
            $samples[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'sku' => get_post_meta($post_id, '_ksm_sku', true),
                'description' => get_post_meta($post_id, '_ksm_description', true),
                'colors' => get_post_meta($post_id, '_ksm_colors', true),
                'material' => get_post_meta($post_id, '_ksm_material', true),
                'popular' => get_post_meta($post_id, '_ksm_popular', true) === '1',
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'images' => $image_urls,
                'display_type' => $display_type,
                'price' => floatval($sample_price),
                'price_formatted' => wc_price($sample_price)
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(array(
        'samples' => $samples,
        'count' => count($samples)
    ));
}

// Voeg een dashboard widget toe
add_action('wp_dashboard_setup', function() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'ksm_dashboard_widget',
            '📦 Kleurstalen Manager Overzicht',
            function() {
                $total_samples = wp_count_posts('kleurstaal');
                $categories = wp_count_terms('kleurstaal_category');
                
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="text-align: center; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                        <div style="font-size: 32px; font-weight: bold; color: #333;">
                            <?php echo $total_samples->publish; ?>
                        </div>
                        <div style="color: #666;">Actieve Kleurstalen</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                        <div style="font-size: 32px; font-weight: bold; color: #333;">
                            <?php echo $categories; ?>
                        </div>
                        <div style="color: #666;">Categorieën</div>
                    </div>
                </div>
                <p style="margin-top: 15px;">
                    <a href="<?php echo admin_url('edit.php?post_type=kleurstaal'); ?>" class="button">
                        Beheer Kleurstalen
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=kleurstaal'); ?>" class="button">
                        Nieuwe Toevoegen
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ksm-statistics'); ?>" class="button">
                        Statistieken
                    </a>
                </p>
                <?php
            }
        );
    }
});
