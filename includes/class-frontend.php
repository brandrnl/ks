<?php
/**
 * Frontend Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Frontend {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'render_popup'));
    }
    
    /**
     * Render de popup HTML
     */
    public function render_popup() {
        ?>
        <!-- Kleurstalen Popup -->
        <div id="ksm-popup" class="ksm-popup" style="display: none;">
            <div class="ksm-popup-overlay"></div>
            <div class="ksm-popup-container">
                <button class="ksm-popup-close" aria-label="<?php _e('Sluiten', 'kleurstalen-manager'); ?>">
                    <span>&times;</span>
                </button>
                
                <!-- Stap 1: Categorie Selectie -->
                <div class="ksm-popup-step" id="ksm-step-category" data-step="1">
                    <div class="ksm-popup-header">
                        <h2><?php _e('Kies je materiaal', 'kleurstalen-manager'); ?></h2>
                        <p><?php _e('Selecteer het type jaloezie waarvan je kleurstalen wilt ontvangen', 'kleurstalen-manager'); ?></p>
                    </div>
                    
                    <div class="ksm-category-grid">
                        <?php $this->render_categories(); ?>
                    </div>
                </div>
                
                <!-- Stap 2: Kleurstalen Selectie -->
                <div class="ksm-popup-step" id="ksm-step-samples" data-step="2" style="display: none;">
                    <div class="ksm-popup-header">
                        <button class="ksm-back-button" data-target="category">
                            <span>&larr;</span> <?php _e('Terug', 'kleurstalen-manager'); ?>
                        </button>
                        <h2><span id="ksm-category-title"></span></h2>
                        <p><?php _e('Selecteer de gewenste kleurstalen', 'kleurstalen-manager'); ?></p>
                    </div>
                    
                    <div class="ksm-samples-grid" id="ksm-samples-container">
                        <!-- Samples worden hier dynamisch geladen -->
                    </div>
                    
                    <div class="ksm-popup-footer">
                        <div class="ksm-selection-info">
                            <span class="ksm-selected-count">0</span> <?php _e('geselecteerd', 'kleurstalen-manager'); ?>
                        </div>
                        <button class="ksm-btn ksm-btn-primary" id="ksm-add-to-cart" disabled>
                            <?php _e('Toevoegen aan winkelwagen', 'kleurstalen-manager'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Stap 3: Winkelwagen Overzicht -->
                <div class="ksm-popup-step" id="ksm-step-cart" data-step="3" style="display: none;">
                    <div class="ksm-popup-header">
                        <button class="ksm-back-button" data-target="samples">
                            <span>&larr;</span> <?php _e('Terug', 'kleurstalen-manager'); ?>
                        </button>
                        <h2><?php _e('Winkelwagen', 'kleurstalen-manager'); ?></h2>
                        <p><?php _e('Overzicht van je geselecteerde kleurstalen', 'kleurstalen-manager'); ?></p>
                    </div>
                    
                    <div class="ksm-cart-content" id="ksm-cart-container">
                        <!-- Cart items worden hier geladen -->
                    </div>
                    
                    <div class="ksm-popup-footer">
                        <div class="ksm-cart-total">
                            <span><?php _e('Totaal:', 'kleurstalen-manager'); ?></span>
                            <strong id="ksm-cart-total">€0,00</strong>
                        </div>
                        <div class="ksm-cart-actions">
                            <button class="ksm-btn ksm-btn-secondary" id="ksm-continue-shopping">
                                <?php _e('Verder winkelen', 'kleurstalen-manager'); ?>
                            </button>
                            <button class="ksm-btn ksm-btn-primary" id="ksm-checkout">
                                <?php _e('Afrekenen', 'kleurstalen-manager'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div class="ksm-loading" id="ksm-loading" style="display: none;">
                    <div class="ksm-spinner"></div>
                    <p><?php _e('Laden...', 'kleurstalen-manager'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Success Notification -->
        <div id="ksm-notification" class="ksm-notification" style="display: none;">
            <div class="ksm-notification-content">
                <span class="ksm-notification-icon">✓</span>
                <span class="ksm-notification-message"></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render categorieën in de popup
     */
    private function render_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'kleurstaal_category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($categories) || is_wp_error($categories)) {
            echo '<p>' . __('Geen categorieën beschikbaar', 'kleurstalen-manager') . '</p>';
            return;
        }
        
        foreach ($categories as $category) {
            $image = get_term_meta($category->term_id, 'category_image', true);
            $icon = get_term_meta($category->term_id, 'category_icon', true);
            ?>
            <div class="ksm-category-card" data-category="<?php echo esc_attr($category->term_id); ?>" data-name="<?php echo esc_attr($category->name); ?>">
                <?php if ($image) : ?>
                    <div class="ksm-category-image">
                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>">
                    </div>
                <?php elseif ($icon) : ?>
                    <div class="ksm-category-icon">
                        <i class="<?php echo esc_attr($icon); ?>"></i>
                    </div>
                <?php else : ?>
                    <div class="ksm-category-placeholder">
                        <?php echo $this->get_category_icon($category->slug); ?>
                    </div>
                <?php endif; ?>
                
                <h3><?php echo esc_html($category->name); ?></h3>
                
                <?php if ($category->description) : ?>
                    <p><?php echo esc_html($category->description); ?></p>
                <?php endif; ?>
                
                <?php
                $count = $this->get_active_samples_count($category->term_id);
                if ($count > 0) :
                ?>
                    <span class="ksm-category-count">
                        <?php printf(_n('%d kleurstaal', '%d kleurstalen', $count, 'kleurstalen-manager'), $count); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Get actieve kleurstalen count voor categorie
     */
    private function get_active_samples_count($category_id) {
        $args = array(
            'post_type' => 'kleurstaal',
            'post_status' => 'publish',
            'posts_per_page' => -1,
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
        return $query->found_posts;
    }
    
    /**
     * Get standaard icon voor categorie
     */
    private function get_category_icon($slug) {
        $icons = array(
            'aluminium' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>',
            'houten' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/><path d="M12 2v20"/><path d="M8 7l8 10"/><path d="M8 17l8-10"/></svg>',
            'bamboe' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5c0 2.5-2 2.5-2 5"/><path d="M7 5c0 2.5 2 2.5 2 5"/><path d="M17 14c0 2.5-2 2.5-2 5"/><path d="M7 14c0 2.5 2 2.5 2 5"/></svg>',
        );
        
        return isset($icons[$slug]) ? $icons[$slug] : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
    }
}