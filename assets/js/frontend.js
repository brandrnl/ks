/**
 * Kleurstalen Manager Frontend JavaScript
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
        
        // Initialize
        init: function() {
            this.cacheDom();
            this.bindEvents();
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
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Open popup buttons
            $(document).on('click', '.ksm-open-popup, .ksm-button', function(e) {
                e.preventDefault();
                var category = $(this).data('category');
                self.openPopup(category);
            });
            
            // Close popup
            this.closeBtn.on('click', function() {
                self.closePopup();
            });
            
            this.overlay.on('click', function() {
                self.closePopup();
            });
            
            // ESC key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.popup.hasClass('active')) {
                    self.closePopup();
                }
            });
            
            // Category selection
            this.categoryCards.on('click', function() {
                var categoryId = $(this).data('category');
                var categoryName = $(this).data('name');
                self.selectCategory(categoryId, categoryName);
            });
            
            // Back buttons
            this.backButtons.on('click', function() {
                var target = $(this).data('target');
                self.goToStep(target);
            });
            
            // Sample selection (delegated)
            $(document).on('click', '.ksm-sample-item', function() {
                self.toggleSample($(this));
            });
            
            // Add to cart
            this.addToCartBtn.on('click', function() {
                self.addToCart();
            });
            
            // Checkout
            this.checkoutBtn.on('click', function() {
                window.location.href = ksm_ajax.checkout_url;
            });
            
            // Continue shopping
            this.continueBtn.on('click', function() {
                self.showStep(2);
            });
            
            // Remove from cart
            $(document).on('click', '.ksm-cart-item-remove', function() {
                var sampleId = $(this).data('sample-id');
                self.removeFromCart(sampleId);
            });
        },
        
        // Open popup
        openPopup: function(preselectedCategory) {
            this.popup.addClass('active');
            $('body').css('overflow', 'hidden');
            
            if (preselectedCategory) {
                // If category is preselected, go directly to samples
                this.selectCategory(preselectedCategory, '');
            } else {
                // Show category selection
                this.showStep(1);
            }
        },
        
        // Close popup
        closePopup: function() {
            this.popup.removeClass('active');
            $('body').css('overflow', 'auto');
            this.resetSelection();
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
            
            this.selectedCategory = categoryId;
            $('#ksm-category-title').text(categoryName);
            
            // Show loading
            this.showLoading();
            
            // Load samples for this category
            $.ajax({
                url: ksm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ksm_get_samples',
                    category_id: categoryId,
                    nonce: ksm_ajax.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.renderSamples(response.data.samples);
                        self.showStep(2);
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
        
        // Render samples
        renderSamples: function(samples) {
            var container = $('#ksm-samples-container');
            container.empty();
            
            if (samples.length === 0) {
                container.html('<p>Geen kleurstalen beschikbaar in deze categorie.</p>');
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
            var html = '<div class="ksm-sample-item" data-sample-id="' + sample.id + '">';
            
            // Popular badge
            if (sample.popular) {
                html += '<span class="ksm-sample-popular">★ Populair</span>';
            }
            
            // Colors
            html += '<div class="ksm-sample-colors">';
            if (sample.colors && sample.colors.length > 0) {
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
            
            html += '</div>';
            
            return html;
        },
        
        // Toggle sample selection
        toggleSample: function($sample) {
            var sampleId = $sample.data('sample-id');
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
        },
        
        // Update selection count
        updateSelectionCount: function() {
            $('.ksm-selected-count').text(this.selectedSamples.length);
            
            // Enable/disable add to cart button
            if (this.selectedSamples.length > 0) {
                this.addToCartBtn.prop('disabled', false);
            } else {
                this.addToCartBtn.prop('disabled', true);
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
            
            if (data.items.length === 0) {
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
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        KSM.init();
    });
    
})(jQuery);