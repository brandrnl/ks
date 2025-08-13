<?php
/**
 * Post Types Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KSM_Post_Types {
    
    public function init() {
        add_action('init', array($this, 'register_post_types'), 5);
        add_action('init', array($this, 'register_taxonomies'), 5);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_kleurstaal', array($this, 'save_meta_boxes'));
        
        // Custom columns
        add_filter('manage_kleurstaal_posts_columns', array($this, 'add_columns'));
        add_action('manage_kleurstaal_posts_custom_column', array($this, 'render_columns'), 10, 2);
        add_filter('manage_edit-kleurstaal_sortable_columns', array($this, 'sortable_columns'));
        
        // Quick edit
        add_action('quick_edit_custom_box', array($this, 'quick_edit_custom_box'), 10, 2);
        add_action('save_post', array($this, 'save_quick_edit'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Forceer eigen menu - BELANGRIJK: late priority
        add_action('admin_menu', array($this, 'force_own_menu'), 999);
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
            'featured_image' => __('Kleurstaal afbeelding', 'kleurstalen-manager'),
            'set_featured_image' => __('Stel kleurstaal afbeelding in', 'kleurstalen-manager'),
            'remove_featured_image' => __('Verwijder kleurstaal afbeelding', 'kleurstalen-manager'),
            'use_featured_image' => __('Gebruik als kleurstaal afbeelding', 'kleurstalen-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'description' => __('Kleurstalen voor jaloezieën', 'kleurstalen-manager'),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 30,
            'menu_icon' => 'dashicons-color-picker',
            'supports' => array('title', 'thumbnail', 'custom-fields'),
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'can_export' => true,
            'exclude_from_search' => true,
            'show_in_rest' => false,
            'map_meta_cap' => true,
        );
        
        register_post_type('kleurstaal', $args);
    }
    
    public function register_taxonomies() {
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
            'back_to_items' => __('← Terug naar categorieën', 'kleurstalen-manager'),
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_quick_edit' => true,
            'query_var' => true,
            'rewrite' => false,
            'capabilities' => array(
                'manage_terms' => 'manage_options',
                'edit_terms' => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'manage_options',
            ),
            'show_in_rest' => false,
        );
        
        register_taxonomy('kleurstaal_category', array('kleurstaal'), $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'kleurstaal_details',
            __('📋 Kleurstaal Details', 'kleurstalen-manager'),
            array($this, 'render_details_meta_box'),
            'kleurstaal',
            'normal',
            'high'
        );
        
        add_meta_box(
            'kleurstaal_visual',
            __('🖼️ Visuele Weergave', 'kleurstalen-manager'),
            array($this, 'render_visual_meta_box'),
            'kleurstaal',
            'normal',
            'high'
        );
        
        add_meta_box(
            'kleurstaal_colors',
            __('🎨 Kleuren', 'kleurstalen-manager'),
            array($this, 'render_colors_meta_box'),
            'kleurstaal',
            'normal',
            'high'
        );
        
        add_meta_box(
            'kleurstaal_settings',
            __('⚙️ Instellingen', 'kleurstalen-manager'),
            array($this, 'render_settings_meta_box'),
            'kleurstaal',
            'side',
            'default'
        );
        
        add_meta_box(
            'kleurstaal_statistics',
            __('📊 Statistieken', 'kleurstalen-manager'),
            array($this, 'render_statistics_meta_box'),
            'kleurstaal',
            'side',
            'low'
        );
        
        add_meta_box(
            'kleurstaal_help',
            __('💡 Hulp & Tips', 'kleurstalen-manager'),
            array($this, 'render_help_meta_box'),
            'kleurstaal',
            'side',
            'low'
        );
    }
    
    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('kleurstaal_meta_box', 'kleurstaal_meta_box_nonce');
        
        $sku = get_post_meta($post->ID, '_ksm_sku', true);
        $description = get_post_meta($post->ID, '_ksm_description', true);
        $material = get_post_meta($post->ID, '_ksm_material', true);
        $price = get_post_meta($post->ID, '_ksm_price', true);
        ?>
        <style>
            .ksm-meta-field { margin-bottom: 20px; }
            .ksm-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .ksm-meta-field .description { color: #666; font-size: 13px; margin-top: 5px; }
            .ksm-meta-field input[type="text"], 
            .ksm-meta-field input[type="number"],
            .ksm-meta-field select, 
            .ksm-meta-field textarea { width: 100%; }
        </style>
        
        <div class="ksm-meta-box">
            <div class="ksm-meta-field">
                <label for="ksm_sku">
                    <?php _e('SKU/Artikelnummer:', 'kleurstalen-manager'); ?>
                    <span style="color: red;">*</span>
                </label>
                <input type="text" 
                       id="ksm_sku" 
                       name="ksm_sku" 
                       value="<?php echo esc_attr($sku); ?>" 
                       placeholder="Bijv: ALU-001"
                       required />
                <p class="description">
                    <?php _e('Uniek artikelnummer voor deze kleurstaal. Dit wordt gebruikt voor identificatie.', 'kleurstalen-manager'); ?>
                </p>
            </div>
            
            <div class="ksm-meta-field">
                <label for="ksm_price">
                    <?php _e('Prijs per staal (optioneel):', 'kleurstalen-manager'); ?>
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span><?php echo get_woocommerce_currency_symbol(); ?></span>
                    <input type="number" 
                           id="ksm_price" 
                           name="ksm_price" 
                           value="<?php echo esc_attr($price); ?>" 
                           placeholder="0.00"
                           step="0.01"
                           min="0"
                           style="width: 150px;" />
                </div>
                <p class="description">
                    <?php _e('Laat leeg om de standaard prijs te gebruiken uit de instellingen. Vul een prijs in voor een afwijkende prijs.', 'kleurstalen-manager'); ?>
                </p>
            </div>
            
            <div class="ksm-meta-field">
                <label for="ksm_material">
                    <?php _e('Materiaal:', 'kleurstalen-manager'); ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="ksm_material" name="ksm_material" required>
                    <option value=""><?php _e('— Selecteer materiaal —', 'kleurstalen-manager'); ?></option>
                    <option value="aluminium" <?php selected($material, 'aluminium'); ?>>
                        <?php _e('Aluminium', 'kleurstalen-manager'); ?>
                    </option>
                    <option value="hout" <?php selected($material, 'hout'); ?>>
                        <?php _e('Hout', 'kleurstalen-manager'); ?>
                    </option>
                    <option value="bamboe" <?php selected($material, 'bamboe'); ?>>
                        <?php _e('Bamboe', 'kleurstalen-manager'); ?>
                    </option>
                    <option value="kunststof" <?php selected($material, 'kunststof'); ?>>
                        <?php _e('Kunststof', 'kleurstalen-manager'); ?>
                    </option>
                    <option value="stof" <?php selected($material, 'stof'); ?>>
                        <?php _e('Stof', 'kleurstalen-manager'); ?>
                    </option>
                    <option value="composiet" <?php selected($material, 'composiet'); ?>>
                        <?php _e('Composiet', 'kleurstalen-manager'); ?>
                    </option>
                </select>
                <p class="description">
                    <?php _e('Het materiaal waarvan deze kleurstaal is gemaakt.', 'kleurstalen-manager'); ?>
                </p>
            </div>
            
            <div class="ksm-meta-field">
                <label for="ksm_description">
                    <?php _e('Beschrijving:', 'kleurstalen-manager'); ?>
                </label>
                <textarea id="ksm_description" 
                          name="ksm_description" 
                          rows="4"
                          placeholder="<?php _e('Optionele beschrijving van deze kleurstaal...', 'kleurstalen-manager'); ?>"><?php echo esc_textarea($description); ?></textarea>
                <p class="description">
                    <?php _e('Een korte beschrijving die wordt getoond bij de kleurstaal. Bijv: eigenschappen, afwerking, etc.', 'kleurstalen-manager'); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render visual meta box (nieuwe functie voor afbeeldingen)
     */
    public function render_visual_meta_box($post) {
        // Get saved images
        $sample_images = get_post_meta($post->ID, '_ksm_sample_images', true);
        if (!is_array($sample_images)) {
            $sample_images = array();
        }
        
        // Get display type
        $display_type = get_post_meta($post->ID, '_ksm_display_type', true);
        if (!$display_type) {
            $display_type = 'colors'; // Default to colors
        }
        ?>
        <style>
            .ksm-visual-meta-box { padding: 15px; }
            .ksm-display-type-selector { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
            .ksm-display-type-selector label { 
                display: inline-block; 
                margin-right: 20px; 
                cursor: pointer;
                padding: 8px 15px;
                border-radius: 4px;
                transition: all 0.3s;
            }
            .ksm-display-type-selector label:hover { background: #e0e0e0; }
            .ksm-display-type-selector input[type="radio"]:checked + span { 
                font-weight: bold; 
                color: #0073aa;
            }
            .ksm-images-section { margin-top: 20px; }
            .ksm-image-upload-container { 
                border: 2px dashed #ccc; 
                border-radius: 8px; 
                padding: 20px; 
                text-align: center;
                background: #fafafa;
            }
            .ksm-images-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); 
                gap: 15px; 
                margin-top: 20px;
            }
            .ksm-image-item { 
                position: relative; 
                border: 2px solid #ddd; 
                border-radius: 8px; 
                overflow: hidden;
                background: white;
            }
            .ksm-image-item img { 
                width: 100%; 
                height: 150px; 
                object-fit: cover; 
                display: block;
            }
            .ksm-image-item-actions { 
                position: absolute; 
                top: 5px; 
                right: 5px; 
                display: flex;
                gap: 5px;
            }
            .ksm-image-remove, .ksm-image-primary { 
                background: rgba(255,255,255,0.9); 
                border: none; 
                width: 30px; 
                height: 30px; 
                border-radius: 4px; 
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s;
            }
            .ksm-image-remove:hover { background: #ff4444; color: white; }
            .ksm-image-primary { background: #ffd700; }
            .ksm-image-primary.is-primary { background: #4CAF50; color: white; }
            .ksm-image-item.primary { border-color: #4CAF50; box-shadow: 0 0 10px rgba(76,175,80,0.3); }
            .ksm-display-note { 
                padding: 10px; 
                background: #fff3cd; 
                border-left: 4px solid #ffc107; 
                margin-top: 15px;
                border-radius: 4px;
            }
        </style>
        
        <div class="ksm-visual-meta-box">
            <div class="ksm-display-type-selector">
                <h4><?php _e('Weergave Type:', 'kleurstalen-manager'); ?></h4>
                <label>
                    <input type="radio" name="ksm_display_type" value="colors" <?php checked($display_type, 'colors'); ?>>
                    <span>🎨 <?php _e('Alleen Kleuren', 'kleurstalen-manager'); ?></span>
                </label>
                <label>
                    <input type="radio" name="ksm_display_type" value="image" <?php checked($display_type, 'image'); ?>>
                    <span>🖼️ <?php _e('Alleen Afbeelding', 'kleurstalen-manager'); ?></span>
                </label>
                <label>
                    <input type="radio" name="ksm_display_type" value="both" <?php checked($display_type, 'both'); ?>>
                    <span>🎨🖼️ <?php _e('Kleuren + Afbeelding', 'kleurstalen-manager'); ?></span>
                </label>
            </div>
            
            <div class="ksm-images-section">
                <h4><?php _e('Kleurstaal Afbeeldingen:', 'kleurstalen-manager'); ?></h4>
                <p class="description">
                    <?php _e('Upload één of meerdere afbeeldingen van deze kleurstaal. De eerste afbeelding wordt als hoofdafbeelding gebruikt.', 'kleurstalen-manager'); ?>
                </p>
                
                <div class="ksm-image-upload-container">
                    <button type="button" class="button button-primary button-large" id="ksm-upload-images">
                        <span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
                        <?php _e('Upload Afbeeldingen', 'kleurstalen-manager'); ?>
                    </button>
                    <p class="description" style="margin-top: 10px;">
                        <?php _e('Aanbevolen formaat: 800x800px, JPG of PNG', 'kleurstalen-manager'); ?>
                    </p>
                </div>
                
                <div class="ksm-images-grid" id="ksm-images-grid">
                    <?php foreach ($sample_images as $index => $image_id) : 
                        $image_url = wp_get_attachment_image_url($image_id, 'medium');
                        if ($image_url) :
                    ?>
                        <div class="ksm-image-item <?php echo $index === 0 ? 'primary' : ''; ?>" data-image-id="<?php echo $image_id; ?>">
                            <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <div class="ksm-image-item-actions">
                                <button type="button" 
                                        class="ksm-image-primary <?php echo $index === 0 ? 'is-primary' : ''; ?>" 
                                        title="<?php _e('Stel in als hoofdafbeelding', 'kleurstalen-manager'); ?>">
                                    ⭐
                                </button>
                                <button type="button" 
                                        class="ksm-image-remove" 
                                        title="<?php _e('Verwijder afbeelding', 'kleurstalen-manager'); ?>">
                                    ✕
                                </button>
                            </div>
                            <input type="hidden" name="ksm_sample_images[]" value="<?php echo $image_id; ?>">
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="ksm-display-note">
                    <strong><?php _e('Tip:', 'kleurstalen-manager'); ?></strong>
                    <?php _e('Je kunt zowel kleuren als afbeeldingen gebruiken. In de popup kunnen klanten kiezen welke weergave ze prefereren.', 'kleurstalen-manager'); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            // Upload images button
            $('#ksm-upload-images').on('click', function(e) {
                e.preventDefault();
                
                // Create media frame if not exists
                if (frame) {
                    frame.open();
                    return;
                }
                
                // Create a new media frame
                frame = wp.media({
                    title: '<?php _e('Selecteer of upload afbeeldingen', 'kleurstalen-manager'); ?>',
                    button: {
                        text: '<?php _e('Gebruik deze afbeeldingen', 'kleurstalen-manager'); ?>'
                    },
                    multiple: true // Allow multiple selection
                });
                
                // When images are selected
                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    
                    attachments.forEach(function(attachment) {
                        // Check if image already exists
                        if ($('.ksm-image-item[data-image-id="' + attachment.id + '"]').length) {
                            return;
                        }
                        
                        // Add new image to grid
                        var isPrimary = $('#ksm-images-grid .ksm-image-item').length === 0;
                        var html = '<div class="ksm-image-item' + (isPrimary ? ' primary' : '') + '" data-image-id="' + attachment.id + '">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                            '<div class="ksm-image-item-actions">' +
                            '<button type="button" class="ksm-image-primary' + (isPrimary ? ' is-primary' : '') + '" title="Stel in als hoofdafbeelding">⭐</button>' +
                            '<button type="button" class="ksm-image-remove" title="Verwijder afbeelding">✕</button>' +
                            '</div>' +
                            '<input type="hidden" name="ksm_sample_images[]" value="' + attachment.id + '">' +
                            '</div>';
                        
                        $('#ksm-images-grid').append(html);
                    });
                });
                
                // Open the frame
                frame.open();
            });
            
            // Remove image
            $(document).on('click', '.ksm-image-remove', function() {
                var $item = $(this).closest('.ksm-image-item');
                var wasPrimary = $item.hasClass('primary');
                
                $item.fadeOut(300, function() {
                    $(this).remove();
                    
                    // If this was primary, make first image primary
                    if (wasPrimary) {
                        var $firstItem = $('#ksm-images-grid .ksm-image-item').first();
                        if ($firstItem.length) {
                            $firstItem.addClass('primary');
                            $firstItem.find('.ksm-image-primary').addClass('is-primary');
                        }
                    }
                });
            });
            
            // Set as primary image
            $(document).on('click', '.ksm-image-primary', function() {
                // Remove primary from all
                $('.ksm-image-item').removeClass('primary');
                $('.ksm-image-primary').removeClass('is-primary');
                
                // Add primary to clicked
                $(this).addClass('is-primary');
                $(this).closest('.ksm-image-item').addClass('primary');
                
                // Move to first position
                var $item = $(this).closest('.ksm-image-item');
                $item.prependTo('#ksm-images-grid');
            });
            
            // Make images sortable
            $('#ksm-images-grid').sortable({
                items: '.ksm-image-item',
                cursor: 'move',
                placeholder: 'ksm-image-placeholder',
                forcePlaceholderSize: true,
                update: function(event, ui) {
                    // Update primary status based on position
                    $('.ksm-image-item').removeClass('primary');
                    $('.ksm-image-primary').removeClass('is-primary');
                    
                    var $first = $('.ksm-image-item').first();
                    $first.addClass('primary');
                    $first.find('.ksm-image-primary').addClass('is-primary');
                }
            });
        });
        </script>
        <?php
    }
    
    public function render_colors_meta_box($post) {
        $colors = get_post_meta($post->ID, '_ksm_colors', true);
        if (!is_array($colors) || empty($colors)) {
            $colors = array('#ffffff');
        }
        ?>
        <style>
            .ksm-colors-meta-box { padding: 10px; }
            .ksm-color-row { 
                display: flex; 
                align-items: center; 
                gap: 10px; 
                margin-bottom: 10px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .ksm-color-preview-box {
                margin-top: 20px;
                padding: 15px;
                background: #f5f5f5;
                border-radius: 8px;
            }
            #ksm-preview-box {
                border: 2px solid #ddd;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        </style>
        
        <div class="ksm-colors-meta-box">
            <p class="description">
                <?php _e('Voeg de kleuren toe die in deze kleurstaal zitten. Meerdere kleuren worden als gradient getoond.', 'kleurstalen-manager'); ?>
            </p>
            
            <div id="ksm-colors-container">
                <?php foreach ($colors as $index => $color) : ?>
                    <div class="ksm-color-row">
                        <span style="color: #666;">#<?php echo ($index + 1); ?></span>
                        <input type="text" 
                               name="ksm_colors[]" 
                               value="<?php echo esc_attr($color); ?>" 
                               class="ksm-color-picker" 
                               style="width: 100px;" 
                               placeholder="#ffffff" />
                        <input type="color" 
                               value="<?php echo esc_attr($color); ?>" 
                               onchange="this.previousElementSibling.value=this.value; updateColorPreview();" 
                               style="width: 50px; height: 35px; cursor: pointer; border-radius: 4px;">
                        <button type="button" class="button ksm-remove-color">
                            <?php _e('Verwijder', 'kleurstalen-manager'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p>
                <button type="button" class="button button-primary" id="ksm-add-color">
                    <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                    <?php _e('Kleur toevoegen', 'kleurstalen-manager'); ?>
                </button>
            </p>
            
            <div class="ksm-color-preview-box">
                <h4><?php _e('Live Voorbeeld:', 'kleurstalen-manager'); ?></h4>
                <div id="ksm-preview-box" style="width: 100%; height: 150px; border-radius: 8px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var colorIndex = <?php echo count($colors); ?>;
            
            // Add color button
            $('#ksm-add-color').on('click', function() {
                colorIndex++;
                var newRow = '<div class="ksm-color-row">' +
                    '<span style="color: #666;">#' + colorIndex + '</span>' +
                    '<input type="text" name="ksm_colors[]" value="#ffffff" class="ksm-color-picker" style="width: 100px;" placeholder="#ffffff" />' +
                    '<input type="color" value="#ffffff" onchange="this.previousElementSibling.value=this.value; updateColorPreview();" style="width: 50px; height: 35px; cursor: pointer; border-radius: 4px;">' +
                    '<button type="button" class="button ksm-remove-color">Verwijder</button>' +
                    '</div>';
                $('#ksm-colors-container').append(newRow);
                updateColorPreview();
            });
            
            // Remove color
            $(document).on('click', '.ksm-remove-color', function() {
                if ($('.ksm-color-row').length > 1) {
                    $(this).closest('.ksm-color-row').fadeOut(300, function() {
                        $(this).remove();
                        updateColorNumbers();
                        updateColorPreview();
                    });
                } else {
                    alert('Er moet minimaal één kleur zijn.');
                }
            });
            
            // Update color numbers
            function updateColorNumbers() {
                $('.ksm-color-row').each(function(index) {
                    $(this).find('span:first').text('#' + (index + 1));
                });
                colorIndex = $('.ksm-color-row').length;
            }
            
            // Update preview on color change
            $(document).on('change input', '.ksm-color-picker', function() {
                var colorInput = $(this).next('input[type="color"]');
                if (colorInput.length) {
                    colorInput.val($(this).val());
                }
                updateColorPreview();
            });
            
            // Initial preview
            updateColorPreview();
        });
        
        function updateColorPreview() {
            var colors = [];
            jQuery('.ksm-color-picker').each(function() {
                colors.push(jQuery(this).val());
            });
            
            if (colors.length > 1) {
                jQuery('#ksm-preview-box').css('background', 'linear-gradient(135deg, ' + colors.join(', ') + ')');
            } else if (colors.length === 1) {
                jQuery('#ksm-preview-box').css('background', colors[0]);
            }
        }
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
        <style>
            .ksm-settings-field { margin-bottom: 15px; }
            .ksm-settings-field label { font-weight: 600; }
            .ksm-checkbox-label { display: flex; align-items: center; gap: 5px; }
        </style>
        
        <div class="ksm-settings-meta-box">
            <div class="ksm-settings-field">
                <label class="ksm-checkbox-label">
                    <input type="checkbox" name="ksm_active" value="1" <?php checked($active, '1'); ?> />
                    <strong><?php _e('Actief', 'kleurstalen-manager'); ?></strong>
                </label>
                <p class="description">
                    <?php _e('Schakel uit om te verbergen in de frontend', 'kleurstalen-manager'); ?>
                </p>
            </div>
            
            <div class="ksm-settings-field">
                <label class="ksm-checkbox-label">
                    <input type="checkbox" name="ksm_popular" value="1" <?php checked($popular, '1'); ?> />
                    <strong><?php _e('⭐ Populair/Aanbevolen', 'kleurstalen-manager'); ?></strong>
                </label>
                <p class="description">
                    <?php _e('Toon als aanbevolen kleurstaal', 'kleurstalen-manager'); ?>
                </p>
            </div>
            
            <div class="ksm-settings-field">
                <label for="ksm_sort_order">
                    <strong><?php _e('Sorteer volgorde:', 'kleurstalen-manager'); ?></strong>
                </label>
                <input type="number" 
                       id="ksm_sort_order" 
                       name="ksm_sort_order" 
                       value="<?php echo esc_attr($sort_order ?: 0); ?>" 
                       min="0" 
                       step="1" 
                       style="width: 100%;" />
                <p class="description">
                    <?php _e('Lager = hoger in lijst (0 = standaard)', 'kleurstalen-manager'); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render statistics meta box
     */
    public function render_statistics_meta_box($post) {
        if ($post->post_status === 'auto-draft') {
            echo '<p>' . __('Statistieken zijn beschikbaar na publicatie.', 'kleurstalen-manager') . '</p>';
            return;
        }
        
        $selection_count = get_post_meta($post->ID, '_ksm_selection_count', true) ?: 0;
        $last_selected = get_post_meta($post->ID, '_ksm_last_selected', true);
        ?>
        <div class="ksm-stats-box">
            <p>
                <strong><?php _e('Aantal selecties:', 'kleurstalen-manager'); ?></strong><br>
                <span style="font-size: 24px; color: #0073aa;"><?php echo intval($selection_count); ?></span>
            </p>
            
            <?php if ($last_selected) : ?>
                <p>
                    <strong><?php _e('Laatst geselecteerd:', 'kleurstalen-manager'); ?></strong><br>
                    <?php echo date_i18n(get_option('date_format'), strtotime($last_selected)); ?>
                </p>
            <?php endif; ?>
            
            <p>
                <strong><?php _e('Status:', 'kleurstalen-manager'); ?></strong><br>
                <?php
                $active = get_post_meta($post->ID, '_ksm_active', true);
                if ($active === '1') {
                    echo '<span style="color: green;">✓ ' . __('Actief', 'kleurstalen-manager') . '</span>';
                } else {
                    echo '<span style="color: red;">✗ ' . __('Inactief', 'kleurstalen-manager') . '</span>';
                }
                ?>
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
            <h4><?php _e('Tips:', 'kleurstalen-manager'); ?></h4>
            <ul style="margin-left: 20px; list-style: disc; line-height: 1.6;">
                <li><?php _e('Gebruik een duidelijke titel', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Voeg een unieke SKU toe', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Selecteer de juiste categorie', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Voeg meerdere kleuren toe voor gradients', 'kleurstalen-manager'); ?></li>
                <li><?php _e('Upload een afbeelding voor betere weergave', 'kleurstalen-manager'); ?></li>
            </ul>
            
            <h4 style="margin-top: 20px;"><?php _e('Shortcodes:', 'kleurstalen-manager'); ?></h4>
            <p><code>[kleurstalen_knop]</code></p>
            <p><code>[kleurstalen_grid]</code></p>
            <p><code>[kleurstalen_categorie]</code></p>
            
            <p style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=ksm-settings'); ?>" class="button button-small">
                    <?php _e('Plugin Instellingen', 'kleurstalen-manager'); ?>
                </a>
            </p>
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
        
        if (isset($_POST['ksm_price'])) {
            update_post_meta($post_id, '_ksm_price', floatval($_POST['ksm_price']));
        }
        
        if (isset($_POST['ksm_description'])) {
            update_post_meta($post_id, '_ksm_description', sanitize_textarea_field($_POST['ksm_description']));
        }
        
        if (isset($_POST['ksm_material'])) {
            update_post_meta($post_id, '_ksm_material', sanitize_text_field($_POST['ksm_material']));
        }
        
        // Save display type
        if (isset($_POST['ksm_display_type'])) {
            update_post_meta($post_id, '_ksm_display_type', sanitize_text_field($_POST['ksm_display_type']));
        }
        
        // Save sample images
        if (isset($_POST['ksm_sample_images'])) {
            $images = array_map('intval', $_POST['ksm_sample_images']);
            update_post_meta($post_id, '_ksm_sample_images', $images);
        } else {
            delete_post_meta($post_id, '_ksm_sample_images');
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
    
    /**
     * Forceer eigen menu item (niet onder WooCommerce)
     */
    public function force_own_menu() {
        global $menu, $submenu;
        
        // Debug: Log menu status
        error_log('KSM: Forcing own menu...');
        
        // Verwijder eerst eventuele WooCommerce submenu items
        if (isset($submenu['woocommerce'])) {
            foreach ($submenu['woocommerce'] as $key => $item) {
                if (isset($item[2]) && strpos($item[2], 'kleurstaal') !== false) {
                    unset($submenu['woocommerce'][$key]);
                    error_log('KSM: Removed from WooCommerce submenu: ' . $item[2]);
                }
            }
        }
        
        // Check of menu al bestaat
        $menu_exists = false;
        $menu_position = null;
        
        if (is_array($menu)) {
            foreach ($menu as $position => $item) {
                if (isset($item[2]) && $item[2] === 'edit.php?post_type=kleurstaal') {
                    $menu_exists = true;
                    $menu_position = $position;
                    error_log('KSM: Menu already exists at position ' . $position);
                    break;
                }
            }
        }
        
        // Als menu niet bestaat, voeg het toe
        if (!$menu_exists) {
            error_log('KSM: Adding new menu item...');
            
            // Vind een vrije positie rond 30
            $desired_position = 30;
            while (isset($menu[$desired_position])) {
                $desired_position++;
            }
            
            $menu[$desired_position] = array(
                __('Kleurstalen', 'kleurstalen-manager'), // Menu title
                'edit_posts',                              // Capability
                'edit.php?post_type=kleurstaal',          // Menu slug
                __('Kleurstalen', 'kleurstalen-manager'), // Page title
                'menu-top menu-icon-kleurstaal',          // CSS classes
                'menu-kleurstalen',                       // ID
                'dashicons-color-picker'                  // Icon
            );
            
            error_log('KSM: Menu added at position ' . $desired_position);
        }
        
        // Zorg ervoor dat submenu items correct zijn
        if (!isset($submenu['edit.php?post_type=kleurstaal'])) {
            $submenu['edit.php?post_type=kleurstaal'] = array();
        }
        
        // Reset en voeg submenu items toe
        $submenu['edit.php?post_type=kleurstaal'] = array(
            10 => array(
                __('Alle Kleurstalen', 'kleurstalen-manager'),
                'edit_posts',
                'edit.php?post_type=kleurstaal'
            ),
            20 => array(
                __('Nieuwe Toevoegen', 'kleurstalen-manager'),
                'edit_posts',
                'post-new.php?post_type=kleurstaal'
            ),
            30 => array(
                __('Categorieën', 'kleurstalen-manager'),
                'manage_categories',
                'edit-tags.php?taxonomy=kleurstaal_category&post_type=kleurstaal'
            )
        );
        
        error_log('KSM: Submenu items added');
    }
    
    public function add_columns($columns) {
        $new_columns = array();
        
        // Herorden columns
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Titel', 'kleurstalen-manager');
        $new_columns['preview'] = __('Voorbeeld', 'kleurstalen-manager');
        $new_columns['sku'] = __('SKU', 'kleurstalen-manager');
        $new_columns['taxonomy-kleurstaal_category'] = __('Categorie', 'kleurstalen-manager');
        $new_columns['material'] = __('Materiaal', 'kleurstalen-manager');
        $new_columns['active'] = __('Status', 'kleurstalen-manager');
        $new_columns['selections'] = __('Selecties', 'kleurstalen-manager');
        $new_columns['sort_order'] = __('Volgorde', 'kleurstalen-manager');
        $new_columns['date'] = __('Datum', 'kleurstalen-manager');
        
        return $new_columns;
    }
    
    public function render_columns($column, $post_id) {
        switch ($column) {
            case 'preview':
                $display_type = get_post_meta($post_id, '_ksm_display_type', true);
                $colors = get_post_meta($post_id, '_ksm_colors', true);
                $images = get_post_meta($post_id, '_ksm_sample_images', true);
                
                echo '<div style="display: flex; align-items: center; gap: 10px;">';
                
                // Toon afbeelding als die er is
                if (!empty($images) && is_array($images)) {
                    $first_image = reset($images);
                    $image_url = wp_get_attachment_image_url($first_image, 'thumbnail');
                    if ($image_url) {
                        echo '<img src="' . esc_url($image_url) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                    }
                }
                
                // Toon kleuren als die er zijn
                if (is_array($colors) && !empty($colors)) {
                    echo '<div style="display: flex; gap: 3px;">';
                    foreach (array_slice($colors, 0, 3) as $color) {
                        echo '<span style="display: inline-block; width: 30px; height: 30px; background: ' . esc_attr($color) . '; border: 1px solid #ddd; border-radius: 4px;"></span>';
                    }
                    if (count($colors) > 3) {
                        echo '<span style="display: inline-flex; align-items: center; padding: 0 5px; color: #666;">+' . (count($colors) - 3) . '</span>';
                    }
                    echo '</div>';
                }
                
                // Toon display type badge
                if ($display_type) {
                    $type_labels = array(
                        'colors' => '🎨',
                        'image' => '🖼️',
                        'both' => '🎨🖼️'
                    );
                    echo '<span style="font-size: 18px;" title="' . esc_attr($display_type) . '">' . (isset($type_labels[$display_type]) ? $type_labels[$display_type] : '') . '</span>';
                }
                
                echo '</div>';
                break;
                
            case 'sku':
                $sku = get_post_meta($post_id, '_ksm_sku', true);
                echo $sku ? '<code>' . esc_html($sku) . '</code>' : '—';
                break;
                
            case 'material':
                $material = get_post_meta($post_id, '_ksm_material', true);
                $materials = array(
                    'aluminium' => __('Aluminium', 'kleurstalen-manager'),
                    'hout' => __('Hout', 'kleurstalen-manager'),
                    'bamboe' => __('Bamboe', 'kleurstalen-manager'),
                    'kunststof' => __('Kunststof', 'kleurstalen-manager'),
                    'stof' => __('Stof', 'kleurstalen-manager'),
                    'composiet' => __('Composiet', 'kleurstalen-manager'),
                );
                echo isset($materials[$material]) ? $materials[$material] : '—';
                break;
                
            case 'active':
                $active = get_post_meta($post_id, '_ksm_active', true);
                $popular = get_post_meta($post_id, '_ksm_popular', true);
                
                echo '<div style="display: flex; gap: 5px; align-items: center;">';
                if ($active === '1') {
                    echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . __('Actief', 'kleurstalen-manager') . '"></span>';
                } else {
                    echo '<span class="dashicons dashicons-dismiss" style="color: #dc3232;" title="' . __('Inactief', 'kleurstalen-manager') . '"></span>';
                }
                
                if ($popular === '1') {
                    echo '<span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="' . __('Populair', 'kleurstalen-manager') . '"></span>';
                }
                echo '</div>';
                break;
                
            case 'selections':
                $count = get_post_meta($post_id, '_ksm_selection_count', true);
                echo intval($count);
                break;
                
            case 'sort_order':
                $order = get_post_meta($post_id, '_ksm_sort_order', true);
                echo $order ? intval($order) : '0';
                break;
        }
    }
    
    public function sortable_columns($columns) {
        $columns['sort_order'] = 'sort_order';
        $columns['sku'] = 'sku';
        $columns['selections'] = 'selections';
        return $columns;
    }
    
    /**
     * Quick edit custom box
     */
    public function quick_edit_custom_box($column_name, $post_type) {
        if ($post_type !== 'kleurstaal') {
            return;
        }
        
        if ($column_name === 'active') {
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label class="inline-edit-group">
                        <span class="title"><?php _e('Status', 'kleurstalen-manager'); ?></span>
                        <label class="alignleft">
                            <input type="checkbox" name="ksm_active" value="1">
                            <span class="checkbox-title"><?php _e('Actief', 'kleurstalen-manager'); ?></span>
                        </label>
                        <label class="alignleft">
                            <input type="checkbox" name="ksm_popular" value="1">
                            <span class="checkbox-title"><?php _e('Populair', 'kleurstalen-manager'); ?></span>
                        </label>
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Save quick edit
     */
    public function save_quick_edit($post_id) {
        if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'kleurstaal') {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['ksm_active'])) {
            update_post_meta($post_id, '_ksm_active', $_POST['ksm_active'] ? '1' : '0');
        }
        
        if (isset($_POST['ksm_popular'])) {
            update_post_meta($post_id, '_ksm_popular', $_POST['ksm_popular'] ? '1' : '0');
        }
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        // Alleen op kleurstaal pagina's
        if ($screen->post_type !== 'kleurstaal') {
            return;
        }
        
        // Check voor lege categorieën
        if ($screen->base === 'edit' || $screen->base === 'post') {
            $categories = get_terms(array(
                'taxonomy' => 'kleurstaal_category',
                'hide_empty' => false
            ));
            
            if (empty($categories) || is_wp_error($categories)) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Let op:', 'kleurstalen-manager'); ?></strong> 
                        <?php _e('Er zijn nog geen categorieën aangemaakt. Maak eerst een categorie aan voordat je kleurstalen toevoegt.', 'kleurstalen-manager'); ?>
                    </p>
                    <p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=kleurstaal_category&post_type=kleurstaal'); ?>" class="button button-primary">
                            <?php _e('Categorie toevoegen', 'kleurstalen-manager'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }
}
