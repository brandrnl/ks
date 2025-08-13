<?php
/**
 * AJAX Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Ajax {
    
    public function init() {
        // Frontend AJAX actions
        add_action('wp_ajax_ksm_get_samples', array($this, 'get_samples'));
        add_action('wp_ajax_nopriv_ksm_get_samples', array($this, 'get_samples'));
        
        add_action('wp_ajax_ksm_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_nopriv_ksm_add_to_cart', array($this, 'add_to_cart'));
        
        add_action('wp_ajax_ksm_get_cart', array($this, 'get_cart'));
        add_action('wp_ajax_nopriv_ksm_get_cart', array($this, 'get_cart'));
        
        add_action('wp_ajax_ksm_update_cart', array($this, 'update_cart'));
        add_action('wp_ajax_nopriv_ksm_update_cart', array($this, 'update_cart'));
        
        add_action('wp_ajax_ksm_remove_from_cart', array($this, 'remove_from_cart'));
        add_action('wp_ajax_nopriv_ksm_remove_from_cart', array($this, 'remove_from_cart'));
    }
    
    /**
     * Get samples voor een categorie
     */
    public function get_samples() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        if (!$category_id) {
            wp_send_json_error(__('Geen categorie geselecteerd', 'kleurstalen-manager'));
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
                
                $samples[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'sku' => get_post_meta($post_id, '_ksm_sku', true),
                    'description' => get_post_meta($post_id, '_ksm_description', true),
                    'colors' => get_post_meta($post_id, '_ksm_colors', true),
                    'material' => get_post_meta($post_id, '_ksm_material', true),
                    'popular' => get_post_meta($post_id, '_ksm_popular', true) === '1',
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium')
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'samples' => $samples,
            'count' => count($samples)
        ));
    }
    
    /**
     * Voeg samples toe aan cart
     */
    public function add_to_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        $samples = isset($_POST['samples']) ? $_POST['samples'] : array();
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        if (empty($samples)) {
            wp_send_json_error(__('Geen kleurstalen geselecteerd', 'kleurstalen-manager'));
        }
        
        // Valideer samples
        $valid_samples = array();
        foreach ($samples as $sample_id) {
            $sample_id = intval($sample_id);
            if (get_post_type($sample_id) === 'kleurstaal') {
                $valid_samples[] = $sample_id;
            }
        }
        
        if (empty($valid_samples)) {
            wp_send_json_error(__('Ongeldige kleurstalen', 'kleurstalen-manager'));
        }
        
        // Get of maak sample product
        $product_id = $this->get_or_create_sample_product();
        
        if (!$product_id) {
            wp_send_json_error(__('Kan product niet vinden', 'kleurstalen-manager'));
        }
        
        // Bereid cart item data voor
        $cart_item_data = array(
            'ksm_samples' => $valid_samples,
            'ksm_category' => $category_id,
            'ksm_is_sample' => true
        );
        
        // Check of er al samples in cart zitten
        $existing_key = $this->find_sample_cart_item();
        
        if ($existing_key) {
            // Update bestaande
            $cart_item = WC()->cart->get_cart_item($existing_key);
            $existing_samples = isset($cart_item['ksm_samples']) ? $cart_item['ksm_samples'] : array();
            
            // Merge samples
            $merged_samples = array_unique(array_merge($existing_samples, $valid_samples));
            
            // Update cart item
            WC()->cart->cart_contents[$existing_key]['ksm_samples'] = $merged_samples;
            WC()->cart->set_session();
            
            $message = sprintf(
                __('%d kleurstalen toegevoegd aan winkelwagen', 'kleurstalen-manager'),
                count($valid_samples)
            );
        } else {
            // Voeg nieuw item toe
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
            
            if (!$cart_item_key) {
                wp_send_json_error(__('Kon niet toevoegen aan winkelwagen', 'kleurstalen-manager'));
            }
            
            $message = sprintf(
                __('%d kleurstalen toegevoegd aan winkelwagen', 'kleurstalen-manager'),
                count($valid_samples)
            );
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'samples_count' => count($valid_samples)
        ));
    }
    
    /**
     * Get cart contents
     */
    public function get_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        $cart_items = array();
        $total = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
                $samples = isset($cart_item['ksm_samples']) ? $cart_item['ksm_samples'] : array();
                
                foreach ($samples as $sample_id) {
                    $sample = get_post($sample_id);
                    if ($sample) {
                        $colors = get_post_meta($sample_id, '_ksm_colors', true);
                        $cart_items[] = array(
                            'id' => $sample_id,
                            'title' => $sample->post_title,
                            'sku' => get_post_meta($sample_id, '_ksm_sku', true),
                            'colors' => $colors,
                            'cart_key' => $cart_item_key
                        );
                    }
                }
                
                $total += $cart_item['line_subtotal'];
            }
        }
        
        wp_send_json_success(array(
            'items' => $cart_items,
            'total' => wc_price($total),
            'raw_total' => $total,
            'count' => count($cart_items)
        ));
    }
    
    /**
     * Update cart
     */
    public function update_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        $samples = isset($_POST['samples']) ? $_POST['samples'] : array();
        
        if (!$cart_key || !WC()->cart->get_cart_item($cart_key)) {
            wp_send_json_error(__('Cart item niet gevonden', 'kleurstalen-manager'));
        }
        
        // Update samples
        WC()->cart->cart_contents[$cart_key]['ksm_samples'] = array_map('intval', $samples);
        WC()->cart->set_session();
        
        wp_send_json_success(array(
            'message' => __('Winkelwagen bijgewerkt', 'kleurstalen-manager')
        ));
    }
    
    /**
     * Remove from cart
     */
    public function remove_from_cart() {
        check_ajax_referer('ksm_nonce', 'nonce');
        
        $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
        
        if (!$sample_id) {
            wp_send_json_error(__('Geen sample ID', 'kleurstalen-manager'));
        }
        
        // Vind cart item met deze sample
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ksm_samples']) && in_array($sample_id, $cart_item['ksm_samples'])) {
                // Verwijder sample uit array
                $samples = array_diff($cart_item['ksm_samples'], array($sample_id));
                
                if (empty($samples)) {
                    // Verwijder hele cart item als geen samples meer
                    WC()->cart->remove_cart_item($cart_item_key);
                } else {
                    // Update samples array
                    WC()->cart->cart_contents[$cart_item_key]['ksm_samples'] = $samples;
                    WC()->cart->set_session();
                }
                
                wp_send_json_success(array(
                    'message' => __('Kleurstaal verwijderd', 'kleurstalen-manager')
                ));
            }
        }
        
        wp_send_json_error(__('Sample niet gevonden in winkelwagen', 'kleurstalen-manager'));
    }
    
    /**
     * Find existing sample cart item
     */
    private function find_sample_cart_item() {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
                return $cart_item_key;
            }
        }
        return false;
    }
    
    /**
     * Get or create sample product
     */
    private function get_or_create_sample_product() {
        $product_id = get_option('ksm_sample_product_id');
        
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }
        
        // Maak nieuw product
        $product = new WC_Product_Simple();
        $product->set_name(__('Kleurstalen Set', 'kleurstalen-manager'));
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        
        $price = get_option('ksm_price_per_sample', 4);
        $product->set_price($price);
        $product->set_regular_price($price);
        
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->set_virtual(false);
        $product->set_sold_individually(true);
        
        $delivery = get_option('ksm_delivery_days', '3-4');
        $description = sprintf(
            __('Kleurstalen set voor jaloezieën. Levering binnen %s werkdagen.', 'kleurstalen-manager'),
            $delivery
        );
        
        if (get_option('ksm_discount_enabled', true)) {
            $description .= ' ' . __('U ontvangt een kortingscode ter waarde van het aankoopbedrag bij uw volgende bestelling.', 'kleurstalen-manager');
        }
        
        $product->set_description($description);
        
        $product_id = $product->save();
        
        update_option('ksm_sample_product_id', $product_id);
        
        return $product_id;
    }
}