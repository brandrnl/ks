/**
 * Kleurstalen Manager Frontend JavaScript - FIXED VERSION
 */

(function($) {
    'use strict';
    
    var KSM = {
        // Properties
        popup: null,
        currentStep: 1,
        selectedCategory: null,
        selectedSamples: [],
        maxSamples: parseInt(ksm_ajax.max_samples) || 10,
        isOpen: false,
        
        // Initialize
        init: function() {
            var self = this;
            
            // Wait for DOM ready
            $(document).ready(function() {
                self.cacheDom();
                self.bindEvents();
                console.log('KSM: Initialized');
            });
        },
        
        // Cache DOM elements
        cacheDom: function() {
            this.popup = $('#ksm-popup');
            this.overlay = $('.ksm-popup-overlay');
            this.closeBtn = $('.ksm-popup-close');
            this.categoryCards = $('.ksm-category-card');
            this.backButtons = $('.ksm-back-button');
            this.addToCartBtn = $('#ksm-add-to-cart');
            this.checkoutBtn = $('#ksm-checkout');
            this.continueBtn = $('#ksm-continue-shopping');
            this.notification = $('#ksm-notification');
            
            console.log('KSM: DOM cached, popup found:', this.popup.length > 0);
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Open popup buttons - use delegation for dynamic content
            $(document).on('click', '.ksm-open-popup, .ksm-button, [data-ksm-popup]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('KSM: Button clicked');
                var category = $(this).data('category');
                self.openPopup(category);
            });
            
            // Close popup - click on X button
            $(document).on('click', '.ksm-popup-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('KSM: Close button clicked');
                self.closePopup();
            });
            
            // Close popup - click on overlay
            $(document).on('click', '.ksm-popup-overlay', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('KSM: Overlay clicked');
                self.closePopup();
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.isOpen) {
                    self.closePopup();
                }
            });
            
            // Category selection
            $(document).on('click', '.ksm-category-card', function(e) {
                e.preventDefault();
                var categoryId = $(this).data('category');
                var categoryName = $(this).data('name');
                console.log('KSM: Category selected:', categoryId);
                self.selectCategory(categoryId, categoryName);
            });
            
            // Back buttons
            $(document).on('click', '.ksm-back-button', function(e) {
                e.preventDefault();
                var target = $(this).data('target');
                self.goToStep(target);
            });
            
            // Sample selection
            $(document).on('click', '.ksm-sample-item', function(e) {
                e.preventDefault();
                self.toggleSample($(this));
            });
            
            // Add to cart
            $(document).on('click', '#ksm-add-to-cart', function(e) {
                e.preventDefault();
                self.addToCart();
            });
            
            // Checkout
            $(document).on('click', '#ksm-checkout', function(e) {
                e.preventDefault();
                window.location.href = ksm_ajax.checkout_url;
            });
            
            // Continue shopping
            $(document).on('click', '#ksm-continue-shopping', function(e) {
                e.preventDefault();
                self.showStep(2);
            });
            
            // Remove from cart
            $(document).on('click', '.ksm-cart-item-remove', function(e) {
                e.preventDefault();
                var sampleId = $(this).data('sample-id');
                self.removeFromCart(sampleId);
            });
        },
        
        // Open popup
        openPopup: function(preselectedCategory) {
            var self = this;
            
            console.log('KSM: Opening popup...');
            
            // Check if popup exists
            if (this.popup.length === 0) {
                console.error('KSM: Popup element not found! Creating dynamically...');
                this.createPopupHTML();
                this.cacheDom(); // Re-cache DOM after creating popup
            }
            
            // Show popup
            this.popup.show().addClass('active');
            $('body').addClass('ksm-popup-open').css('overflow', 'hidden');
            this.isOpen = true;
            
            // Show correct step
            if (preselectedCategory) {
                this.selectCategory(preselectedCategory, '');
            } else {
                this.showStep(1);
            }
            
            console.log('KSM: Popup opened');
        },
        
        // Close popup
        closePopup: function() {
            console.log('KSM: Closing popup...');
            
            this.popup.removeClass('active');
            $('body').removeClass('ksm-popup-open').css('overflow', '');
            this.isOpen = false;
            
            // Hide after animation
            setTimeout(() => {
                this.popup.hide();
            }, 300);
            
            this.resetSelection();
            console.log('KSM: Popup closed');
        },
        
        // Create popup HTML if not exists
        createPopupHTML: function() {
            var html = `
            <div id="ksm-popup" class="ksm-popup" style="display: none;">
                <div class="ksm-popup-overlay"></div>
                <div class="ksm-popup-container">
                    <button class="ksm-popup-close" aria-label="Sluiten">
                        <span>&times;</span>
                    </button>
                    
                    <!-- Step 1: Category Selection -->
                    <div class="ksm-popup-step" id="ksm-step-category" data-step="1">
                        <div class="ksm-popup-header">
                            <h2>Kies je materiaal</h2>
                            <p>Selecteer het type jaloezie waarvan je kleurstalen wilt ontvangen</p>
                        </div>
                        <div class="ksm-category-grid">
                            <p>Laden...</p>
                        </div>
                    </div>
                    
                    <!-- Step 2: Samples Selection -->
                    <div class="ksm-popup-step" id="ksm-step-samples" data-step="2" style="display: none;">
                        <div class="ksm-popup-header">
                            <button class="ksm-back-button" data-target="category">
                                <span>&larr;</span> Terug
                            </button>
                            <h2><span id="ksm-category-title"></span></h2>
                            <p>Selecteer de gewenste kleurstalen</p>
                        </div>
                        <div class="ksm-samples-grid" id="ksm-samples-container">
                            <!-- Samples will be loaded here -->
                        </div>
                        <div class="ksm-popup-footer">
                            <div class="ksm-selection-info">
                                <span class="ksm-selected-count">0</span> geselecteerd
                            </div>
                            <button class="ksm-btn ksm-btn-primary" id="ksm-add-to-cart" disabled>
                                Toevoegen aan winkelwagen
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Cart Overview -->
                    <div class="ksm-popup-step" id="ksm-step-cart" data-step="3" style="display: none;">
                        <div class="ksm-popup-header">
                            <button class="ksm-back-button" data-target="samples">
                                <span>&larr;</span> Terug
                            </button>
                            <h2>Winkelwagen</h2>
                            <p>Overzicht van je geselecteerde kleurstalen</p>
                        </div>
                        <div class="ksm-cart-content" id="ksm-cart-container">
                            <!-- Cart items will be loaded here -->
                        </div>
                        <div class="ksm-popup-footer">
                            <div class="ksm-cart-total">
                                <span>Totaal:</span>
                                <strong id="ksm-cart-total">€0,00</strong>
                            </div>
                            <div class="ksm-cart-actions">
                                <button class="ksm-btn ksm-btn-secondary" id="ksm-continue-shopping">
                                    Verder winkelen
                                </button>
                                <button class="ksm-btn ksm-btn-primary" id="ksm-checkout">
                                    Afrekenen
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div class="ksm-loading" id="ksm-loading" style="display: none;">
                        <div class="ksm-spinner"></div>
                        <p>Laden...</p>
                    </div>
                </div>
            </div>`;
            
            $('body').append(html);
            console.log('KSM: Popup HTML created');
            
            // Load categories
            this.loadCategories();
        },
        
        // Load categories via AJAX
        loadCategories: function() {
            var self = this;
            
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ksm_get_categories',
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.renderCategories(response.data);
                    }
                },
                error: function() {
                    console.error('KSM: Failed to load categories');
                }
            });
        },
        
        // Render categories
        renderCategories: function(categories) {
            var html = '';
            
            console.log('KSM: Rendering categories:', categories);
            
            if (!categories || categories.length === 0) {
                html = '<p style="text-align: center; padding: 40px;">Geen categorieën beschikbaar. Maak eerst categorieën aan in de admin.</p>';
            } else {
                categories.forEach(function(category) {
                    html += '<div class="ksm-category-card" data-category="' + category.id + '" data-name="' + category.name + '">';
                    
                    // Show image if available
                    if (category.image) {
                        html += '<div class="ksm-category-image">';
                        html += '<img src="' + category.image + '" alt="' + category.name + '" style="width: 100%; height: 100%; object-fit: cover;">';
                        html += '</div>';
                    } else {
                        // Default icon
                        html += '<div class="ksm-category-placeholder">';
                        html += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                        html += '<rect x="3" y="3" width="18" height="18" rx="2"/>';
                        html += '</svg>';
                        html += '</div>';
                    }
                    
                    html += '<h3>' + category.name + '</h3>';
                    
                    if (category.description) {
                        html += '<p>' + category.description + '</p>';
                    }
                    
                    html += '<span class="ksm-category-count">' + category.count + ' kleurstalen</span>';
                    html += '</div>';
                });
            }
            
            $('.ksm-category-grid').html(html);
        },
        
        // Show step
        showStep: function(step) {
            $('.ksm-popup-step').hide();
            
            switch(step) {
                case 1:
                    $('#ksm-step-category').show();
                    break;
                case 2:
                    $('#ksm-step-samples').show();
                    break;
                case 3:
                    $('#ksm-step-cart').show();
                    this.loadCart();
                    break;
            }
            
            this.currentStep = step;
        },
        
        // Go to step
        goToStep: function(target) {
            switch(target) {
                case 'category':
                    this.showStep(1);
                    break;
                case 'samples':
                    this.showStep(2);
                    break;
                case 'cart':
                    this.showStep(3);
                    break;
            }
        },
        
        // Select category
        selectCategory: function(categoryId, categoryName) {
            var self = this;
            
            console.log('KSM: Selecting category:', categoryId, categoryName);
            
            this.selectedCategory = categoryId;
            $('#ksm-category-title').text(categoryName);
            
            // Show loading
            this.showLoading();
            
            // Load samples for this category
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ksm_get_samples',
                    category_id: categoryId,
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    console.log('KSM: Samples response:', response);
                    self.hideLoading();
                    
                    if (response && response.success) {
                        if (response.data && response.data.samples) {
                            self.renderSamples(response.data.samples);
                            self.showStep(2);
                        } else {
                            console.error('KSM: No samples in response');
                            self.showNotification('Geen kleurstalen gevonden in deze categorie', 'warning');
                            // Still show step 2 but with empty message
                            self.renderSamples([]);
                            self.showStep(2);
                        }
                    } else {
                        console.error('KSM: Error response:', response);
                        self.showNotification(response.data || 'Er ging iets mis bij het laden van de kleurstalen', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('KSM: AJAX error:', status, error);
                    console.error('KSM: Response text:', xhr.responseText);
                    self.hideLoading();
                    self.showNotification('Er ging iets mis bij het laden van de kleurstalen. Probeer het opnieuw.', 'error');
                }
            });
        },
        
        // Render samples
        renderSamples: function(samples) {
            var self = this;
            var container = $('#ksm-samples-container');
            container.empty();
            
            console.log('KSM: Rendering samples:', samples);
            
            if (!samples || samples.length === 0) {
                container.html('<p style="text-align: center; padding: 40px;">Geen kleurstalen beschikbaar in deze categorie.</p>');
                return;
            }
            
            samples.forEach(function(sample) {
                var html = self.createSampleHtml(sample);
                container.append(html);
            });
            
            this.updateSelectionCount();
        },
        
        // Create sample HTML
        createSampleHtml: function(sample) {
            var html = '<div class="ksm-sample-item" data-sample-id="' + sample.id + '" data-price="' + (sample.price || 0) + '">';
            
            // Popular badge
            if (sample.popular) {
                html += '<span class="ksm-sample-popular">★ Populair</span>';
            }
            
            // Visual display
            html += '<div class="ksm-sample-colors">';
            
            // Check for images first
            if (sample.images && sample.images.length > 0) {
                html += '<img src="' + sample.images[0] + '" alt="' + sample.title + '" style="width: 100%; height: 100%; object-fit: cover;">';
            } else if (sample.colors && sample.colors.length > 0) {
                // Show colors
                if (sample.colors.length > 1) {
                    var gradient = 'linear-gradient(135deg, ' + sample.colors.join(', ') + ')';
                    html += '<div class="ksm-sample-gradient" style="background: ' + gradient + ';"></div>';
                } else {
                    html += '<div class="ksm-sample-color" style="background: ' + sample.colors[0] + ';"></div>';
                }
            } else if (sample.thumbnail) {
                html += '<img src="' + sample.thumbnail + '" alt="' + sample.title + '">';
            }
            
            html += '</div>';
            
            // Title and SKU
            html += '<div class="ksm-sample-title">' + sample.title + '</div>';
            if (sample.sku) {
                html += '<div class="ksm-sample-sku">' + sample.sku + '</div>';
            }
            
            // Price display
            if (sample.price_formatted) {
                html += '<div class="ksm-sample-price">' + sample.price_formatted + '</div>';
            } else if (sample.price) {
                html += '<div class="ksm-sample-price">€' + sample.price.toFixed(2) + '</div>';
            }
            
            html += '</div>';
            
            return html;
        },
        
        // Toggle sample selection (updated with price calculation)
        toggleSample: function($sample) {
            var sampleId = $sample.data('sample-id');
            var samplePrice = parseFloat($sample.data('price')) || 0;
            var index = this.selectedSamples.indexOf(sampleId);
            
            if (index > -1) {
                // Deselect
                this.selectedSamples.splice(index, 1);
                $sample.removeClass('selected');
            } else {
                // Check max samples
                if (this.selectedSamples.length >= this.maxSamples) {
                    this.showNotification('Maximum ' + this.maxSamples + ' kleurstalen per bestelling', 'warning');
                    return;
                }
                
                // Select
                this.selectedSamples.push(sampleId);
                $sample.addClass('selected');
            }
            
            this.updateSelectionCount();
            this.updateTotalPrice();
        },
        
        // Update total price
        updateTotalPrice: function() {
            var total = 0;
            var self = this;
            
            $('.ksm-sample-item.selected').each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                total += price;
            });
            
            // Update price display if exists
            if ($('.ksm-selection-price').length) {
                $('.ksm-selection-price').html('Totaal: €' + total.toFixed(2));
            }
        },
        
        // Update selection count
        updateSelectionCount: function() {
            $('.ksm-selected-count').text(this.selectedSamples.length);
            
            // Enable/disable add to cart button
            if (this.selectedSamples.length > 0) {
                $('#ksm-add-to-cart').prop('disabled', false);
            } else {
                $('#ksm-add-to-cart').prop('disabled', true);
            }
        },
        
        // Add to cart
        addToCart: function() {
            var self = this;
            
            if (this.selectedSamples.length === 0) {
                this.showNotification('Selecteer eerst kleurstalen', 'warning');
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ksm_add_to_cart',
                    samples: this.selectedSamples,
                    category_id: this.selectedCategory,
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        self.showStep(3);
                        
                        // Update WooCommerce cart widget if exists
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        self.showNotification(response.data || ksm_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotification(ksm_ajax.strings.error, 'error');
                }
            });
        },
        
        // Load cart
        loadCart: function() {
            var self = this;
            
            this.showLoading();
            
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ksm_get_cart',
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.renderCart(response.data);
                    } else {
                        self.showNotification(response.data || ksm_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotification(ksm_ajax.strings.error, 'error');
                }
            });
        },
        
        // Render cart
        renderCart: function(data) {
            var container = $('#ksm-cart-container');
            container.empty();
            
            if (!data.items || data.items.length === 0) {
                container.html('<p>Je winkelwagen is leeg.</p>');
                $('#ksm-cart-total').text(ksm_ajax.currency + '0,00');
                return;
            }
            
            data.items.forEach(function(item) {
                var html = self.createCartItemHtml(item);
                container.append(html);
            });
            
            $('#ksm-cart-total').html(data.total);
        },
        
        // Create cart item HTML
        createCartItemHtml: function(item) {
            var html = '<div class="ksm-cart-item" data-item-id="' + item.id + '">';
            
            // Color preview
            html += '<div class="ksm-cart-item-color"';
            if (item.colors && item.colors.length > 0) {
                if (item.colors.length > 1) {
                    html += ' style="background: linear-gradient(135deg, ' + item.colors.join(', ') + ');"';
                } else {
                    html += ' style="background: ' + item.colors[0] + ';"';
                }
            }
            html += '></div>';
            
            // Info
            html += '<div class="ksm-cart-item-info">';
            html += '<div class="ksm-cart-item-title">' + item.title + '</div>';
            if (item.sku) {
                html += '<div class="ksm-cart-item-sku">' + item.sku + '</div>';
            }
            html += '</div>';
            
            // Remove button
            html += '<button class="ksm-cart-item-remove" data-sample-id="' + item.id + '">&times;</button>';
            
            html += '</div>';
            
            return html;
        },
        
        // Remove from cart
        removeFromCart: function(sampleId) {
            var self = this;
            
            this.showLoading();
            
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ksm_remove_from_cart',
                    sample_id: sampleId,
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        self.loadCart();
                        
                        // Update WooCommerce cart widget
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        self.showNotification(response.data || ksm_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showNotification(ksm_ajax.strings.error, 'error');
                }
            });
        },
        
        // Show loading
        showLoading: function() {
            $('#ksm-loading').show();
        },
        
        // Hide loading
        hideLoading: function() {
            $('#ksm-loading').hide();
        },
        
        // Show notification
        showNotification: function(message, type) {
            // Create notification if not exists
            if (this.notification.length === 0) {
                $('body').append('<div id="ksm-notification" class="ksm-notification"><div class="ksm-notification-content"><span class="ksm-notification-icon"></span><span class="ksm-notification-message"></span></div></div>');
                this.notification = $('#ksm-notification');
            }
            
            var $notification = this.notification;
            var $message = $notification.find('.ksm-notification-message');
            var $icon = $notification.find('.ksm-notification-icon');
            
            // Set message
            $message.text(message);
            
            // Set icon based on type
            switch(type) {
                case 'success':
                    $icon.css('background', '#4CAF50').text('✓');
                    break;
                case 'warning':
                    $icon.css('background', '#ff9800').text('!');
                    break;
                case 'error':
                    $icon.css('background', '#f44336').text('✕');
                    break;
                default:
                    $icon.css('background', '#2196F3').text('i');
            }
            
            // Show notification
            $notification.show().addClass('show');
            
            // Auto hide after 3 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.hide();
                }, 300);
            }, 3000);
        },
        
        // Reset selection
        resetSelection: function() {
            this.selectedSamples = [];
            this.selectedCategory = null;
            $('.ksm-sample-item').removeClass('selected');
            this.updateSelectionCount();
        }
    };
    
    // Global reference
    window.KSM = KSM;
    
    // Initialize
    KSM.init();
    
})(jQuery);
