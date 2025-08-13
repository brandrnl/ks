<?php
/**
 * Plugin Name: Kleurstalen Manager Pro
 * Plugin URI: https://jaloezieopmaat.com
 * Description: Professionele kleurstalen manager met popup flow en volledige beheer mogelijkheden
 * Version: 2.0.1
 * Author: Jaloezie op Maat
 * Text Domain: kleurstalen-manager
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Voorkom directe toegang
if (!defined('ABSPATH')) {
    exit;
}

// Check of WooCommerce actief is
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Kleurstalen Manager vereist WooCommerce om te functioneren.</p></div>';
    });
    return;
}

// Plugin constanten
define('KSM_VERSION', '2.0.1');
define('KSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KSM_PLUGIN_BASENAME', plugin_basename(__FILE__));

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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Laad alle benodigde classes
        require_once KSM_PLUGIN_PATH . 'includes/class-core.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-post-types.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-admin.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-cart.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-order.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-email.php';
        require_once KSM_PLUGIN_PATH . 'includes/class-shortcodes.php';
    }
    
    private function init_hooks() {
        // Init action - BELANGRIJK: priority 0 voor vroege registratie
        add_action('init', array($this, 'init'), 0);
        
        // Activatie/Deactivatie hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin scripts en styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Frontend scripts en styles
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function init() {
        // Registreer post type en taxonomie DIRECT
        $this->register_post_type();
        $this->register_taxonomy();
        
        // Init andere modules
        $post_types = new KSM_Post_Types();
        $post_types->init();
        
        $shortcodes = new KSM_Shortcodes();
        $shortcodes->init();
        
        $ajax = new KSM_Ajax();
        $ajax->init();
        
        $cart = new KSM_Cart();
        $cart->init();
        
        $order = new KSM_Order();
        $order->init();
        
        $admin = new KSM_Admin();
        $frontend = new KSM_Frontend();
    }
    
    /**
     * Register post type direct in hoofdbestand voor zekerheid
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Kleurstalen',
            'singular_name' => 'Kleurstaal',
            'menu_name' => 'Kleurstalen',
            'add_new' => 'Nieuwe toevoegen',
            'add_new_item' => 'Nieuwe kleurstaal toevoegen',
            'edit_item' => 'Kleurstaal bewerken',
            'new_item' => 'Nieuwe kleurstaal',
            'view_item' => 'Bekijk kleurstaal',
            'search_items' => 'Zoek kleurstalen',
            'not_found' => 'Geen kleurstalen gevonden',
            'not_found_in_trash' => 'Geen kleurstalen in prullenbak',
            'all_items' => 'Alle kleurstalen',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We voegen het later toe aan WooCommerce menu
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
        );
        
        register_post_type('kleurstaal', $args);
    }
    
    /**
     * Register taxonomy direct in hoofdbestand voor zekerheid
     */
    public function register_taxonomy() {
        $labels = array(
            'name' => 'Categorieën',
            'singular_name' => 'Categorie',
            'search_items' => 'Zoek categorieën',
            'all_items' => 'Alle categorieën',
            'edit_item' => 'Bewerk categorie',
            'update_item' => 'Update categorie',
            'add_new_item' => 'Nieuwe categorie toevoegen',
            'new_item_name' => 'Nieuwe categorie naam',
            'menu_name' => 'Categorieën',
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
        );
        
        register_taxonomy('kleurstaal_category', array('kleurstaal'), $args);
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Voeg toe aan WooCommerce menu
        add_submenu_page(
            'woocommerce',
            'Kleurstalen',
            'Kleurstalen',
            'manage_woocommerce',
            'edit.php?post_type=kleurstaal'
        );
        
        add_submenu_page(
            'woocommerce',
            'Nieuwe kleurstaal',
            '→ Nieuwe kleurstaal',
            'manage_woocommerce',
            'post-new.php?post_type=kleurstaal'
        );
        
        add_submenu_page(
            'woocommerce',
            'Categorieën',
            '→ Categorieën',
            'manage_woocommerce',
            'edit-tags.php?taxonomy=kleurstaal_category&post_type=kleurstaal'
        );
    }
    
    public function admin_scripts($hook) {
        global $post_type;
        
        // Alleen laden op onze pagina's
        if ('kleurstaal' === $post_type || strpos($hook, 'kleurstalen') !== false) {
            // Basic admin styles
            wp_add_inline_style('wp-admin', '
                .ksm-meta-box { padding: 10px; }
                .ksm-color-row { margin: 10px 0; display: flex; gap: 10px; align-items: center; }
                .ksm-color-preview { margin-top: 20px; }
                .ksm-admin-preview { display: flex; gap: 5px; }
            ');
        }
    }
    
    public function frontend_scripts() {
        wp_enqueue_style('ksm-frontend', KSM_PLUGIN_URL . 'assets/css/frontend.css', array(), KSM_VERSION);
        wp_enqueue_script('ksm-frontend', KSM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), KSM_VERSION, true);
        
        wp_localize_script('ksm-frontend', 'ksm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ksm_nonce'),
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url(),
            'currency' => get_woocommerce_currency_symbol(),
            'price_per_sample' => get_option('ksm_price_per_sample', 4),
            'max_samples' => get_option('ksm_max_samples', 10),
            'strings' => array(
                'select_category' => 'Selecteer een categorie',
                'select_samples' => 'Selecteer kleurstalen',
                'added_to_cart' => 'Toegevoegd aan winkelwagen',
                'error' => 'Er ging iets mis',
                'loading' => 'Laden...',
                'close' => 'Sluiten'
            )
        ));
    }
    
    public function activate() {
        // Registreer post type en taxonomy voor flush
        $this->register_post_type();
        $this->register_taxonomy();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Maak database tabellen
        $this->create_tables();
        
        // Maak standaard categorieën
        $this->create_default_categories();
        
        // Maak sample product
        $core = new KSM_Core();
        $core->create_sample_product();
        
        // Set default options
        add_option('ksm_price_per_sample', 4);
        add_option('ksm_max_samples', 10);
        add_option('ksm_delivery_days', '3-4');
        add_option('ksm_discount_enabled', true);
        add_option('ksm_discount_validity_days', 90);
        
        // Maak een admin pagina voor eerste setup
        $this->create_setup_page();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'ksm_selections';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(100) DEFAULT '',
            samples text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function create_default_categories() {
        $categories = array(
            'aluminium' => array(
                'name' => 'Aluminium Jaloezieën',
                'description' => 'Kleurstalen voor aluminium jaloezieën'
            ),
            'houten' => array(
                'name' => 'Houten Jaloezieën',
                'description' => 'Kleurstalen voor houten jaloezieën'
            ),
            'bamboe' => array(
                'name' => 'Bamboe Jaloezieën',
                'description' => 'Kleurstalen voor bamboe jaloezieën'
            )
        );
        
        foreach ($categories as $slug => $category) {
            if (!term_exists($slug, 'kleurstaal_category')) {
                wp_insert_term(
                    $category['name'],
                    'kleurstaal_category',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
            }
        }
    }
    
    /**
     * Create setup page voor eerste configuratie
     */
    private function create_setup_page() {
        // Check of setup pagina al bestaat
        $setup_page = get_page_by_path('kleurstalen-setup');
        
        if (!$setup_page) {
            $page_id = wp_insert_post(array(
                'post_title' => 'Kleurstalen Setup',
                'post_name' => 'kleurstalen-setup',
                'post_content' => '[ksm_setup]',
                'post_status' => 'private',
                'post_type' => 'page',
                'post_author' => 1,
            ));
            
            update_option('ksm_setup_page_id', $page_id);
        }
    }
}

// Start de plugin
function ksm_init() {
    return Kleurstalen_Manager::get_instance();
}
add_action('plugins_loaded', 'ksm_init');

// Direct admin notice voor setup
add_action('admin_notices', function() {
    if (get_current_screen()->id !== 'edit-kleurstaal' && get_current_screen()->id !== 'kleurstaal') {
        return;
    }
    
    $categories = get_terms(array(
        'taxonomy' => 'kleurstaal_category',
        'hide_empty' => false
    ));
    
    if (empty($categories) || is_wp_error($categories)) {
        ?>
        <div class="notice notice-warning">
            <p><strong>Kleurstalen Manager:</strong> Er zijn nog geen categorieën aangemaakt.</p>
            <p>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=kleurstaal_category&post_type=kleurstaal'); ?>" class="button button-primary">
                    Categorieën aanmaken
                </a>
                <button type="button" class="button" onclick="createDefaultCategories()">
                    Standaard categorieën aanmaken
                </button>
            </p>
        </div>
        <script>
        function createDefaultCategories() {
            if (confirm('Wil je de standaard categorieën (Aluminium, Houten, Bamboe) aanmaken?')) {
                jQuery.post(ajaxurl, {
                    action: 'ksm_create_default_categories',
                    nonce: '<?php echo wp_create_nonce('ksm_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Er ging iets mis: ' + response.data);
                    }
                });
            }
        }
        </script>
        <?php
    }
});

// AJAX handler voor het aanmaken van standaard categorieën
add_action('wp_ajax_ksm_create_default_categories', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Geen rechten');
    }
    
    check_ajax_referer('ksm_admin', 'nonce');
    
    $categories = array(
        'aluminium' => 'Aluminium Jaloezieën',
        'houten' => 'Houten Jaloezieën',
        'bamboe' => 'Bamboe Jaloezieën'
    );
    
    foreach ($categories as $slug => $name) {
        if (!term_exists($slug, 'kleurstaal_category')) {
            wp_insert_term($name, 'kleurstaal_category', array('slug' => $slug));
        }
    }
    
    wp_send_json_success('Categorieën aangemaakt!');
});