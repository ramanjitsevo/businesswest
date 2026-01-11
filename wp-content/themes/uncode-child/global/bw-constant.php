<?php

/** ARM Plans */
define('ARM_PLAN_STAFF', 'staff');
define('ARM_PLAN_ADMIN', 'admin');
define('ARM_PLAN_OWNER', 'owner');

/** Dashboard Tabs Names */
define('DASHBOARD_TAB_MY_BUSINESS', 'My Business');
define('DASHBOARD_TAB_MY_PROFILE', 'My Profile');
define('DASHBOARD_TAB_MY_TEAM', 'My Team');
define('DASHBOARD_TAB_HOME', 'Home');
define('DASHBOARD_TAB_EVENTS', 'Events');
define('DASHBOARD_TAB_RESOURCES', 'Resources');

/**
 * ** Dashboard Tab content heading
 */
define('DASHBOARD_UPCOMING_EVENTS', 'Upcoming Events');
define('DASHBOARD_NEWS', 'Latest Updates and News');

/**
 * ** Dashboard Welcome Text
 */
define('DASHBOARD_WELCOME', 'Welcome,');
define('DASHBOARD_WELCOME_TEXT', 'Your Business West member area – events, connections and opportunities');

/**
 * Admin and Staff registration page Ids
 */
define('ARM_STAFF_REGISTRATION_PAGE_ID', 161151); //http://businesswest.com/register-staff/
define('ARM_ADMIN_REGISTRATION_PAGE_ID', 159868); //http://businesswest.com/register-admin/

// Define Google Maps API key if not already defined elsewhere
if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', '');
}
