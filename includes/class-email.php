<?php
/**
 * Email Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Email {
    
    /**
     * Get email headers
     */
    private function get_headers() {
        $from_name = get_option('ksm_email_from_name', get_bloginfo('name'));
        $from_email = get_option('ksm_email_from_address', get_option('admin_email'));
        
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
    }
    
    /**
     * Send sample confirmation email
     */
    public function send_sample_confirmation($order, $sample_items) {
        $to = $order->get_billing_email();
        $subject = sprintf(
            __('Bevestiging kleurstalen bestelling #%d', 'kleurstalen-manager'),
            $order->get_id()
        );
        
        $samples = array();
        foreach ($sample_items as $item) {
            $sample_ids = $item->get_meta('_ksm_samples');
            if (is_array($sample_ids)) {
                foreach ($sample_ids as $sample_id) {
                    $sample = get_post($sample_id);
                    if ($sample) {
                        $samples[] = $sample->post_title;
                    }
                }
            }
        }
        
        $delivery = get_option('ksm_delivery_days', '3-4');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f5f5f5; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #e0e0e0; }
                .samples-list { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .sample-item { padding: 10px; border-bottom: 1px solid #e0e0e0; }
                .sample-item:last-child { border-bottom: none; }
                .button { display: inline-block; padding: 12px 30px; background: #333; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Bedankt voor je bestelling!', 'kleurstalen-manager'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Beste %s,', 'kleurstalen-manager'), $order->get_billing_first_name()); ?></p>
                    
                    <p><?php _e('We hebben je bestelling voor kleurstalen ontvangen en deze wordt nu verwerkt.', 'kleurstalen-manager'); ?></p>
                    
                    <div class="samples-list">
                        <h3><?php _e('Geselecteerde kleurstalen:', 'kleurstalen-manager'); ?></h3>
                        <?php foreach ($samples as $sample) : ?>
                            <div class="sample-item">• <?php echo esc_html($sample); ?></div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p>
                        <strong><?php _e('Levertijd:', 'kleurstalen-manager'); ?></strong> 
                        <?php printf(__('%s werkdagen', 'kleurstalen-manager'), $delivery); ?>
                    </p>
                    
                    <p><?php _e('Je ontvangt de kleurstalen op het volgende adres:', 'kleurstalen-manager'); ?></p>
                    <p>
                        <?php echo $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address(); ?>
                    </p>
                    
                    <?php if (get_option('ksm_discount_enabled', true)) : ?>
                        <p style="background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;">
                            <strong><?php _e('Let op:', 'kleurstalen-manager'); ?></strong> 
                            <?php _e('Na voltooiing van je bestelling ontvang je een kortingscode ter waarde van het aankoopbedrag die je kunt gebruiken bij je volgende bestelling!', 'kleurstalen-manager'); ?>
                        </p>
                    <?php endif; ?>
                    
                    <center>
                        <a href="<?php echo home_url(); ?>" class="button">
                            <?php _e('Bekijk onze jaloezieën', 'kleurstalen-manager'); ?>
                        </a>
                    </center>
                </div>
                
                <div class="footer">
                    <p><?php _e('Heb je vragen? Neem contact met ons op.', 'kleurstalen-manager'); ?></p>
                    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $this->get_headers());
    }
    
    /**
     * Send discount code email
     */
    public function send_discount_code($order, $code, $amount, $expiry) {
        $to = $order->get_billing_email();
        $subject = __('Je kortingscode is klaar!', 'kleurstalen-manager');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; border-radius: 10px 10px 0 0; color: white; }
                .content { background: white; padding: 30px; border: 1px solid #e0e0e0; }
                .discount-box { background: #f0fdf4; border: 2px dashed #4CAF50; padding: 30px; text-align: center; border-radius: 10px; margin: 30px 0; }
                .discount-code { font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 2px; margin: 20px 0; }
                .button { display: inline-block; padding: 15px 40px; background: #4CAF50; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; font-size: 18px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 <?php _e('Je kortingscode is klaar!', 'kleurstalen-manager'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Beste %s,', 'kleurstalen-manager'), $order->get_billing_first_name()); ?></p>
                    
                    <p><?php _e('Bedankt voor het bestellen van onze kleurstalen! Als dank voor je interesse ontvang je onderstaande kortingscode.', 'kleurstalen-manager'); ?></p>
                    
                    <div class="discount-box">
                        <p><?php _e('Jouw persoonlijke kortingscode:', 'kleurstalen-manager'); ?></p>
                        <div class="discount-code"><?php echo $code; ?></div>
                        <p>
                            <?php _e('Waarde:', 'kleurstalen-manager'); ?> <strong><?php echo wc_price($amount); ?></strong><br>
                            <?php _e('Geldig tot:', 'kleurstalen-manager'); ?> <strong><?php echo date_i18n(get_option('date_format'), strtotime($expiry)); ?></strong>
                        </p>
                    </div>
                    
                    <h3><?php _e('Hoe gebruik je de kortingscode?', 'kleurstalen-manager'); ?></h3>
                    <ol>
                        <li><?php _e('Kies je favoriete jaloezieën uit ons assortiment', 'kleurstalen-manager'); ?></li>
                        <li><?php _e('Voeg ze toe aan je winkelwagen', 'kleurstalen-manager'); ?></li>
                        <li><?php _e('Vul de kortingscode in bij het afrekenen', 'kleurstalen-manager'); ?></li>
                        <li><?php _e('De korting wordt automatisch verrekend!', 'kleurstalen-manager'); ?></li>
                    </ol>
                    
                    <center>
                        <a href="<?php echo home_url('/shop'); ?>" class="button">
                            <?php _e('Shop nu met korting', 'kleurstalen-manager'); ?>
                        </a>
                    </center>
                    
                    <p style="margin-top: 30px;">
                        <small><?php _e('Deze kortingscode is eenmalig te gebruiken en gekoppeld aan je e-mailadres.', 'kleurstalen-manager'); ?></small>
                    </p>
                </div>
                
                <div class="footer">
                    <p><?php _e('Veel plezier met je nieuwe jaloezieën!', 'kleurstalen-manager'); ?></p>
                    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $this->get_headers());
    }
}