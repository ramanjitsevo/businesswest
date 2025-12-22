jQuery(document).ready(function(){
    jQuery('form[name="arm_form_gm_invite"]').find("input,select,textarea").not("[type=submit]").ARMjqBootstrapValidation({
        preventSubmit: true,
        prependExistingHelpBlock: false,
        autoAdd: {
            helpBlocks: true
        },
        submitError: function($form, event, errors) {
            var arm_frm_id=jQuery($form).attr('id');
            if(Object.keys(errors).length>0){
                var errors_elment_focust_index='1';
                jQuery.each(errors, function( index, value ) {
                  
                  jQuery("#"+arm_frm_id+" [name='"+index+"']").addClass('arm_invalid');
                  if(errors_elment_focust_index=='1'){
                    jQuery("#"+arm_frm_id+" [name='"+index+"']").focus();
                  }
                  errors_elment_focust_index=parseInt(errors_elment_focust_index)+1;
                });
            }            
            event.preventDefault();
            return false;
        },
        submitSuccess: function($form, event) {
            
            var arm_frm_id=jQuery($form).attr('id');
            arminviteSubmitBtn(arm_frm_id);
            setTimeout(function(){ 
                jQuery("#"+arm_frm_id+" [name]").removeClass('arm_invalid');
            }, 10000);
            event.preventDefault();
            return false;

        },
        filter: function() {
            return jQuery(this).is(":visible");
        }        
    });

	//To check that default plan enable group membership and hide if not enable
    var select_plan_skin = jQuery('input[name="arm_front_plan_skin_type"]').val();
    if(select_plan_skin == 'skin5')
    {
        var arm_gm_default_selected_plan = jQuery("input.arm_module_plan_input").val();
        var arm_gm_subscription_plan_val = jQuery('[name=subscription_plan]').val();
    }
    else
    {
        var arm_gm_default_selected_plan = jQuery("input.arm_module_plan_input:checked").val();
	var arm_gm_subscription_plan_val = jQuery('[name=subscription_plan]:checked').val();
    }
	
    if(arm_gm_subscription_plan_val == "" || arm_gm_subscription_plan_val == undefined)
    {
        if(select_plan_skin == 'skin5')
        {
            var arm_gm_default_selected_dropdown_plan = jQuery("[name='subscription_plan']");
            arm_gm_default_selected_dropdown_plan = arm_gm_default_selected_dropdown_plan[Object.keys(arm_gm_default_selected_dropdown_plan)[0]];
            if( (typeof document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0]) != "undefined")
            {
               arm_gm_subscription_plan_val = document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0].getAttribute('value');
            }
    	
        }
        else
        {
            var arm_gm_default_selected_dropdown_plan = jQuery("[name='subscription_plan']:selected");
            arm_gm_default_selected_dropdown_plan = arm_gm_default_selected_dropdown_plan[Object.keys(arm_gm_default_selected_dropdown_plan)[0]];
            if( (typeof document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0]) != "undefined")
            {
            arm_gm_subscription_plan_val = document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0].getAttribute('value');
            }
        }
    }


    if(jQuery("span").hasClass("arm_selected_child_users"))
    {
        jQuery(".arm_selected_child_users").html('<b>'+jQuery('.arm_gm_hidden_min_members_'+arm_gm_subscription_plan_val).val()+'</b>');
    }

    if((jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).length == 0 && arm_gm_default_selected_plan != undefined) || (jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).length == 0) || arm_gm_subscription_plan_val == 0)
	{
		jQuery(".arm_gm_setup_sub_user_selection_main_wrapper").css('display', 'none');
	}
	else
	{
		jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).trigger('change');
	}

    if(arm_gm_default_selected_plan != undefined){
        var arm_gm_is_display = jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).data('arm_gm_is_display');
        if(arm_gm_is_display){
            jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).css('display', 'block');
        }else{
            jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).css('display', '');    
        }
    }else if(arm_gm_subscription_plan_val != undefined){
        var arm_gm_is_display = jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).data('arm_gm_is_display');
        if(arm_gm_is_display){
            jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).css('display', 'block');
        }else{
            jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).css('display', 'none');
        }
    }else{
        jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).css('display', 'none');
    }
});


function arm_gm_tax_calculation(arm_gm_plan_amount,arm_gm_selected_plan_id, arm_gm_selected_country = "")
{
    var arm_form_id = jQuery('input[data-id="subscription_plan_'+arm_gm_selected_plan_id+'"]').parents('form').attr('id');
    var currency_decimal = jQuery("#"+arm_form_id).find('.arm_global_currency_decimal').val();
    var arm_proratated_amount = jQuery('input[data-id="subscription_plan_'+arm_gm_selected_plan_id+'"]').attr('data-pro_rata');
    var is_pro_rata = jQuery('input[data-id="subscription_plan_'+arm_gm_selected_plan_id+'"]').attr('data-pro_rata_enabled');
    var user_selected_seats = jQuery('input[name="arm_gm_purchased_member_seats_'+arm_gm_selected_plan_id+'"]').val();
    var nonce = jQuery('input[name="arm_wp_nonce"]').val();
	jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_recalculate_plan_amount&arm_gm_plan_id="+arm_gm_selected_plan_id+"&arm_gm_plan_amount="+arm_gm_plan_amount+"&arm_gm_selected_country="+arm_gm_selected_country+"&is_pro_rata="+is_pro_rata+"&arm_prorated_amount= "+arm_proratated_amount+"&arm_selected_seats="+user_selected_seats+"+&arm_wp_nonce="+nonce,
        success: function (response) {
            var arm_gm_response_data = JSON.parse(response);
            var arm_gm_taxable_amount = arm_gm_response_data.arm_gm_taxable_amount;
            var arm_gm_taxable_amount_int = arm_gm_response_data.arm_gm_taxable_amount;
            arm_gm_taxable_amount = parseFloat(arm_gm_taxable_amount);
            arm_gm_taxable_amount = arm_gm_taxable_amount.toFixed(currency_decimal);
            jQuery("input[name='arm_total_payable_amount']").val(arm_gm_taxable_amount_int);
            if(jQuery(".arm_tax_amount_text").length > 0){
                jQuery(".arm_tax_amount_text").html(arm_gm_response_data.arm_gm_total_tax);
            }
            jQuery(".arm_payable_amount_text").html(arm_gm_taxable_amount);
        }
    });    
}


jQuery(document).on('change', "input.arm_module_plan_input", function () {
    var plan_skin = jQuery("input[name=arm_front_plan_skin_type]").val();   
    var arm_setup_form_id = jQuery( this ).parents( '.arm_membership_setup_form' ).attr('id');
    var arm_membership_setup_form = jQuery( '#'+ arm_setup_form_id);
    if(plan_skin == 'skin5')
    {
        var arm_gm_selected_plan_id = arm_membership_setup_form.find('input.arm_module_plan_input').val();
    }
    else
    {
        var arm_gm_selected_plan_id = arm_membership_setup_form.find('input.arm_module_plan_input:checked').val();
    }
	
    if (arm_membership_setup_form.find('input:radio[name="arm_selected_payment_mode"]').length) {
        arm_membership_setup_form.find('input:radio[name="arm_selected_payment_mode"]').filter('[value="auto_debit_subscription"]').attr('checked', true).trigger('change');
    }
    armSetupHideShowSections(arm_membership_setup_form);
	arm_membership_setup_form.find(".arm_gm_setup_sub_user_selection_main_wrapper").css('display', 'none');
    if(arm_membership_setup_form.find(".arm_gm_sub_user_"+arm_gm_selected_plan_id).length > 0)
	{
        var arm_gm_is_display = arm_membership_setup_form.find(".arm_gm_sub_user_"+arm_gm_selected_plan_id).data('arm_gm_is_display');
        if(arm_gm_is_display){
            arm_membership_setup_form.find(".arm_gm_sub_user_"+arm_gm_selected_plan_id).css('display', 'block');
        }else{
            arm_membership_setup_form.find(".arm_gm_sub_user_"+arm_gm_selected_plan_id).css('display', 'none');
        }
	}
});

jQuery(document).on('click', '.arm_group_membership_child_user_paging_container .arm_page_numbers', function(){
    var arm_gm_page_no = jQuery(this).data('page');
    var arm_gm_per_page = jQuery(this).data('per_page');
    armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
});


jQuery(document).on('click', '.arm_invite_user_button', function(e){
    jQuery(".arm_error_msg_box").removeClass('ng-inactive');
    jQuery(".arm_error_msg_box").addClass('ng-hide');
	jQuery('#arm_gm_form_invite_user_shortcode_modal').bPopup({
	    opacity: 0.5,
	    closeClass: 'popup_close_btn',
	    follow: [false, false],
	});
    jQuery('#arm_gm_form_invite_user_shortcode_modal').find('.arm-df__form-control_email_label').trigger('focus');
});

function arm_gm_change_form_child_counter(setup_id,plan_id)
{
    var member_purchased_seats = jQuery(document).find(' input[name="arm_gm_purchased_member_seats_'+plan_id+'"]').val();
    
    console.log(member_purchased_seats);

    jQuery(document).find('.arm_gm_hidden_min_members_'+plan_id).val(member_purchased_seats);

    jQuery(document).find('#gm_sub_user_select_'+plan_id).val(member_purchased_seats).trigger("change");   
}


jQuery(document).on('click', '.arm_user_group_membership_list_table .arm_click_to_copy_text', function () {
    var code = jQuery(this).attr('data-code');
    var isSuccess = armCopyToClipboard(code);
    if (!isSuccess) {
        var $this = jQuery(this).parent('.arm_form_shortcode_box').find('.armCopyText');
        var $thisHover = jQuery(this).parent('.arm_form_shortcode_box').find('.arm_click_to_copy_text');
        var $input = jQuery('<input type=text>');
        $input.prop('value', code);
        $input.prop('readonly', true);
        $input.insertAfter($this);
        $input.focus();
        $input.select();
        $this.hide();
        $thisHover.hide();
        $input.focusout(function () {
            $this.show();
            $thisHover.removeAttr('style');
            $input.remove();
        });
    } else {
        jQuery(this).parent('.arm_form_shortcode_box').find('.arm_copied_text').show().delay(3000).fadeOut();
    }
});
/*
	Inherit parent controller of ARMember Form and create new controller and assign new function to new controller.
*/
function armRefreshChildUsersList(page_no, per_page)
{
    var arm_gm_page_no = page_no;
    var arm_gm_per_page = per_page;
    var arm_gm_invite_form_data = jQuery(".arm_invite_form_id").serialize();
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_invite_code_pagination&pagination_type=child_users&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page+'&'+arm_gm_invite_form_data,
        beforeSend: function () {
            jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
        },
        success: function (response) {
            var arm_gm_response_content = JSON.parse(response);
            jQuery(".arm_gm_child_user_list_tbody").empty();
            jQuery(".arm_group_membership_child_user_paging_container").empty();
            jQuery(".arm_gm_child_user_list_tbody").append(arm_gm_response_content.arm_gm_tbody_content);
            jQuery(".arm_group_membership_child_user_paging_container").append(arm_gm_response_content.arm_gm_paging_content);
            jQuery(".gm_parent_wrapper_container").css('opacity', '1');
            if(arm_gm_response_content.arm_gm_is_hide){
                jQuery(".arm_invite_user_button").css('display', 'none');
            }else{
                jQuery(".arm_invite_user_button").css('display', 'block');
            }
        }
    });
}

function arminviteSubmitBtn(arm_form_id)
{
    var email_value =jQuery('#'+arm_form_id).find("input[name='invite[email_label]']").val();
    if('' != email_value)
    {

        if(email_value.includes(','))
        {
            var emails = email_value.split(',');
        }
        else
        {
            var emails = email_value;
            
        }
        var arm_gm_page_no = jQuery('#'+arm_form_id).find('.arm-df__form-control-submit-btn').attr('data-current_page');
        var arm_gm_per_page = jQuery('#'+arm_form_id).find('.arm-df__form-control-submit-btn').attr('data-per_page');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: 'action=arm_gm_invite_users&invited_emails='+emails+'&arm_gm_page_no='+arm_gm_page_no+'&arm_gm_per_page='+arm_gm_per_page,
            beforeSend: function () {
                jQuery("#arm_invite_invite_submit").addClass('active');
                jQuery("#arm_invite_invite_submit").attr('disabled', 'disabled');
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function(response)
            {
                jQuery(".gm_parent_wrapper_container").css('opacity', '1');
                jQuery("#arm_invite_invite_submit").removeAttr('disabled', 'disabled');
                jQuery("#arm_invite_invite_submit").removeClass('active');
                jQuery("[name='invite[email_label]']").val('');
                var response_data = JSON.parse(response);
                if(response_data.status == "success")
                {
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper .arm-df__fc--validation__wrap").html('<ul><li>'+response_data.message+'</li></ul>');
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper .arm-df__fc--validation__wrap").addClass('arm_success_msg');
                }
                else
                {
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper .arm-df__fc--validation__wrap").html('<ul><li>'+response_data.message+'</li></ul>');
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper .arm-df__fc--validation__wrap").addClass('arm_error_msg');
                }
    
                jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper").css('display', 'block');
    
                setTimeout(function(){
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_editor_form_fields_wrapper").css('display', 'none');
                    if(response_data.status == "success"){ 
                        armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                        jQuery(".arm_popup_close_btn").trigger('click');
                    }
                }, 3000);
            }
        });
    }
}


function gm_validateEmail(email) {
    var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    return re.test(email);
}



var is_error = 0;

function arm_gm_validate_field_len(obj) {
    if('' == obj.value) {
        jQuery(".arm_error_msg_box").addClass('ng-inactive');
        jQuery(".arm_error_msg_box").removeClass('ng-hide');
        var err_msg = jQuery(obj).attr("data-msg-required");
        var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
        jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
        jQuery(".arm_error_msg_box").fadeIn("slow");
        is_error = 1;
        return false;
    } else {
        var emails = obj.value.split(',');
        var invalidEmails = [];
        for (var i = 0; i < emails.length; i++) { 
            if(!gm_validateEmail(emails[i].trim())) {
              invalidEmails.push(emails[i].trim())
            }
        }
        if(invalidEmails.length > 0) { 
            var err_msg = jQuery(obj).attr("data-arm_min_len_msg");
            var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
            jQuery(".arm_error_msg_box").fadeIn("slow");
            is_error = 1;
            return false;
        } else {
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html("");
            jQuery(".arm_error_msg_box").fadeOut("slow");
            is_error = 0;
            return true;
        }    
    }   
}



jQuery(document).on('click', '.arm_delete_user_button', function(e){
    var delete_cnt = jQuery(this).data('cnt');
    var arm_gm_confirmation = confirm(__ChildDeleteText);
    if(arm_gm_confirmation)
    {
        var delete_email = jQuery(this).data('delete_email');
        var arm_gm_page_no = jQuery(this).data('page_no');
        var arm_gm_per_page = jQuery(this).data('per_page');
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_delete_member&delete_email="+delete_email+"&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function (response) {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_delete_user_msg").css('display', 'block'); }, 500);
                setTimeout(function(){ jQuery(".arm_gm_delete_user_msg").css('display', 'none'); }, 5000);
            }
        });
    }
});

jQuery(document).on('click', '.armcancel', function(e){
    var delete_cnt = jQuery(this).data('cnt');
    jQuery(".arm_confirm_box_"+delete_cnt).fadeOut("slow");  
});



jQuery(document).on('click', '.arm_gm_refresh_coupon_code', function(){
    var arm_gm_confirmation = confirm(__CodeRefreshText);
    if(arm_gm_confirmation)
    {
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        var arm_gm_page_no = jQuery(this).data('page');
        var arm_gm_per_page = jQuery(this).data('per_page');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_update_coupon_code&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function(response)
            {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_refresh_invite_code_msg").css('display', 'block'); }, 500);
                setTimeout(function(){
                    jQuery(".arm_gm_refresh_invite_code_msg").css('display', 'none');
                }, 5000);
            }
        });
    }
});


jQuery(document).on('click', '.arm_gm_resend_email_button', function(e){
    var arm_gm_confirmation = confirm(__CodeResendText);
    if(arm_gm_confirmation)
    {
        var arm_gm_resend_email = jQuery(this).data('resend_email');
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        var arm_gm_page_no = jQuery(this).data('page');
        var arm_gm_per_page = jQuery(this).data('per_page');

        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_resend_email&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_resend_email="+arm_gm_resend_email+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function(response)
            {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_resend_email_msg").css('display', 'block'); }, 500);
                setTimeout(function(){
                    jQuery(".arm_gm_resend_email_msg").css('display', 'none');
                }, 5000);
            }
        });
    }
});

function arm_gm_change_plan_price(element, arm_form_random_id, arm_gm_plan_price = '0', arm_is_trial_enabled = '0')
{
    var arm_form_id = "arm_setup_form"+arm_form_random_id;
    var arm_gm_form = jQuery("#"+arm_form_id);

    var arm_gm_plan_id = 0;
	if(arm_gm_plan_price=='') { arm_gm_plan_price = 0; }
    var arm_gm_plan_amount = arm_gm_plan_price;
    if(arm_gm_form.find('[name=subscription_plan]:checked').length > 0)
    {
        arm_gm_plan_id = arm_gm_form.find('[name=subscription_plan]:checked').val();
        arm_gm_plan_amount = arm_gm_form.find('[name=subscription_plan]:checked').attr('data-amt');
    }
    else
    {
        //condition for dropdown plan skin.
        //arm_gm_plan_id = arm_gm_form[0].subscription_plan.selectedOptions[0].value;
        arm_gm_plan_id = arm_gm_form[0].subscription_plan.value;
    }

    var arm_selected_old_user_seats = arm_gm_form.find('input[name=gm_sub_user_select_'+arm_gm_plan_id+']').attr('data-old_value');
    if(arm_selected_old_user_seats == element.value)
    {
        return;
    }
    var plan_skin = jQuery('#'+arm_form_id+' input[name=arm_front_plan_skin_type]').val();    
    var currency_decimal = jQuery("#"+arm_form_id).find('.arm_global_currency_decimal').val();
    if(currency_decimal == '' ){ currency_decimal = 0; }
    var arm_gm_parent_element = document.getElementById(arm_gm_form.attr('id'));
    var arm_gm_form_id = '#'+arm_gm_form.attr('id')+' ';
    armResetCouponCode(arm_gm_form);
    
    
    if(arm_gm_plan_id == "" || arm_gm_plan_id == undefined)
    {
        if(plan_skin == 'skin5')
        {
            var arm_gm_default_selected_dropdown_plan = arm_gm_form.find("[name=subscription_plan]");
            arm_gm_plan_id = arm_gm_form.find('input[name=subscription_plan]').val();
            var arm_gm_plan_amount = arm_gm_form.find('li.arm__dc--item[data-value="'+arm_gm_plan_id+'"]').getAttribute('data-amt');
        }
        else
        {

            var arm_gm_default_selected_dropdown_plan = arm_gm_form.find("[name=subscription_plan]:checked");
            arm_gm_default_selected_dropdown_plan = arm_gm_default_selected_dropdown_plan[Object.keys(arm_gm_default_selected_dropdown_plan)[0]];
            arm_gm_plan_id = document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0].getAttribute('value');
            var arm_gm_plan_amount = arm_gm_form.find('[name=subscription_plan]:checked').getAttribute('data-amt');
        }
    }
    if(arm_gm_plan_amount!='')
    {
        arm_gm_plan_amount = arm_gm_plan_amount.toString().replace(',', '').replace(' ', '');
        
        
        if (arm_gm_form.find('input[name=gm_sub_user_select_'+arm_gm_plan_id+']').length > 0) {
            
            var li_arm_gm_min_members=arm_gm_form.find('ul[data-id=gm_sub_user_select_'+arm_gm_plan_id+'] li:first');
            
            var arm_gm_min_members = arm_gm_form.find("#gm_sub_user_select_"+arm_gm_plan_id).attr('data-default-val');
            var arm_gm_selected_user_val = parseFloat(element.value);

            arm_gm_form.find('input[name=gm_sub_user_select_'+arm_gm_plan_id+']').attr('data-old_value', arm_gm_selected_user_val);
            
            arm_gm_plan_amount = arm_gm_plan_amount / arm_gm_min_members;
            if(arm_gm_form.find(".arm_gm_sub_user_"+arm_gm_plan_id).css('display') != "none"){
                arm_gm_plan_amount = (arm_gm_plan_amount * arm_gm_selected_user_val);
                arm_gm_plan_amount = parseFloat(arm_gm_plan_amount);
                arm_gm_plan_amount = arm_gm_plan_amount.toFixed(currency_decimal);
            }
            
            if(!Number.isNaN(arm_gm_plan_amount) && arm_is_trial_enabled == 0 )
            {
                armResetCouponCode(arm_gm_form);
                arm_gm_tax_calculation(arm_gm_plan_amount,arm_gm_plan_id, '');
                if((arm_gm_parent_element.querySelectorAll(".arm_selected_child_users").length > 0)){
                    document.getElementsByClassName("arm_selected_child_users")[0].innerHTML = '<b>'+arm_gm_selected_user_val+'</b>';
                }
            }
        }
    }
}

function armResetCouponCode(form)
{
    jQuery(form).find('.arm_apply_coupon_container').find('.notify_msg').remove();
    if (typeof jQuery(form).find('input[name="arm_coupon_code"]').val() != 'undefined') {
        jQuery(form).find('input[name="arm_coupon_code"]').val('');
    }
}