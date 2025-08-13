<?php
/**
 * Cart Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Cart {
    
    public function init() {
        // Cart item display
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_samples'), 10, 2);
        
        // Cart item price
        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price'), 10, 3);
        
        // Cart item quantity
        add_filter('woocommerce_cart_item_quantity', array($this, 'cart_item_quantity'), 10, 3);
        
        // Cart item remove link
        add_filter('woocommerce_cart_item_remove_link', array($this, 'cart_item_remove_link'), 10, 2);
        
        // Cart item thumbnail
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 10, 3);
        
        // Prevent quantity change
        add_filter('woocommerce_cart_item_quantity', array($this, 'disable_quantity_change'), 10, 3);
    }
    
    /**
     * Display cart item samples
     */
    public function display_cart_item_samples($item_data, $cart_item) {
        if (!isset($cart_item['ksm_samples'])) {
            return $item_data;
        }
        
        $samples = array();
        foreach ($cart_item['ksm_samples'] as $sample_id) {
            $sample = get_post($sample_id);
            if ($sample) {
                $samples[] = $sample->post_title;
            }
        }
        
        if (!empty($samples)) {
            $item_data[] = array(
                'key' => __('Geselecteerde kleurstalen', 'kleurstalen-manager'),
                'value' => implode(', ', $samples),
                'display' => implode('<br>', $samples)
            );
            
            $item_data[] = array(
                'key' => __('Aantal kleurstalen', 'kleurstalen-manager'),
                'value' => count($samples)
            );
            
            $delivery = get_option('ksm_delivery_days', '3-4');
            $item_data[] = array(
                'key' => __('Levertijd', 'kleurstalen-manager'),
                'value' => sprintf(__('%s werkdagen', 'kleurstalen-manager'), $delivery)
            );
        }
        
        return $item_data;
    }
    
    /**
     * Cart item price
     */
    public function cart_item_price($price, $cart_item, $cart_item_key) {
        if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
            $sample_price = get_option('ksm_price_per_sample', 4);
            return wc_price($sample_price);
        }
        return $price;
    }
    
    /**
     * Cart item quantity - always 1 for samples
     */
    public function cart_item_quantity($product_quantity, $cart_item_key, $cart_item) {
        if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
            return sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
        }
        return $product_quantity;
    }
    
    /**
     * Custom remove link
     */
    public function cart_item_remove_link($link, $cart_item_key) {
        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        
        if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
            return sprintf(
                '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">×</a>',
                esc_url(wc_get_cart_remove_url($cart_item_key)),
                __('Verwijder kleurstalen set', 'kleurstalen-manager'),
                esc_attr($cart_item['product_id']),
                esc_attr($cart_item_key),
                esc_attr($cart_item['data']->get_sku())
            );
        }
        
        return $link;
    }
    
    /**
     * Cart item thumbnail
     */
    public function cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['ksm_samples']) && !empty($cart_item['ksm_samples'])) {
            $colors = array();
            
            // Get first 4 sample colors
            $count = 0;
            foreach ($cart_item['ksm_samples'] as $sample_id) {
                if ($count >= 4) break;
                
                $sample_colors = get_post_meta($sample_id, '_ksm_colors', true);
                if (is_array($sample_colors) && !empty($sample_colors)) {
                    $colors[] = $sample_colors[0];
                    $count++;
                }
            }
            
            if (!empty($colors)) {
                $thumbnail = '<div class="ksm-cart-thumbnail" style="width: 60px; height: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 2px;">';
                foreach ($colors as $index => $color) {
                    $thumbnail .= '<div style="background: ' . esc_attr($color) . '; border-radius: 4px;"></div>';
                }
                // Fill empty spots
                for ($i = count($colors); $i < 4; $i++) {
                    $thumbnail .= '<div style="background: #f0f0f0; border-radius: 4px;"></div>';
                }
                $thumbnail .= '</div>';
            }
        }
        
        return $thumbnail;
    }
    
    /**
     * Disable quantity change for samples
     */
    public function disable_quantity_change($product_quantity, $cart_item_key, $cart_item) {
        if (isset($cart_item['ksm_is_sample']) && $cart_item['ksm_is_sample']) {
            return '<span class="quantity">1</span>';
        }
        return $product_quantity;
    }
}