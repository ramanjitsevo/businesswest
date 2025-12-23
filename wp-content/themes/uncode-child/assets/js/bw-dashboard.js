(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize dashboard
        initDashboard();
        
        // Tab switching functionality
        function initDashboard() {
            // Parent tab with submenu click handler
            $('.bw-tab-parent').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSubmenu($(this));
            });
            
            // Regular tab and submenu tab click handler
            $('.bw-tab:not(.bw-tab-parent)').on('click', function(e) {
                e.preventDefault();
                
                // Check if tab has external link
                if ($(this).hasClass('bw-tab-link')) {
                    var link = $(this).data('link');
                    var target = $(this).data('target') || '_self';
                    
                    if (link) {
                        window.open(link, target);
                        return;
                    }
                }
                
                switchTab($(this));
            });
            
            // Set initial active tab if none is set
            if ($('.bw-tab.bw-active').length === 0 && $('.bw-tab').length > 0) {
                $('.bw-tab:first').addClass('bw-active');
                $('.bw-tab-pane:first').addClass('bw-active');
            }
            
            // Show submenu if first active tab is a submenu
            if ($('.bw-submenu-tab.bw-active').length > 0) {
                var $activeSubmenu = $('.bw-submenu-tab.bw-active').closest('.bw-submenu-container');
                $activeSubmenu.addClass('bw-show');
                var parentTab = $activeSubmenu.data('parent');
                $('.bw-tab-parent[data-tab="' + parentTab + '"]').addClass('bw-submenu-open');
            }
        }
        
        // Toggle submenu visibility
        function toggleSubmenu($tab) {
            var tabId = $tab.data('tab');
            var $submenu = $('.bw-submenu-container[data-parent="' + tabId + '"]');
            
            if ($submenu.hasClass('bw-show')) {
                $submenu.removeClass('bw-show');
                $tab.removeClass('bw-submenu-open');
            } else {
                // Close other submenus
                $('.bw-submenu-container').removeClass('bw-show');
                $('.bw-tab-parent').removeClass('bw-submenu-open');
                
                // Open this submenu
                $submenu.addClass('bw-show');
                $tab.addClass('bw-submenu-open');
                
                // Activate first submenu item
                var $firstSubmenuTab = $submenu.find('.bw-submenu-tab:first');
                if ($firstSubmenuTab.length > 0) {
                    switchTab($firstSubmenuTab);
                }
            }
        }
        
        // Switch to a specific tab
        function switchTab($tab) {
            const tabId = $tab.data('tab');
            
            // Update active tab in navigation (only within same level)
            if ($tab.hasClass('bw-submenu-tab')) {
                // Submenu tab - only deactivate other submenu tabs
                $('.bw-submenu-tab').removeClass('bw-active');
            } else {
                // Regular tab - deactivate all non-parent tabs
                $('.bw-tab:not(.bw-tab-parent)').removeClass('bw-active');
                
                // Close all submenus
                $('.bw-submenu-container').removeClass('bw-show');
                $('.bw-tab-parent').removeClass('bw-submenu-open');
            }
            
            $tab.addClass('bw-active');
            
            // Hide all tab panes and show the selected one
            $('.bw-tab-pane').removeClass('bw-active');
            $('#tab-' + tabId).addClass('bw-active');
            
            // Add animation effect
            $('#tab-' + tabId).hide().fadeIn(300);
        }
        
        // Public methods
        window.BWDashboard = {
            switchTab: switchTab,
            toggleSubmenu: toggleSubmenu
        };
    });
    
})(jQuery);