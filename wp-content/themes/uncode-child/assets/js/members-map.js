/**
 * Business Members Directory - Map & Interactivity
 * 
 * Handles Google Maps integration, marker placement, and interactive features
 * Synchronizes map pins with profile cards
 * 
 * @package BusinessWest
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    var map;
    var markers = [];
    var infoWindows = [];
    var currentPage = 1;
    var membersData = [];
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Load members data first
        loadMembersFromJSON();
        
        // Wait for Google Maps API to load
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initMembersDirectory();
        } else {
            console.warn('Google Maps API not loaded. Please add your API key to wp-config.php');
            $('#bw-map-loading').html('<p style="padding: 20px; text-align: center; color: #d63638;"><strong>Map unavailable.</strong></p>');
            
            // Still bind events for cards even without map
            bindEvents();
        }
    });

    /**
     * Initialize the members directory
     */
    function initMembersDirectory() {
        // Load initial members data from JSON
        loadMembersFromJSON();
        
        // Initialize Google Map
        initMap();
        
        // Bind event listeners
        bindEvents();
        
        // Hide loading indicator
        $('#bw-map-loading').fadeOut();
    }

    /**
     * Load members data from JSON script tag
     */
    function loadMembersFromJSON() {
        var jsonData = $('#bw-members-json').text();
        if (jsonData) {
            try {
                membersData = JSON.parse(jsonData);
            } catch (e) {
                console.error('Error parsing members JSON:', e);
                membersData = [];
            }
        }
    }

    /**
     * Initialize Google Map
     */
    function initMap() {
        // Filter members who have valid coordinates
        var membersWithCoords = membersData.filter(function(member) {
            return member.latitude && member.longitude && member.latitude !== 0 && member.longitude !== 0;
        });
        
        if (!membersWithCoords || membersWithCoords.length === 0) {
            // Use default coordinates if no members have location data
            var defaultLat = parseFloat(bwMembersData.default_lat) || 51.4545;
            var defaultLng = parseFloat(bwMembersData.default_lng) || -2.5879;
            
            map = new google.maps.Map(document.getElementById('bw-members-map'), {
                zoom: parseInt(bwMembersData.default_zoom) || 10,
                center: { lat: defaultLat, lng: defaultLng },
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
            });
            
            // Show message on map
            var centerMessage = new google.maps.InfoWindow({
                content: '<div style="padding: 10px; text-align: center;"><strong>No member locations available</strong><br>Members without coordinates will appear in the grid below.</div>',
                position: { lat: defaultLat, lng: defaultLng }
            });
            centerMessage.open(map);
            return;
        }

        // Calculate bounds for all markers
        var bounds = new google.maps.LatLngBounds();
        
        // Create map centered on first member with coordinates
        var firstMember = membersWithCoords[0];
        map = new google.maps.Map(document.getElementById('bw-members-map'), {
            zoom: 10,
            center: { lat: firstMember.latitude, lng: firstMember.longitude },
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true,
        });

        // Add markers only for members with coordinates
        membersWithCoords.forEach(function(member) {
            addMarker(member);
            bounds.extend(new google.maps.LatLng(member.latitude, member.longitude));
        });

        // Fit map to show all markers
        if (membersWithCoords.length > 1) {
            map.fitBounds(bounds);
        }
    }

    /**
     * Add a marker to the map for a member
     */
    function addMarker(member) {
        var position = { lat: member.latitude, lng: member.longitude };
        
        // Create marker
        var marker = new google.maps.Marker({
            position: position,
            map: map,
            title: member.business_name,
            memberId: member.id,
            animation: google.maps.Animation.DROP,
        });

        // Create info window content
        var infoWindowContent = `
            <div class="bw-map-info-window">
                <div class="bw-info-logo">
                    <img src="${member.logo_url}" alt="${member.business_name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;" />
                </div>
                <div class="bw-info-content">
                    <h4 style="margin: 0 0 5px 0; font-size: 16px;">
                        <a href="${member.profile_url}" target="_blank" style="color: #0073aa; text-decoration: none;">
                            ${member.business_name}
                        </a>
                    </h4>
                    ${member.industry ? `<p style="margin: 0 0 5px 0; font-size: 12px; color: #666;"><strong>${member.industry}</strong></p>` : ''}
                    ${member.address ? `<p style="margin: 0; font-size: 12px; color: #666;">${member.address}</p>` : ''}
                    <p style="margin: 10px 0 0 0;">
                        <a href="${member.profile_url}" target="_blank" class="button" style="display: inline-block; padding: 5px 10px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-size: 12px;">
                            View Profile
                        </a>
                    </p>
                </div>
            </div>
        `;

        // Create info window
        var infoWindow = new google.maps.InfoWindow({
            content: infoWindowContent
        });

        // Add click listener to marker
        marker.addListener('click', function() {
            // Close all other info windows
            closeAllInfoWindows();
            
            // Open this info window
            infoWindow.open(map, marker);
            
            // Highlight corresponding card
            highlightCard(member.id);
            
            // Scroll to card
            scrollToCard(member.id);
        });

        // Store marker and info window
        markers.push(marker);
        infoWindows.push(infoWindow);
    }

    /**
     * Close all info windows
     */
    function closeAllInfoWindows() {
        infoWindows.forEach(function(infoWindow) {
            infoWindow.close();
        });
    }

    /**
     * Highlight a member card
     */
    function highlightCard(memberId) {
        // Remove highlight from all cards
        $('.bw-member-card').removeClass('highlighted');
        
        // Add highlight to specific card
        $('.bw-member-card[data-member-id="' + memberId + '"]').addClass('highlighted');
    }

    /**
     * Scroll to a member card
     */
    function scrollToCard(memberId) {
        var $card = $('.bw-member-card[data-member-id="' + memberId + '"]');
        if ($card.length) {
            $('html, body').animate({
                scrollTop: $card.offset().top - 100
            }, 500);
        }
    }

    /**
     * Pan map to marker and show info
     */
    function showMarkerInfo(memberId) {
        var marker = markers.find(function(m) {
            return m.memberId == memberId;
        });

        if (marker) {
            // Pan to marker
            map.panTo(marker.getPosition());
            map.setZoom(15);

            // Find and open info window
            var index = markers.indexOf(marker);
            if (index !== -1) {
                closeAllInfoWindows();
                infoWindows[index].open(map, marker);
            }

            // Bounce marker
            marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(function() {
                marker.setAnimation(null);
            }, 1400);
        }
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Search input - debounced
        var searchTimeout;
        $('#bw-member-search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performFilter();
            }, 500);
        });
       
        // Show on map button click
        $(document).on('click', '.bw-show-map-btn', function() {
            var memberId = $(this).data('member-id');
            showMarkerInfo(memberId);
            
            // Scroll to map
            $('html, body').animate({
                scrollTop: $('#bw-members-map').offset().top - 100
            }, 500);
        });

        // Card click - highlight marker
        $(document).on('click', '.bw-member-card', function(e) {
            // Don't trigger if clicking on links or buttons
            if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) {
                return;
            }
            
            var memberId = $(this).data('member-id');
            highlightCard(memberId);
            showMarkerInfo(memberId);
        });

        // Pagination buttons
        $(document).on('click', '.bw-page-btn', function() {
            var action = $(this).data('page');
            if (action === 'next') {
                currentPage++;
            } else if (action === 'prev' && currentPage > 1) {
                currentPage--;
            }
            performFilter();
        });
    }

    /**
     * Perform filtering with AJAX
     */
    function performFilter() {
        var searchTerm = $('#bw-member-search').val();
        var industry = $('#bw-industry-filter').val();

        // Show loading indicators
        $('#bw-grid-loading').show();
        $('#bw-members-grid').css('opacity', '0.5');

        // AJAX request
        $.ajax({
            url: bwMembersData.ajaxurl,
            type: 'POST',
            data: {
                action: 'bw_filter_members',
                nonce: bwMembersData.nonce,
                search: searchTerm,
                industry: industry,
                paged: currentPage
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    membersData = data.members;

                    // Update results count
                    $('#bw-results-number').text(data.members.length);
                    $('#bw-total-count').text(data.total);

                    // Update grid
                    updateMembersGrid(data.members);

                    // Update map markers
                    updateMapMarkers(data.members);

                    // Update pagination
                    updatePagination(data.current_page, data.pages);
                } else {
                    console.error('Filter error:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                // Hide loading indicators
                $('#bw-grid-loading').hide();
                $('#bw-members-grid').css('opacity', '1');
            }
        });
    }

    /**
     * Update members grid with filtered results
     */
    function updateMembersGrid(members) {
        var $grid = $('#bw-members-grid');
        
        if (members.length === 0) {
            $grid.html('<div class="bw-no-results"><p>No business members found. Please try a different search or filter.</p></div>');
            return;
        }

        // Build HTML for member cards
        var html = '';
        members.forEach(function(member) {
            html += buildMemberCardHTML(member);
        });

        $grid.html(html);
    }

    /**
     * Build HTML for a single member card
     */
    function buildMemberCardHTML(member) {
        var bannerHTML = '<div class="bw-banner-placeholder"></div>';
        
        var logoHTML = member.logo_url
            ? `<div class="bw-member-logo"><img src="${escapeHtml(member.logo_url)}" alt="${escapeHtml(member.business_name)} logo" class="bw-logo-image" onerror="this.onerror=null; this.src='https://www.gravatar.com/avatar/?d=mystery&amp;s=150';" /></div>`
            : `<div class="bw-member-logo"><img src="https://www.gravatar.com/avatar/?d=mystery&amp;s=150" alt="${escapeHtml(member.business_name)} logo" class="bw-logo-image" /></div>`;
        
        var memberPlanHTML = member.member_plan
            ? `<div class="bw-member-plan"><span class="bw-plan-badge">${member.member_plan}</span></div>`
            : '';
        
        var companyHTML = member.company
            ? `<div class="bw-member-company"><span class="bw-company-text">${member.company}</span></div>`
            : '';

        var industryHTML = member.industry 
            ? `<div class="bw-member-industry"><span class="bw-industry-text">${member.industry}</span></div>`
            : '';
        
        var phoneHTML = member.phone
            ? `<div class="bw-member-phone"><a href="tel:${member.phone}" class="bw-phone-link">${member.phone}</a></div>`
            : '';        
        
        // Only show "Show on Map" button if member has coordinates
        var hasCoordinates = member.latitude && member.longitude && member.latitude !== 0 && member.longitude !== 0;
        var mapButtonHTML = hasCoordinates 
            ? `<button type="button" class="bw-show-map-btn" data-member-id="${member.id}" aria-label="Show on map">
                <span class="dashicons dashicons-location-alt"></span> Show on Map
               </button>`
            : '';

        return `
            <div class="bw-member-card" data-member-id="${member.id}" data-lat="${member.latitude}" data-lng="${member.longitude}">
                <div class="bw-member-banner">${bannerHTML}</div>
                ${logoHTML}
                <div class="bw-member-content">
                    <h4 class="bw-member-name">
                        <a href="${escapeHtml(member.profile_url)}" class="bw-member-link" target="_blank" rel="noopener">${escapeHtml(member.business_name)}</a>
                    </h4>
                    ${memberPlanHTML}
                    ${companyHTML}
                    ${industryHTML}
                    ${phoneHTML}                   
                    
                </div>
                <div class="bw-member-actions">
                    <a href="${escapeHtml(member.profile_url)}" class="bw-view-profile-btn" target="_blank" rel="noopener">View Profile</a>
                    ${mapButtonHTML}
                </div>
            </div>
        `;
    }

    /**
     * Update map markers with filtered results
     */
    function updateMapMarkers(members) {
        // Skip if map is not initialized
        if (!map) {
            return;
        }
        
        // Clear existing markers
        markers.forEach(function(marker) {
            marker.setMap(null);
        });
        markers = [];
        infoWindows = [];

        // Filter members with valid coordinates
        var membersWithCoords = members.filter(function(member) {
            return member.latitude && member.longitude && member.latitude !== 0 && member.longitude !== 0;
        });
        
        // Add new markers only for members with coordinates
        if (membersWithCoords.length === 0) {
            // Center on default location if no members have coordinates
            var defaultLat = parseFloat(bwMembersData.default_lat) || 51.4545;
            var defaultLng = parseFloat(bwMembersData.default_lng) || -2.5879;
            map.setCenter({ lat: defaultLat, lng: defaultLng });
            map.setZoom(parseInt(bwMembersData.default_zoom) || 10);
            return;
        }

        var bounds = new google.maps.LatLngBounds();

        membersWithCoords.forEach(function(member) {
            addMarker(member);
            bounds.extend(new google.maps.LatLng(member.latitude, member.longitude));
        });

        // Fit map to show all markers
        if (membersWithCoords.length > 1) {
            map.fitBounds(bounds);
        } else if (membersWithCoords.length === 1) {
            map.setCenter({ lat: membersWithCoords[0].latitude, lng: membersWithCoords[0].longitude });
            map.setZoom(15);
        }
    }

    /**
     * Update pagination controls
     */
    function updatePagination(current, total) {
        if (total <= 1) {
            $('#bw-pagination').hide();
            return;
        }

        $('#bw-pagination').show();
        $('#bw-current-page').text(current);
        $('#bw-total-pages').text(total);

        // Update button states
        if (current === 1) {
            $('.bw-prev-btn').prop('disabled', true);
        } else {
            $('.bw-prev-btn').prop('disabled', false);
        }

        if (current >= total) {
            $('.bw-next-btn').prop('disabled', true);
        } else {
            $('.bw-next-btn').prop('disabled', false);
        }
    }

})(jQuery);
