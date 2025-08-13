<?php
/**
 * Post Types Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Post_Types {
    
    public function init() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Custom columns
        add_filter('manage_kleurstaal_posts_columns', array($this, 'add_columns'));
        add_action('manage_kleurstaal_posts_custom_column', array($this, 'render_columns'), 10, 2);
        add_filter('manage_edit-kleurstaal_sortable_columns', array($this, 'sortable_columns'));
        
        // Add custom admin menu items
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function register_post_types() {
        $labels = array(
            'name' => __('Kleurstalen', 'kleurstalen-manager'),
            'singular_name' => __('Kleurstaal', 'kleurstalen-manager'),
            'menu_name' => __('Kleurstalen', 'kleurstalen-manager'),
            'name_admin_bar' => __('Kleurstaal', 'kleurstalen-manager'),
            'add_new' => __('Nieuwe toevoegen', 'kleurstalen-manager'),
            'add_new_item' => __('Nieuwe kleurstaal toevoegen', 'kleurstalen-manager'),
            'edit_item' => __('Kleurstaal bewerken', 'kleurstalen-manager'),
            'new_item' => __('Nieuwe kleurstaal', 'kleurstalen-manager'),
            'view_item' => __('Bekijk kleurstaal', 'kleurstalen-manager'),
            'view_items' => __('Bekijk kleurstalen', 'kleurstalen-manager'),
            'search_items' => __('Zoek kleurstalen', 'kleurstalen-manager'),
            'not_found' => __('Geen kleurstalen gevonden', 'kleurstalen-manager'),
            'not_found_in_trash' => __('Geen kleurstalen in prullenbak', 'kleurstalen-manager'),
            'all_items' => __('Alle kleurstalen', 'kleurstalen-manager'),
            'archives' => __('Kleurstalen archief', 'kleurstalen-manager'),
            'attributes' => __('Kleurstaal attributen', 'kleurstalen-manager'),
            'insert_into_item' => __('Voeg toe aan kleurstaal', 'kleurstalen-manager'),
            'uploaded_to_this_item' => __('Geüpload naar deze kleurstaal', 'kleurstalen-manager'),
            'filter_items_list' => __('Filter kleurstalen lijst', 'kleurstalen-manager'),
            'items_list_navigation' => __('Kleurstalen lijst navigatie', 'kleurstalen-manager'),
            'items_list' => __('Kleurstalen lijst', 'kleurstalen-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,  // Changed to true for better visibility
            'publicly_queryable' => false,  // But don't show on frontend
            'show_ui' => true,
            'show_in_menu' => true,  // Changed to true first, we'll add to WooCommerce later
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'menu_position' => 56,  // Position after WooCommerce
            'menu_icon' => 'dashicons-color-picker',
            'capability_type' => 'post',  // Changed from 'product' to 'post' for compatibility
            'capabilities' => array(
                'edit_post' => 'manage_woocommerce',
                'read_post' => 'manage_woocommerce',
                'delete_post' => 'manage_woocommerce',
                'edit_posts' => 'manage_woocommerce',
                'edit_others_posts' => 'manage_woocommerce',
                'publish_posts' => 'manage_woocommerce',
                'read_private_posts' => 'manage_woocommerce',
                'create_posts' => 'manage_woocommerce',
            ),
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array('title', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'can_export' => true,
            'show_in_rest' => false,
        );
        
        register_post_type('kleurstaal', $args);
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // First, let's move the post type under WooCommerce if WooCommerce is active
        if (class_exists('WooCommerce')) {
            global $menu, $submenu;
            
            // Remove the standalone menu if it exists
            remove_menu_page('edit.php?post_type=kleurstaal');
            
            // Add as submenu under WooCommerce
            add_submenu_page(
                'woocommerce',
                __('Kleurstalen', 'kleurstalen-manager'),
                __('Kleurstalen', 'kleurstalen-manager'),
                'manage_woocommerce',
                'edit.php?post_type=kleurstaal',
                null,
                1
            );
            
            // Add "Nieuwe kleurstaal" submenu
            add_submenu_page(
                'woocommerce',
                __('Nieuwe kleurstaal', 'kleurstalen-manager'),
                __('→ Nieuwe kleurstaal', 'kleurstalen-manager'),
                'manage_woocommerce',
                'post-new.php?post_type=kleurstaal',
                null,
                2
            );
            
            // Add categories submenu
            add_submenu_page(
                'woocommerce',
                __('Kleurstaal Categorieën', 'kleurstalen-manager'),
                __('→ Categorieën', 'kleurstalen-manager'),
                'manage_woocommerce',
                'edit-tags.php?taxonomy=kleurstaal_category&post_type=kleurstaal',
                null,
                3
            );
        }
    }
    
    public function register_taxonomies() {
        // Categorie taxonomie
        $labels = array(
            'name' => __('Kleurstaal Categorieën', 'kleurstalen-manager'),
            'singular_name' => __('Categorie', 'kleurstalen-manager'),
            'search_items' => __('Zoek categorieën', 'kleurstalen-manager'),
            'all_items' => __('Alle categorieën', 'kleurstalen-manager'),
            'parent_item' => __('Hoofd categorie', 'kleurstalen-manager'),
            'parent_item_colon' => __('Hoofd categorie:', 'kleurstalen-manager'),
            'edit_item' => __('Bewerk categorie', 'kleurstalen-manager'),
            'update_item' => __('Update categorie', 'kleurstalen-manager'),
            'add_new_item' => __('Nieuwe categorie toevoegen', 'kleurstalen-manager'),
            'new_item_name' => __('Nieuwe categorie naam', 'kleurstalen-manager'),
            'menu_name' => __('Categorieën', 'kleurstalen-manager'),
            'not_found' => __('Geen categorieën gevonden', 'kleurstalen-manager'),
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'query_var' => true,
            'rewrite' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_woocommerce',
                'edit_terms' => 'manage_woocommerce',
                'delete_terms' => 'manage_woocommerce',
                'assign_terms' => 'manage_woocommerce',
            ),
            'show_in_rest' => false,
        );
        
        register_taxonomy('kleurstaal_category', array('kleurstaal'), $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'kleurstaal_details',
            __('Kleurstaal Details', 'kleurstalen-manager'),
            array($this, 'render_details_meta_box'),
            'kleurstaal',
            'normal',
            'high'
        );
        
        add_meta_box(
            'kleurstaal_colors',
            __('Kleuren', 'kleurstalen-manager'),
            array($this, 'render_colors_meta_box'),
            'kleurstaal',
            'normal',
            'high'
        );
        
        add_meta_box(
            'kleurstaal_settings',
            __('Instellingen', 'kleurstalen-manager'),
            array($this, 'render_settings_meta_box'),
            'kleurstaal',
            'side',
            'default'
        );
        
        // Add a help box
        add_meta_box(
            'kleurstaal_help',
            __('Hulp', 'kleurstalen-manager'),
            array($this, 'render_help_meta_box'),
            'kleurstaal',
            'side',
            'low'
        );
    }
    
    public function render_details_meta_box($post) {
        wp_nonce_field('kleurstaal_meta_box', 'kleurstaal_meta_box_nonce');
        
        $sku = get_post_meta($post->ID, '_ksm_sku', true);
        $description = get_post_meta($post->ID, '_ksm_description', true);
        $material = get_post_meta($post->ID, '_ksm_material', true);
        ?>
        <div class="ksm-meta-box">
            <p>
                <label for="ksm_sku"><strong><?php _e('SKU/Artikelnummer:', 'kleurstalen-manager'); ?></strong></label><br>
                <input type="text" id="ksm_sku" name="ksm_sku" value="<?php echo esc_attr($sku); ?>" class="regular-text" />
                <span class="description"><?php _e('Uniek artikelnummer voor deze kleurstaal', 'kleurstalen-manager'); ?></span>
            </p>
            
            <p>
                <label for="ksm_description"><strong><?php _e('Beschrijving:', 'kleurstalen-manager'); ?></strong></label><br>
                <textarea id="ksm_description" name="ksm_description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                <span class="description"><?php _e('Korte beschrijving van deze kleurstaal', 'kleurstalen-manager'); ?></span>
            </p>
            
            <p>
                <label for="ksm_material"><strong><?php _e('Materiaal:', 'kleurstalen-manager'); ?></strong></label><br>
                <select id="ksm_material" name="ksm_material" class="regular-text">
                    <option value=""><?php _e('Selecteer materiaal', 'kleurstalen-manager'); ?></option>
                    <option value="aluminium" <?php selected($material, 'aluminium'); ?>><?php _e('Aluminium', 'kleurstalen-manager'); ?></option>
                    <option value="hout" <?php selected($material, 'hout'); ?>><?php _e('Hout', 'kleurstalen-manager'); ?></option>
                    <option value="bamboe" <?php selected($material, 'bamboe'); ?>><?php _e('Bamboe', 'kleurstalen-manager'); ?></option>
                    <option value="kunststof" <?php selected($material, 'kunststof'); ?>><?php _e('Kunststof', 'kleurstalen-manager'); ?></option>
                    <option value="stof" <?php selected($material, 'stof'); ?>><?php _e('Stof', 'kleurstalen-manager'); ?></option>
                </select>
            </p>
        </div>
        <?php
    }
    
    public function render_colors_meta_box($post) {
        $colors = get_post_meta($post->ID, '_ksm_colors', true);
        if (!is_array($colors)) {
            $colors = array('#ffffff');
        }
        ?>
        <div class="ksm-colors-meta-box">
            <p class="description"><?php _e('Voeg de kleuren toe die in deze kleurstaal zitten. Je kunt meerdere kleuren toevoegen voor gradient effecten.', 'kleurstalen-manager'); ?></p>
            
            <div id="ksm-colors-container">
                <?php foreach ($colors as $index => $color) : ?>
                    <div class="ksm-color-row" style="margin: 10px 0;">
                        <input type="text" name="ksm_colors[]" value="<?php echo esc_attr($color); ?>" class="ksm-color-picker" style="width: 100px;" />
                        <input type="color" value="<?php echo esc_attr($color); ?>" onchange="this.previousElementSibling.value=this.value" style="margin-left: 10px; cursor: pointer;">
                        <button type="button" class="button ksm-remove-color" style="margin-left: 10px;"><?php _e('Verwijder', 'kleurstalen-manager'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p>
                <button type="button" class="button button-secondary" id="ksm-add-color"><?php _e('+ Kleur toevoegen', 'kleurstalen-manager'); ?></button>
            </p>
            
            <div class="ksm-color-preview">
                <h4><?php _e('Voorbeeld:', 'kleurstalen-manager'); ?></h4>
                <div id="ksm-preview-box" style="width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; background: linear-gradient(135deg, <?php echo implode(', ', $colors); ?>);"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add color button
            $('#ksm-add-color').on('click', function() {
                var newRow = '<div class="ksm-color-row" style="margin: 10px 0;">' +
                    '<input type="text" name="ksm_colors[]" value="#ffffff" class="ksm-color-picker" style="width: 100px;" />' +
                    '<input type="color" value="#ffffff" onchange="this.previousElementSibling.value=this.value" style="margin-left: 10px; cursor: pointer;">' +
                    '<button type="button" class="button ksm-remove-color" style="margin-left: 10px;">Verwijder</button>' +
                    '</div>';
                $('#ksm-colors-container').append(newRow);
                updatePreview();
            });
            
            // Remove color
            $(document).on('click', '.ksm-remove-color', function() {
                $(this).closest('.ksm-color-row').remove();
                updatePreview();
            });
            
            // Update preview on color change
            $(document).on('change', '.ksm-color-picker, input[type="color"]', function() {
                updatePreview();
            });
            
            function updatePreview() {
                var colors = [];
                $('.ksm-color-picker').each(function() {
                    colors.push($(this).val());
                });
                
                if (colors.length > 1) {
                    $('#ksm-preview-box').css('background', 'linear-gradient(135deg, ' + colors.join(', ') + ')');
                } else if (colors.length === 1) {
                    $('#ksm-preview-box').css('background', colors[0]);
                }
            }
        });
        </script>
        <?php
    }
    
    public function render_settings_meta_box($post) {
        $active = get_post_meta($post->ID, '_ksm_active', true);
        $sort_order = get_post_meta($post->ID, '_ksm_sort_order', true);
        $popular = get_post_meta($post->ID, '_ksm_popular', true);
        
        // Default active to true for new posts
        if ($post->post_status === 'auto-draft') {
            $active = '1';
        }
        ?>
        <div class="ksm-settings-meta-box">
            <p>
                <label>
                    <input type="checkbox" name="ksm_active" value="1" <?php checked($active, '1'); ?> />
                    <strong><?php _e('Actief', 'kleurstalen-manager'); ?></strong>
                </label><br>
                <span class="description"><?php _e('Toon deze kleurstaal in de frontend', 'kleurstalen-manager'); ?></span>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="ksm_popular" value="1" <?php checked($popular, '1'); ?> />
                    <strong><?php _e('Populair', 'kleurstalen-manager'); ?></strong>
                </label><br>
                <span class="description"><?php _e('Markeer als populaire/aanbevolen kleurstaal', 'kleurstalen-manager'); ?></span>
            </p>
            
            <p>
                <label for="ksm_sort_order"><strong><?php _e('Sorteer volgorde:', 'kleurstalen-manager'); ?></strong></label><br>
                <input type="number" id="ksm_sort_order" name="ksm_sort_order" value="<?php echo esc_attr($sort_order); ?>" min="0" step="1" style="width: 70px;" />
                <br><span class="description"><?php _e('Lager nummer = hoger in lijst', 'kleurstalen-manager'); ?></span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render help meta box
     */
    public function render_help_meta_box($post) {
        ?>
        <div class="ksm-help-box">
            <p><strong><?php _e('Tips:', 'kleurstalen-manager'); ?></strong></p>
            <ul style="margin-left: 20px; list-style: disc;">
                <li><?php _e('Vergeet niet een categorie te selecteren', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Voeg een duidelijke titel toe', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Je kunt meerdere kleuren toevoegen voor gradients', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Gebruik de sorteer volgorde om de positie te bepalen', 'kleurstalen-manager'); ?></li>
            </ul>
            
            <p><strong><?php _e('Shortcode:', 'kleurstalen-manager'); ?></strong></p>
            <code>[kleurstalen_knop]</code>
        </div>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        // Security checks
        if (!isset($_POST['kleurstaal_meta_box_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['kleurstaal_meta_box_nonce'], 'kleurstaal_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save details
        if (isset($_POST['ksm_sku'])) {
            update_post_meta($post_id, '_ksm_sku', sanitize_text_field($_POST['ksm_sku']));
        }
        
        if (isset($_POST['ksm_description'])) {
            update_post_meta($post_id, '_ksm_description', sanitize_textarea_field($_POST['ksm_description']));
        }
        
        if (isset($_POST['ksm_material'])) {
            update_post_meta($post_id, '_ksm_material', sanitize_text_field($_POST['ksm_material']));
        }
        
        // Save colors
        if (isset($_POST['ksm_colors'])) {
            $colors = array_map('sanitize_hex_color', $_POST['ksm_colors']);
            $colors = array_filter($colors); // Remove empty values
            if (empty($colors)) {
                $colors = array('#ffffff'); // Default color if none provided
            }
            update_post_meta($post_id, '_ksm_colors', $colors);
        }
        
        // Save settings
        update_post_meta($post_id, '_ksm_active', isset($_POST['ksm_active']) ? '1' : '0');
        update_post_meta($post_id, '_ksm_popular', isset($_POST['ksm_popular']) ? '1' : '0');
        
        if (isset($_POST['ksm_sort_order'])) {
            update_post_meta($post_id, '_ksm_sort_order', intval($_POST['ksm_sort_order']));
        }
    }
    
    public function add_columns($columns) {
        $new_columns = array();
        
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Titel', 'kleurstalen-manager');
        $new_columns['preview'] = __('Voorbeeld', 'kleurstalen-manager');
        $new_columns['sku'] = __('SKU', 'kleurstalen-manager');
        $new_columns['category'] = __('Categorie', 'kleurstalen-manager');
        $new_columns['material'] = __('Materiaal', 'kleurstalen-manager');
        $new_columns['active'] = __('Status', 'kleurstalen-manager');
        $new_columns['sort_order'] = __('Volgorde', 'kleurstalen-manager');
        $new_columns['date'] = __('Datum', 'kleurstalen-manager');
        
        return $new_columns;
    }
    
    public function render_columns($column, $post_id) {
        switch ($column) {
            case 'preview':
                $colors = get_post_meta($post_id, '_ksm_colors', true);
                if (is_array($colors) && !empty($colors)) {
                    echo '<div class="ksm-admin-preview" style="display: flex; gap: 5px;">';
                    foreach ($colors as $color) {
                        echo '<span style="display: inline-block; width: 30px; height: 30px; background: ' . esc_attr($color) . '; border: 1px solid #ddd; border-radius: 4px;"></span>';
                    }
                    echo '</div>';
                }
                break;
                
            case 'sku':
                echo esc_html(get_post_meta($post_id, '_ksm_sku', true));
                break;
                
            case 'category':
                $terms = get_the_terms($post_id, 'kleurstaal_category');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $term_names));
                }
                break;
                
            case 'material':
                $material = get_post_meta($post_id, '_ksm_material', true);
                echo esc_html(ucfirst($material));
                break;
                
            case 'active':
                $active = get_post_meta($post_id, '_ksm_active', true);
                $popular = get_post_meta($post_id, '_ksm_popular', true);
                
                if ($active === '1') {
                    echo '<span class="dashicons dashicons-yes" style="color: green;"></span> ';
                    echo __('Actief', 'kleurstalen-manager');
                } else {
                    echo '<span class="dashicons dashicons-no" style="color: red;"></span> ';
                    echo __('Inactief', 'kleurstalen-manager');
                }
                
                if ($popular === '1') {
                    echo ' <span class="dashicons dashicons-star-filled" style="color: gold;" title="' . __('Populair', 'kleurstalen-manager') . '"></span>';
                }
                break;
                
            case 'sort_order':
                $order = get_post_meta($post_id, '_ksm_sort_order', true);
                echo $order ? esc_html($order) : '0';
                break;
        }
    }
    
    public function sortable_columns($columns) {
        $columns['sort_order'] = 'sort_order';
        $columns['sku'] = 'sku';
        return $columns;
    }
}