<?php
/**
 * BW Dashboard - Invite Role Manager
 * 
 * Adds role selection dropdown to invite form and passes role as URL parameter
 * 
 * @package Uncode Child Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add role selection dropdown to invite form via JavaScript
 */
function bw_add_invite_plan_selector_script()
{
    ?>
    <script type="text/javascript">

        // Fix for missing ARMember variables
        if (typeof __ChildDeleteText === 'undefined') {
            var __ChildDeleteText = 'Are you sure you want to delete child user?';
        }
        if (typeof __CodeRefreshText === 'undefined') {
            var __CodeRefreshText = 'Are you sure you want to re-generate invite code?';
        }
        if (typeof __CodeResendText === 'undefined') {
            var __CodeResendText = 'Are you sure you want to resend invite code?';
        }

        jQuery(document).ready(function ($) {

            // Function to add role selector
            function addRoleSelector() {

                if ($('#bw_invite_user_role').length) {
                    return;
                }               
                
                // Check if current user has staff role (and not admin/owner)
                var currentUserHasStaffPlan = <?php 
                    $user_roles = bw_current_user_plans();
                    $is_staff_only = in_array(ARM_PLAN_STAFF, $user_roles) && !in_array(ARM_PLAN_ADMIN, $user_roles) && !in_array(ARM_PLAN_OWNER, $user_roles);
                    echo json_encode($is_staff_only); 
                ?>;
                
                // Build role options based on user permissions
                var roleOptions = '<option value=""><?php _e('* Select Role', 'uncode'); ?></option>' +
                                 '<option value="<?php echo ARM_PLAN_STAFF; ?>"><?php _e('Staff Member', 'uncode'); ?></option>';
                
                // Only show admin option if user is not staff-only
                if (!currentUserHasStaffPlan) {
                    roleOptions += '<option value="<?php echo ARM_PLAN_ADMIN; ?>"><?php _e('Administrator', 'uncode'); ?></option>';
                }

                var roleSelector = '<div class="arm-df__form-field bw-invite-role-field">' +
                    '<div class="arm-df__form-field-wrap_select arm-df__form-field-wrap arm-controls">' +
                    '<div class="arm-df__form-control-wrapper">' +
                    '<select id="bw_invite_user_role" name="bw_invite_user_role_select" class="arm-df__form-control arm-df__form-control_select arm_material_input" required>' +
                    roleOptions +
                    '</select>' +
                    '</div>' +
                    '<div class="arm-df__fc--validation"></div>' +
                    '<input type="hidden" name="bw_invite_user_role" value="" id="bw_invite_user_role_hidden">' +
                    '</div>' +
                    '</div>';
                var $container = $('form[name="arm_form_gm_invite"]').find('.arm_cc_fields_container').find('.arm-df__form-group_text');
                if ($container.length) {
                    $container.append(roleSelector);

                    // Add event listener to update hidden field when selection changes
                    $(document).on('change', '#bw_invite_user_role', function () {
                        $('#bw_invite_user_role_hidden').val($(this).val());
                    });
                }
            }

            addRoleSelector();

            // Intercept invite form submission to add role data
            $(document).on('click', 'button[name*="arm_gm_invite_btn"], #arm_invite_invite_submit', function (e) {
                var roleValue = $('#bw_invite_user_role').val();

                if (!roleValue || roleValue == '') {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('<?php _e('Please select a user role', 'uncode'); ?>');
                    return false;
                }
            });

            // Intercept AJAX call to ensure role is included
            $(document).ajaxSend(function (event, xhr, settings) {
                if (settings.url && settings.url.indexOf('wp-admin/admin-ajax.php') !== -1 && settings.data) {
                    var formData = new URLSearchParams(settings.data);
                    if (formData.get('action') === 'arm_gm_invite_users') {
                        var selectedRole = $('#bw_invite_user_role').val();
                        if (selectedRole && selectedRole !== '') {
                            settings.data += '&bw_invite_user_role=' + encodeURIComponent(selectedRole);
                        }
                    }
                }
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'bw_add_invite_plan_selector_script');

/**
 * Store role with invite email using transients
 * Captures role selection when invite is sent
 */
function bw_store_invite_plan_with_email()
{
    if (!isset($_POST['bw_invite_user_role']) || empty($_POST['bw_invite_user_role'])) {
        return;
    }

    $role = sanitize_text_field($_POST['bw_invite_user_role']);
    $invited_emails = isset($_POST['invited_emails']) ? $_POST['invited_emails'] : '';

    if (empty($invited_emails)) {
        return;
    }

    // Split multiple emails and store role for each
    $emails = array_map('trim', explode(',', $invited_emails));

    foreach ($emails as $email) {
        if (!empty($email) && is_email($email)) {
            // Store role with email hash for 7 days
            $email_hash = md5(strtolower($email));
            set_transient('bw_invite_plan_' . $email_hash, $role, 30 * DAY_IN_SECONDS);

            // Also store with a temporary general key for immediate use
            set_transient('bw_last_invite_plan', $role, 300); // 5 minutes
        }
    }

    // Store role with coupon code if available
    if (isset($_POST['coupon_code'])) {
        $coupon_codes = is_array($_POST['coupon_code']) ? $_POST['coupon_code'] : array($_POST['coupon_code']);
        foreach ($coupon_codes as $coupon_code) {
            if (!empty($coupon_code)) {
                set_transient('bw_invite_plan_coupon_' . $coupon_code, $role, 30 * DAY_IN_SECONDS);
            }
        }
    }

    // Store role in a global transient for immediate access
    set_transient('bw_invite_plan_global', $role, 300);
}
add_action('wp_ajax_arm_gm_invite_users', 'bw_store_invite_plan_with_email', 1);

/**
 * Modify registration page URL based on selected role
 * This filter is called by ARMember when preparing the invite email
 */
function bw_change_registration_page_by_plan($invite_link, $parent_user_id, $page_id)
{
    // Extract coupon code from current link to get role
    $coupon_code = '';
    if (preg_match('/arm_invite_code=([A-Za-z0-9]+)/', $invite_link, $matches)) {
        $coupon_code = $matches[1];
    }

    // Get role from coupon code first, fallback to other methods
    $role = '';
    if (!empty($coupon_code)) {
        $role = get_transient('bw_invite_plan_coupon_' . $coupon_code);
    }

    if (empty($role)) {
        $role = get_transient('bw_last_invite_plan');
    }

    if (empty($role)) {
        $role = get_transient('bw_invite_plan_global');
    }

    if (empty($role)) {
        $role = get_transient('bw_invite_plan_' . md5($_POST['invited_emails'] ?? ''));
    }

    if (empty($role)) {
        return $invite_link;
    }

    // Define registration page IDs for each role    
    $registration_pages = array(
        ARM_PLAN_ADMIN => ARM_ADMIN_REGISTRATION_PAGE_ID,
        ARM_PLAN_STAFF => ARM_STAFF_REGISTRATION_PAGE_ID
    );

    // Get the registration page ID for this role
    $new_page_id = isset($registration_pages[$role]) ? $registration_pages[$role] : 0;

    if ($new_page_id > 0 && $new_page_id != $page_id) {
        // Build new link with different page
        $new_link = get_permalink($new_page_id);

        if (!empty($coupon_code)) {
            $new_link = add_query_arg('arm_invite_code', $coupon_code, $new_link);
        }

        return $new_link;
    }

    return $invite_link;    
}
add_filter('arm_modify_redirection_page_external', 'bw_change_registration_page_by_plan', 99, 3);
