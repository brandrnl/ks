<?php
/**
 * Shortcodes Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Shortcodes {
    
    public function init() {
        add_shortcode('kleurstalen_knop', array($this, 'render_button'));
        add_shortcode('kleurstalen_grid', array($this, 'render_grid'));
        add_shortcode('kleurstalen_categorie', array($this, 'render_category'));
    }
    
    /**
     * Render kleurstalen knop
     * [kleurstalen_knop text="Bestel kleurstalen" class="custom-class" style="primary"]
     */
    public function render_button($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Bestel kleurstalen', 'kleurstalen-manager'),
            'class' => '',
            'style' => 'primary', // primary, secondary, outline
            'size' => 'medium', // small, medium, large
            'align' => 'left', // left, center, right
            'category' => '', // Pre-select category
            'icon' => false
        ), $atts);
        
        $button_classes = array('ksm-button', 'ksm-open-popup');
        
        // Style
        if ($atts['style']) {
            $button_classes[] = 'ksm-button-' . $atts['style'];
        }
        
        // Size
        if ($atts['size']) {
            $button_classes[] = 'ksm-button-' . $atts['size'];
        }
        
        // Custom class
        if ($atts['class']) {
            $button_classes[] = $atts['class'];
        }
        
        // Data attributes
        $data_attrs = '';
        if ($atts['category']) {
            $data_attrs = 'data-category="' . esc_attr($atts['category']) . '"';
        }
        
        // Alignment wrapper
        $alignment_class = 'ksm-button-align-' . $atts['align'];
        
        ob_start();
        ?>
        <div class="ksm-button-wrapper <?php echo esc_attr($alignment_class); ?>">
            <button type="button" 
                    class="<?php echo esc_attr(implode(' ', $button_classes)); ?>"
                    <?php echo $data_attrs; ?>>
                <?php if ($atts['icon']) : ?>
                    <span class="ksm-button-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </span>
                <?php endif; ?>
                <span class="ksm-button-text"><?php echo esc_html($atts['text']); ?></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render kleurstalen grid
     * [kleurstalen_grid category="aluminium" columns="4" show_sku="true"]
     */
    public function render_grid($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => 4,
            'limit' => -1,
            'orderby' => 'sort_order',
            'order' => 'ASC',
            'show_sku' => false,
            'show_description' => false,
            'show_select' => false,
            'popular_only' => false
        ), $atts);
        
        $args = array(
            'post_type' => 'kleurstaal',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'order' => $atts['order'],
            'meta_query' => array(
                array(
                    'key' => '_ksm_active',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        // Orderby
        if ($atts['orderby'] === 'sort_order') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_ksm_sort_order';
        } else {
            $args['orderby'] = $atts['orderby'];
        }
        
        // Category filter
        if ($atts['category']) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'kleurstaal_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        // Popular only
        if ($atts['popular_only']) {
            $args['meta_query'][] = array(
                'key' => '_ksm_popular',
                'value' => '1',
                'compare' => '='
            );
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('Geen kleurstalen gevonden', 'kleurstalen-manager') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="ksm-grid ksm-grid-<?php echo esc_attr($atts['columns']); ?>">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $colors = get_post_meta($post_id, '_ksm_colors', true);
                $sku = get_post_meta($post_id, '_ksm_sku', true);
                $description = get_post_meta($post_id, '_ksm_description', true);
                $popular = get_post_meta($post_id, '_ksm_popular', true) === '1';
                ?>
                <div class="ksm-grid-item" data-sample-id="<?php echo $post_id; ?>">
                    <?php if ($popular) : ?>
                        <span class="ksm-badge ksm-badge-popular"><?php _e('Populair', 'kleurstalen-manager'); ?></span>
                    <?php endif; ?>
                    
                    <div class="ksm-sample-preview">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php elseif (is_array($colors) && !empty($colors)) : ?>
                            <div class="ksm-color-preview">
                                <?php if (count($colors) > 1) : ?>
                                    <div class="ksm-color-gradient" style="background: linear-gradient(135deg, <?php echo implode(', ', $colors); ?>);"></div>
                                <?php else : ?>
                                    <div class="ksm-color-solid" style="background: <?php echo $colors[0]; ?>;"></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ksm-sample-info">
                        <h4><?php the_title(); ?></h4>
                        
                        <?php if ($atts['show_sku'] && $sku) : ?>
                            <span class="ksm-sku"><?php echo esc_html($sku); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_description'] && $description) : ?>
                            <p class="ksm-description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_select']) : ?>
                            <button class="ksm-select-sample" data-sample-id="<?php echo $post_id; ?>">
                                <?php _e('Selecteer', 'kleurstalen-manager'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render specifieke categorie met samples
     * [kleurstalen_categorie slug="aluminium" title="true"]
     */
    public function render_category($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'id' => '',
            'title' => true,
            'description' => true,
            'columns' => 4,
            'limit' => -1,
            'button' => true,
            'button_text' => __('Bekijk alle kleurstalen', 'kleurstalen-manager')
        ), $atts);
        
        // Get category
        if ($atts['id']) {
            $category = get_term($atts['id'], 'kleurstaal_category');
        } elseif ($atts['slug']) {
            $category = get_term_by('slug', $atts['slug'], 'kleurstaal_category');
        } else {
            return '<p>' . __('Geen categorie opgegeven', 'kleurstalen-manager') . '</p>';
        }
        
        if (!$category || is_wp_error($category)) {
            return '<p>' . __('Categorie niet gevonden', 'kleurstalen-manager') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="ksm-category-section">
            <?php if ($atts['title']) : ?>
                <h3 class="ksm-category-title"><?php echo esc_html($category->name); ?></h3>
            <?php endif; ?>
            
            <?php if ($atts['description'] && $category->description) : ?>
                <p class="ksm-category-description"><?php echo esc_html($category->description); ?></p>
            <?php endif; ?>
            
            <?php
            // Show samples grid
            echo $this->render_grid(array(
                'category' => $category->slug,
                'columns' => $atts['columns'],
                'limit' => $atts['limit']
            ));
            ?>
            
            <?php if ($atts['button']) : ?>
                <div class="ksm-category-button">
                    <button class="ksm-button ksm-button-primary ksm-open-popup" 
                            data-category="<?php echo esc_attr($category->term_id); ?>">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}