<?php
/**
 * Order Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Order {
    
    public function init() {
        // Save order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_meta'), 10, 4);
        
        // Order complete
        add_action('woocommerce_order_status_completed', array($this, 'order_completed'));
        add_action('woocommerce_order_status_processing', array($this, 'order_completed'));
        
        // Display order item meta
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'display_meta_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'display_meta_value'), 10, 3);
    }
    
    /**
     * Save order item meta
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['ksm_samples'])) {
            $samples = array();
            foreach ($values['ksm_samples'] as $sample_id) {
                $sample = get_post($sample_id);
                if ($sample) {
                    $sku = get_post_meta($sample_id, '_ksm_sku', true);
                    $samples[] = $sample->post_title . ($sku ? ' (' . $sku . ')' : '');
                }
            }
            
            $item->add_meta_data('_ksm_samples', $values['ksm_samples']);
            $item->add_meta_data(__('Kleurstalen', 'kleurstalen-manager'), implode(', ', $samples));
            $item->add_meta_data('_ksm_is_sample', true);
            
            // Track selection count
            foreach ($values['ksm_samples'] as $sample_id) {
                $count = get_post_meta($sample_id, '_ksm_selection_count', true);
                update_post_meta($sample_id, '_ksm_selection_count', intval($count) + 1);
            }
        }
    }
    
    /**
     * Order completed
     */
    public function order_completed($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if order contains samples
        $has_samples = false;
        $sample_items = array();
        
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_ksm_is_sample')) {
                $has_samples = true;
                $sample_items[] = $item;
            }
        }
        
        if (!$has_samples) {
            return;
        }
        
        // Generate discount code if enabled
        if (get_option('ksm_discount_enabled', true)) {
            $this->generate_discount_code($order, $sample_items);
        }
        
        // Send confirmation email if enabled
        if (get_option('ksm_send_confirmation_email', true)) {
            $email = new KSM_Email();
            $email->send_sample_confirmation($order, $sample_items);
        }
    }
    
    /**
     * Generate discount code
     */
    private function generate_discount_code($order, $sample_items) {
        // Generate unique code
        $code = 'KLEUR' . strtoupper(substr(md5($order->get_id() . time()), 0, 6));
        
        // Calculate discount amount
        $discount_amount = 0;
        foreach ($sample_items as $item) {
            $discount_amount += $item->get_total();
        }
        
        // Create coupon
        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount($discount_amount);
        $coupon->set_individual_use(false);
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);
        
        // Set email restriction
        $coupon->set_email_restrictions(array($order->get_billing_email()));
        
        // Set expiry date
        $validity_days = get_option('ksm_discount_validity_days', 90);
        $expiry_date = date('Y-m-d', strtotime('+' . $validity_days . ' days'));
        $coupon->set_date_expires($expiry_date);
        
        // Set description
        $coupon->set_description(
            sprintf(
                __('Kortingscode voor kleurstalen bestelling #%d', 'kleurstalen-manager'),
                $order->get_id()
            )
        );
        
        // Add meta to identify as KSM generated
        $coupon_id = $coupon->save();
        update_post_meta($coupon_id, '_ksm_generated', '1');
        update_post_meta($coupon_id, '_ksm_order_id', $order->get_id());
        
        // Save code to order meta
        $order->update_meta_data('_ksm_discount_code', $code);
        $order->update_meta_data('_ksm_discount_amount', $discount_amount);
        $order->save();
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Kortingscode %s aangemaakt ter waarde van %s', 'kleurstalen-manager'),
                $code,
                wc_price($discount_amount)
            )
        );
        
        // Send discount code email
        $email = new KSM_Email();
        $email->send_discount_code($order, $code, $discount_amount, $expiry_date);
    }
    
    /**
     * Display meta key
     */
    public function display_meta_key($display_key, $meta, $order_item) {
        if ($meta->key === '_ksm_samples') {
            return __('Kleurstalen IDs', 'kleurstalen-manager');
        }
        return $display_key;
    }
    
    /**
     * Display meta value
     */
    public function display_meta_value($display_value, $meta, $order_item) {
        if ($meta->key === '_ksm_samples' && is_array($meta->value)) {
            $samples = array();
            foreach ($meta->value as $sample_id) {
                $sample = get_post($sample_id);
                if ($sample) {
                    $samples[] = $sample->post_title;
                }
            }
            return implode(', ', $samples);
        }
        return $display_value;
    }
}