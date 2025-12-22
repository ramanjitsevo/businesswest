<?php
global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $is_multiple_membership_feature, $arm_datepicker_loaded, $arm_pay_per_post_feature;
$arm_datepicker_loaded = 1;

$get_action = isset($_GET['action']) ? sanitize_text_field( $_GET['action'] ) : '';
if($arm_pay_per_post_feature->isPayPerPostFeature && !empty($get_action) && ($get_action == "pay_per_post_report"))
{
    $all_active_plans = $arm_subscription_plans->arm_get_paid_post_data();
}
else
{
    $all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans();
}

$payment_gateways = $arm_payment_gateways->arm_get_all_payment_gateways_for_setup();


$year = date("Y");
$month = date("m");
$month_label = "";
$to_year = $year - 12;
$yearLists = "";
$monthLists = "";
$gateways_list = "";

$firstmonth = date('n', strtotime("January"));
for($i = $firstmonth; $i <= 12; $i++){
    // $month_num = $i+1;
    $dateObj   = DateTime::createFromFormat('!m', $i);
    $month_name = $dateObj->format('F');
    $monthLists .= '<li data-label="' . $month_name . '" data-value="'.$i.'">' . $month_name . '</li>';
}

for($i=$year; $i>$year - 12; $i--) {
    $yearLists .= '<li data-label="' . $i . '" data-value="'.$i.'">' . $i . '</li>';
}


$gateways_list = '<li data-label="' . addslashes( esc_attr__('All Gateways', 'ARMember')) . '" data-value="">' . addslashes( esc_html__('All Gateways', 'ARMember') ) . '</li>';
if(!empty($payment_gateways)) {
    foreach ($payment_gateways as $key => $gateways) {
        $gateways_list .= '<li data-label="' . $gateways['gateway_name'] . '" data-value="' . $key . '">' . $gateways['gateway_name'] . '</li>';    
    }
}

$gateways_list .= "<li data-label='".esc_attr__('Manual','ARMember')."' data-value='manual'>".esc_html__('Manual','ARMember')."</li>";

/*$is_wc_feature = get_option('arm_is_woocommerce_feature');
if( '1' == $is_wc_feature ){
    $gateways_list .= '<li data-label="' . addslashes( esc_attr__('WooCommerce', 'ARMember') ) . '" data-value="woocommerce">'.addslashes( esc_html__('WooCommerce','ARMember') ).'</li>';
}*/

//echo "gateways_list : <br>".$gateways_list;die;
$plansLists = '<li data-label="' . addslashes( esc_attr__('All Plans', 'ARMember')) . '" data-value="">' . addslashes( esc_html__('All Plans', 'ARMember') ) . '</li>';
if (!empty($all_active_plans)) {
    foreach ($all_active_plans as $p) {
        $p_id = $p['arm_subscription_plan_id'];
        $plansLists .= '<li data-label="' . esc_attr($p['arm_subscription_plan_name']) . '" data-value="' . $p_id . '">' . esc_attr($p['arm_subscription_plan_name']) . '</li>';
    }
}

$page_title = "";

if (isset($get_action) && $get_action == 'member_report' ) {
    $page_title = esc_html__("Membership Report", "ARMember");
}
if (isset($get_action) && $get_action == 'payment_report' ) {
    $page_title = esc_html__("Payments Report", "ARMember");
}
if (isset($get_action) && $get_action == 'pay_per_post_report' ) {
    $page_title = esc_html__("Paid Post Report", "ARMember");
}
if (isset($get_action) && $get_action == 'coupon_report' ) {
    $page_title = esc_html__("Coupon Report", "ARMember");
}

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();

$log_id = isset($_REQUEST['log_id']) ? $_REQUEST['log_id'] : 0;

if(isset($_POST["arm_export_report_data"]) && $_POST["arm_export_report_data"] == 1) {//phpcs:ignore
    $type = sanitize_text_field($_POST['type']);//phpcs:ignore
    $graph_type = sanitize_text_field($_POST['graph_type']);//phpcs:ignore
    $arm_report_type = isset($_POST['arm_report_type']) ? sanitize_text_field($_POST['arm_report_type']) : '';//phpcs:ignore
    $is_export_to_csv = isset($_POST['is_export_to_csv']) ? intval($_POST['is_export_to_csv']) : false;//phpcs:ignore
    $is_pagination = false;
    require_once(MEMBERSHIP_VIEWS_DIR . '/arm_graph_ajax.php');
    exit;
}

?>

<?php
    $backToListingIcon = MEMBERSHIPLITE_IMAGES_URL . '/back_to_listing_arrow.png';
    if (is_rtl()) {
        $backToListingIcon = MEMBERSHIPLITE_IMAGES_URL . '/back_to_listing_arrow_right.png';
    }
?>
<div class="wrap arm_page arm_report_analytics_main_wrapper">
    <?php
    if ($setact != 1) {
        if($ARMember->arm_licence_notice_status()){
            $admin_css_url = admin_url('admin.php?page=arm_manage_license');
            $nonce = wp_create_nonce('arm_wp_nonce');
            ?>
            <div class="armember_notice_warning armember_licence_notice_warning">ARMember License is not activated. Please activate license from <a href="<?php echo esc_url($admin_css_url); ?>">here</a><span class="armember_close_licence_notice_icon" id="armember_close_licence_notice_icon" data-nonce="<?php echo $nonce;?>" data-type="armember" title="<?php esc_html_e('Dismiss for 7 days', 'ARMember'); ?>"></span></div>
    <?php }
    } ?>
    <div class="content_wrapper arm_report_analytics_content arm_report_analytics_summary" id="content_wrapper">
        <div class="page_title">
            <span><?php echo esc_html($page_title); ?></span>
            <div class="armclear"></div>
            <?php 
            if ($get_action == 'member_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_members" onclick="javascript:arm_change_graph('daily', 'members');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_members" onclick="javascript:arm_change_graph('monthly', 'members');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_members" onclick="javascript:arm_change_graph('yearly', 'members');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>                   
                    </div>
                    
                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">
                                
                            </div>
                        </div>
        
        
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_150">
                                    <dt><span><?php echo $month_label; //phpcs:ignore?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
        
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_100">
                                    <dt><span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
        
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_200">
                                    <dt><span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <input type="button" class="arm_cancel_btn arm_margin_0 arm_margin_right_0" id="arm_report_export_button" value="<?php esc_html_e('Export to CSV','ARMember'); ?>">
                            <input type="hidden" value="monthly" name="armgraphval_members" id="armgraphval_members" />
                        </div>
                    </div>
                </div>
            <?php } 
            else if ($get_action == 'payment_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_payment_history" onclick="javascript:arm_change_graph('daily', 'payment_history');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_payment_history" onclick="javascript:arm_change_graph('monthly', 'payment_history');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_payment_history" onclick="javascript:arm_change_graph('yearly', 'payment_history');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>

                    </div>

                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_150">
                                    <dt><span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_100">
                                    <dt><span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_200">
                                    <dt><span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_gateway_filter_item">
                            <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                <dl class="arm_selectbox arm_width_150">
                                    <dt><span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                            <?php echo $gateways_list; //phpcs:ignore?>
                                            <?php ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <input type="button" class="arm_cancel_btn arm_margin_0 arm_margin_right_0" id="arm_report_export_button" value="<?php esc_html_e('Export to CSV','ARMember'); ?>">
                            <input type="hidden" value="monthly" name="armgraphval_payment_history" id="armgraphval_payment_history" />
                        </div>
                    </div>
                </div>
                <?php
            }
            else if ($get_action == 'pay_per_post_report') { ?>
                <div class="sltstandard">
                    <div class="arm_report_chart_type">
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="daily_pay_per_post_report" onclick="javascript:arm_change_graph('daily', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="monthly_pay_per_post_report" onclick="javascript:arm_change_graph('monthly', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                    
                        <a href="javascript:void(0);" class="btn_chart_type" id="yearly_pay_per_post_report" onclick="javascript:arm_change_graph('yearly', 'pay_per_post_report');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>
                    </div>

                    <div class="arm_report_analtics_filter_div">
                        <div class="arm_filter_div" id="arm_date_filter_item">
                            <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_month_filter_item">
                            <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                <dl class="arm_selectbox arm_width_150">
                                    <dt><span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                            <?php echo $monthLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_year_filter_item">
                            <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                <dl class="arm_selectbox arm_width_100">
                                    <dt><span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                            <?php echo $yearLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_plan_filter_item">
                            <div id="arm_plan_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_plan_filter" class="arm_plan_filter" value="">
                                <dl class="arm_selectbox arm_width_200">
                                    <dt><span><?php esc_html_e('All Plans', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_plan_filter" data-placeholder="Select Status">
                                            <?php echo $plansLists; //phpcs:ignore?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
    
                        <div class="arm_filter_div" id="arm_gateway_filter_item">
                            <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                <dl class="arm_selectbox arm_width_150">
                                    <dt><span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                    <dd>
                                        <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                            <?php echo $gateways_list; //phpcs:ignore?>
                                            <?php ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="arm_filter_div">
                            <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                            <input type="button" class="arm_cancel_btn arm_margin_right_0 arm_margin_0" id="arm_report_export_button" value="<?php esc_html_e('Export to CSV','ARMember'); ?>">
                            <input type="hidden" value="monthly" name="armgraphval_pay_per_post_report" id="armgraphval_pay_per_post_report" />
                        </div>
                    </div>
                </div>
            <?php } else if ($get_action == 'coupon_report') { ?>
                    <div class="sltstandard">
                        <div class="arm_report_chart_type">
                            <a href="javascript:void(0);" class="btn_chart_type" id="daily_coupon_report" onclick="javascript:arm_change_graph('daily', 'coupon_report');"><?php echo addslashes(esc_html__('Hourly', 'ARMember')); //phpcs:ignore?></a>
                        
                            <a href="javascript:void(0);" class="btn_chart_type" id="monthly_coupon_report" onclick="javascript:arm_change_graph('monthly', 'coupon_report');"><?php echo addslashes(esc_html__('Daily', 'ARMember')); //phpcs:ignore?></a>
                        
                            <a href="javascript:void(0);" class="btn_chart_type" id="yearly_coupon_report" onclick="javascript:arm_change_graph('yearly', 'coupon_report');"><?php echo addslashes(esc_html__('Monthly', 'ARMember')); //phpcs:ignore?></a>
                        </div>
                        <div class="arm_report_analtics_filter_div arm_coupon_report_filter">
                            <div class="arm_filter_div" id="arm_date_filter_item">
                                <div id="arm_date_filter_item" class="arm_filter_status_box arm_import_export_date_fields">
                                    <input type="text" id="arm_date_filter" placeholder="Select date" class="arm_datepicker_filter" value="" autocomplete="off">  
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_month_filter_item">
                                <div id="arm_month_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_month_filter" class="arm_month_filter" value="<?php echo esc_attr($month); ?>">
                                    <dl class="arm_selectbox arm_width_150">
                                        <dt><span><?php echo esc_html($month_label); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul data-id="arm_month_filter" data-placeholder="Select Status">
                                                <?php echo $monthLists; //phpcs:ignore?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_year_filter_item">
                                <div id="arm_year_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_year_filter" class="arm_year_filter" value="<?php echo esc_attr($year); ?>">
                                    <dl class="arm_selectbox arm_width_100">
                                        <dt><span><?php echo esc_html($year); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul data-id="arm_year_filter" data-placeholder="Select Status">
                                                <?php echo $yearLists; //phpcs:ignore?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
    
                            <div class="arm_filter_div" id="arm_search_coupon_filter_item">
                                <div id="arm_search_coupon_filter_item" class="arm_filter_status_box arm_datatable_searchbox arm_datatable_filter_item">
                                    <input type="text" id="arm_search_coupon" class="arm_search_coupon" name="arm_search_coupon" placeholder="<?php esc_html_e('Coupon Code', 'ARMember');?>" value="">
                                </div>
                            </div>
                            <div class="arm_filter_div" id="arm_gateway_filter_item">
                                <div id="arm_gateway_filter_item" class="arm_filter_status_box arm_datatable_filter_item">
                                    <input type="hidden" id="arm_gateway_filter" class="arm_gateway_filter" value="">
                                    <dl class="arm_selectbox arm_width_150">
                                        <dt><span><?php esc_html_e('All Gateways', 'ARMember'); ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul data-id="arm_gateway_filter" data-placeholder="Select Status">
                                                <?php echo $gateways_list; //phpcs:ignore?>
                                                <?php ?>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="arm_filter_div">
                                <input type="button" class="armemailaddbtn arm_margin_right_8" id="arm_report_apply_filter_button" value="<?php esc_html_e('Apply','ARMember'); ?>" />
                                <input type="button" class="arm_cancel_btn arm_margin_right_0" id="arm_report_export_button" value="<?php esc_html_e('Export to CSV','ARMember'); ?>">
                                <input type="hidden" value="monthly" name="armgraphval_coupon_report" id="armgraphval_coupon_report" />
                            </div>
                        </div>
                    </div>
                <?php }?>
        </div>

        <div class="armclear"></div>
        <form  method="post" action="#" id="arm_report_analytics_form">

<?php
if (in_array($get_action, array('member_report', 'payment_report', 'pay_per_post_report','coupon_report'))) {
    echo '<input type="hidden" name="arm_report_type" id="arm_report_type" value="'.esc_attr($get_action).'">';
    if ($get_action == 'member_report') { ?>

        <div class="arm_members_chart">
            <div id="chart_container_members">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_members" class="arm_chart_container_inner" ></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_members" class="arm_chart_container_inner" ></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_members" class="arm_chart_container_inner" ></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>
            <br>
            <div class="arm_members_table_container">
                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Email', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>                                     
                                <td><?php esc_html_e('Next Recurring Date', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Plan Expire Date', 'ARMember'); ?></td>                                          
                                <td><?php esc_html_e('Join Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_members_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_members_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>

<?php }
    else if ($get_action == 'payment_report') { ?>

        <div class="arm_member_payment_history_chart">
            <div id="chart_container_payment_history">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_payment_history" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_payment_history" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_payment_history" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Invoice ID', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member' , 'ARMember'); ?></td>
                                <td><?php esc_html_e('Paid By', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_payments_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_payments_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_24" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>

<?php }
    else if ($get_action == 'pay_per_post_report') { ?>

        <div class="arm_member_pay_per_post_report_chart">
            <div id="chart_container_pay_per_post_report">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_pay_per_post_report" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Invoice ID', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Paid By', 'ARMember'); ?></td>                                
                                <td><?php esc_html_e('Paid Post', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_pay_per_post_report_table_body_content">
                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_payments_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_0" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>

<?php } else if ($get_action == 'coupon_report') { ?>

        <div class="arm_member_coupon_report_chart">
            <div id="chart_container_coupon_report">
                <div id="daily_chart" class="arm_chart_container">
                    <label class="lbltitle">Daily chart</label><br />
                    <div id="chart1_coupon_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="monthly_chart" class="arm_chart_container">
                    <label class="lbltitle">Monthly chart</label><br />
                    <div id="chart2_coupon_report" class="arm_chart_container_inner"></div>
                </div>

                <div id="yearly_chart" class="arm_chart_container">
                    <label class="lbltitle">Yearly chart</label><br />
                    <div id="chart3_coupon_report" class="arm_chart_container_inner"></div>
                </div>
                <span class="lbltitle next_chart" style="display: none;">Previous</span>
                <span class="lbltitle next_chart" style="display: none;">Next</span>
                <br /><br />
            </div>

            <br>
            <div class="arm_members_table_container">

                <div class="arm_all_loginhistory_wrapper">
                    <table class="form-table arm_member_last_subscriptions_table" width="100%">
                        <thead>
                            <tr class="arm_subscription_table_header">
                                <td><?php esc_html_e('Coupon Code', 'ARMember'); ?></td>
                                <td class='arm_align_right'><?php esc_html_e('Coupon Discount', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Member', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Plan', 'ARMember'); ?></td>
                                <td class="arm_align_right"><?php esc_html_e('Paid Amount', 'ARMember'); ?></td>
                                <td class="arm_align_center"><?php esc_html_e('Payment Gateway', 'ARMember'); ?></td>
                                <td><?php esc_html_e('Payment Date', 'ARMember'); ?></td>
                            </tr>
                        </thead>
                        <tbody class="arm_coupon_report_table_body_content">                            
                        </tbody>
                    </table>
                    <div class="arm_membership_history_pagination_block">
                        <div class="arm_membership_history_paging_container" id="arm_coupon_report_table_paging">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="arm_invoice_detail_container">
            <div class="arm_invoice_detail_popup popup_wrapper arm_invoice_detail_popup_wrapper">
                <div class="popup_wrapper_inner" style="overflow: hidden;">
                    <div class="popup_header arm_text_align_center" >
                        <span class="popup_close_btn arm_popup_close_btn arm_invoice_detail_close_btn"></span>
                        <span class="add_rule_content"><?php esc_html_e('Invoice Detail','ARMember' );?></span>
                    </div>
                    <div class="popup_content_text arm_invoice_detail_popup_text arm_padding_0" id="arm_invoice_detail_popup_text" ></div>
                    <div class="popup_footer arm_text_align_center" style=" padding: 0 0 35px;">
                        <?php 
                        $invoice_pdf_icon_html='';
                        $invoice_pdf_icon_html=apply_filters('arm_membership_invoice_details_outside',$invoice_pdf_icon_html,$log_id);
                        echo $invoice_pdf_icon_html; //phpcs:ignore
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php }
}
?>
            <input type='hidden' name='is_export_to_csv' value='0'>
            <input type='hidden' name='current_page' value=''>
            <input type='hidden' name='gateway_filter' value=''>
            <input type='hidden' name='date_filter' value=''>
            <input type='hidden' name='month_filter' value=''>
            <input type='hidden' name='year_filter' value=''>
            <input type='hidden' name='plan_id' value=''>
            <input type='hidden' name='plan_type' value=''>
            <input type='hidden' name='graph_type' value=''>
            <input type='hidden' name='type' value=''>
            <input type='hidden' name='action' value=''>
            <input type='hidden' name='arm_export_report_data' value='0'>
            <input type="hidden" name="arm_search_coupon" value="">

        </form>
    </div>
</div>
<?php $wpnonce = wp_create_nonce( 'arm_wp_nonce' );?>
<input type="hidden" name="arm_wp_nonce" value="<?php echo esc_attr($wpnonce);?>"/>
<script type="text/javascript">
    var ARM_IMAGE_URL = "<?php echo MEMBERSHIPLITE_IMAGES_URL;?>";
</script>
<?php require_once(MEMBERSHIPLITE_VIEWS_DIR.'/arm_view_member_details.php')?>
<?php
    echo $ARMember->arm_get_need_help_html_content('members-report-analysis'); //phpcs:ignore
?>