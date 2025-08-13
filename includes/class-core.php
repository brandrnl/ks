<?php
/**
 * Core Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Core {
    
    /**
     * Create sample product
     */
    public function create_sample_product() {
        $product_id = get_option('ksm_sample_product_id');
        
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }
        
        // Create new product
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
        $product->set_short_description($description);
        
        // Set SKU
        $product->set_sku('KSM-SAMPLES');
        
        $product_id = $product->save();
        
        update_option('ksm_sample_product_id', $product_id);
        
        return $product_id;
    }
    
    /**
     * Get sample product
     */
    public function get_sample_product() {
        $product_id = get_option('ksm_sample_product_id');
        
        if (!$product_id) {
            $product_id = $this->create_sample_product();
        }
        
        return wc_get_product($product_id);
    }
    
    /**
     * Update sample product price
     */
    public function update_sample_product_price($price) {
        $product = $this->get_sample_product();
        
        if ($product) {
            $product->set_price($price);
            $product->set_regular_price($price);
            $product->save();
        }
    }
}