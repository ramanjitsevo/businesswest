<?php
/**
 * BW Dashboard - Tabs Component
 *
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the dashboard tabs based on user role
 */
function bw_render_tabs($user_roles)
{

    // Define tabs with role permissions
    $tabs = array(
        'dashboard' => array(
            'title' => 'Home',
            'icon' => '<i class="fa fa-home" role="presentation"></i>',
            'roles' => array('administrator', 'subscriber'),
            'content' => bw_dashboard_home_content()
        ),
        'events' => array(
            'title' => 'Events',
            'icon' => '<i class="fa fa-calendar" role="presentation"></i>',
            'roles' => array('administrator', 'subscriber'),
            'has_submenu' => false,
            // 'submenus' => array(
            //     'upcoming-events' => array(
            //         'title' => 'Upcoming Events',
            //         'icon' => '<i class="fa fa-clock-o" role="presentation"></i>',
            //         'roles' => array('administrator'),
            //         'content' => do_shortcode('[tribe_events view="list"]')
            //     ),
            //     'calendar' => array(
            //         'title' => 'Calendar',
            //         'icon' => '<i class="fa fa-calendar-o" role="presentation"></i>',
            //         'roles' => array('administrator'),
            //         'content' => do_shortcode("[tribe_mini_calendar]")
            //     ),
            //     'manage-events' => array(
            //         'title' => 'Manage Events',
            //         'icon' => '<i class="fa fa-cog" role="presentation"></i>',
            //         'roles' => array('administrator'),
            //         'content' => '[bw_manage_events_content]',
            //         'link' => '/wp-admin/edit.php?post_type=tribe_events',
            //         'target' => '_blank'
            //     )
            // ),
            'content' => do_shortcode('[tribe_events view="list"]')
        ),
        //Hide entirely for Phase 1
        // 'classifieds' => array(
        //     'title' => 'Classifieds',
        //     'icon' => '<i class="fa fa-bullhorn" role="presentation"></i>',
        //     'roles' => array('administrator', 'subscriber'),
        //     'content' => ''
        // ),
        'my-profile' => array(
            'title' => 'My Profile',
            'icon' => '<i class="fa fa-user" role="presentation"></i>',
            'roles' => array('administrator', 'subscriber'),
            'content' => bw_dashboard_home_content()
        ),
        'my-business' => array(
            'title' => 'My Business',
            'icon' => '<i class="fa fa-building" role="presentation"></i>',
            'roles' => array('administrator', 'subscriber'),
            'content' => do_shortcode('[arm_template type="profile" id="1"]')
        ),
        'my-team' => array(
            'title' => 'My Team',
            'icon' => '<i class="fa fa-users" role="presentation"></i>',
            'roles' => array('administrator'),
            'content' => ''
        )
    );

    echo '<div class="bw-dashboard-content">';
    echo '<div class="bw-tabs-container bw-flex bw-w-100">';

    // Render tab navigation
    echo '<div class="bw-tabs-nav">';
    $first_tab = true;
    foreach ($tabs as $tab_id => $tab) {

        if (!empty(array_intersect($user_roles, $tab['roles']))) {

            $active_class = $first_tab ? 'bw-active' : '';

            // Check if tab has submenus
            if (!empty($tab['has_submenu']) && !empty($tab['submenus'])) {
                echo '<div class="bw-tab bw-tab-parent ' . $active_class . '" data-tab="' . $tab_id . '">';
                echo '<span class="bw-tab-icon">' . $tab['icon'] . '</span>';
                echo '<span class="bw-tab-title">' . $tab['title'] . '</span>';
                echo '<span class="bw-tab-arrow"><i class="fa fa-chevron-down" role="presentation"></i></span>';
                echo '</div>';

                // Render submenus
                echo '<div class="bw-submenu-container" data-parent="' . $tab_id . '">';
                $first_submenu = true;

                foreach ($tab['submenus'] as $submenu_id => $submenu) {
                    if (!empty(array_intersect($user_roles, $submenu['roles']))) {
                        $submenu_active = ($first_tab && $first_submenu) ? 'bw-active' : '';

                        // Check if submenu has external link
                        if (!empty($submenu['link'])) {
                            $target = !empty($submenu['target']) ? $submenu['target'] : '_self';
                            echo '<div class="bw-tab bw-submenu-tab bw-tab-link ' . $submenu_active . '" data-tab="' . $tab_id . '-' . $submenu_id . '" data-link="' . esc_url($submenu['link']) . '" data-target="' . esc_attr($target) . '">';
                        } else {
                            echo '<div class="bw-tab bw-submenu-tab ' . $submenu_active . '" data-tab="' . $tab_id . '-' . $submenu_id . '">';
                        }

                        echo '<span class="bw-tab-icon">' . $submenu['icon'] . '</span>';
                        echo '<span class="bw-tab-title">' . $submenu['title'] . '</span>';
                        echo '</div>';
                        $first_submenu = false;
                    }
                }

                echo '</div>';
            } else {

                echo '<div class="bw-tab ' . $active_class . '" data-tab="' . $tab_id . '">';
                    echo '<span class="bw-tab-icon">' . $tab['icon'] . '</span>';
                    echo '<span class="bw-tab-title">' . $tab['title'] . '</span>';
                echo '</div>';

            }

            $first_tab = false;
        }

    }
    echo '</div>';

    // Render tab content
    echo '<div class="bw-tabs-content">';
    $first_content = true;
    foreach ($tabs as $tab_id => $tab) {

        // Check if current user role can view this tab
        if (!empty(array_intersect($user_roles, $tab['roles']))) {

            // Check if tab has submenus
            if (!empty($tab['has_submenu']) && !empty($tab['submenus'])) {
                $first_submenu_content = true;
                foreach ($tab['submenus'] as $submenu_id => $submenu) {
                    if (!empty(array_intersect($user_roles, $submenu['roles']))) {
                        $active_class = ($first_content && $first_submenu_content) ? 'bw-active' : '';
                        echo '<div class="bw-tab-pane ' . $active_class . '" id="tab-' . $tab_id . '-' . $submenu_id . '">';
                        echo $submenu['content'];
                        echo '</div>';
                        $first_submenu_content = false;
                        $first_content = false;
                    }
                }
            } else {
                $active_class = $first_content ? 'bw-active' : '';
                echo '<div class="bw-tab-pane ' . $active_class . '" id="tab-' . $tab_id . '">';
                echo $tab['content'];
                echo '</div>';
                $first_content = false;
            }
        }

    }
    echo '</div>'; // .bw-tabs-content

    echo '</div>'; // .bw-tabs-container
    echo '</div>'; // .bw-dashboard-content
}