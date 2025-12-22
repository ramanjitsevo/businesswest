jQuery(document).on('click', '.arm_add_direct_login', function() {
    if (jQuery.isFunction(jQuery().autocomplete))
    {
        if(jQuery("#arm_direct_logins_user_id").length > 0){
            jQuery('#arm_direct_logins_user_id').autocomplete({
                minLength: 0,
                delay: 500,
                appendTo: ".arm_auto_user_field",
                source: function (request, response) {
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=get_direct_logins_member_list&txt="+request.term,
                        beforeSend: function () {},
                        success: function (res) {
                            jQuery('#arm_direct_logins_users_items').html('');
                            response(res.data);
                        }
                    });
                },
                focus: function() {return false;},
                select: function(event, ui) {
                    var itemData = ui.item;
                    jQuery("#arm_direct_logins_user_id").val('');
                    if(jQuery('#arm_direct_logins_users_items .arm_direct_logins_users_itembox_'+itemData.id).length > 0) {
                    } else {
                        var itemHtml = '<div class="arm_direct_logins_users_itembox arm_direct_logins_users_itembox_'+itemData.id+'">';
                        itemHtml += '<input type="hidden" id="arm_direct_logins_user_id_hidden" name="arm_direct_logins_user_id_hidden" value="'+itemData.id+'"/>';
                        itemHtml += '</div>';
                        jQuery("#arm_direct_logins_users_items").html(itemHtml);
                        jQuery("#arm_direct_logins_user_id").val(itemData.label);
                    }
                    jQuery('#arm_direct_logins_users_items').hide();
                    return false;
                },
            }).data('uiAutocomplete')._renderItem = function (ul, item) {
                var itemClass = 'ui-menu-item';
                if(jQuery('#arm_direct_logins_users_items .arm_direct_logins_users_itembox_'+item.id).length > 0) {
                    itemClass += ' ui-menu-item-selected';
                }
                var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                return jQuery(itemHtml).appendTo(ul);
            };
        }
    }

    jQuery('.arm_add_new_direct_logins').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 

});
jQuery(document).on('click', '.add_add_direct_login_close_btn', function () {
    jQuery('.arm_add_new_direct_logins').bPopup().close();
});

function arm_direct_logins_edit(arm_dl_user_id, arm_dl_username){
    jQuery('#arm_direct_logins_edit_user_id').val(arm_dl_user_id);
    jQuery('.arm_dl_set_username').html(arm_dl_username);
    
    jQuery('.arm_edit_direct_logins').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 
}
jQuery(document).on('click', '.arm_edit_direct_login_close_btn', function () {
    jQuery('.arm_edit_direct_logins').bPopup().close();
});
jQuery(document).on('change', "input[name='arm_direct_logins_user_type']", function () {
    if(jQuery(this).val() === 'new_user')
    {
        jQuery('.exists_user_section').hide(0);
        jQuery('.new_user_section').show(0);
    }
    else if(jQuery(this).val() === 'exists_user')
    {
        jQuery('.new_user_section').hide(0);
        jQuery('.exists_user_section').show(0);
    }
});
jQuery(document).on('click', '.arm_add_direct_logins_submit', function () {
    var arm_direct_logins_data = jQuery('#arm_add_direct_logins_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var arm_dl_user_type = jQuery("input[name='arm_direct_logins_user_type']:checked").val();
        var error_count = 0;
        if( arm_dl_user_type === 'exists_user' )
        {   
            if (jQuery('#arm_direct_logins_user_id').val() == '' && jQuery('#arm_direct_logins_user_id_hidden').val()== '') {
                jQuery('#arm_user_ids_select_chosen').css('border-color', '#ff0000');
                jQuery('#arm_user_ids_error').show();
                error_count++;
            }
            else
            {
                jQuery('#arm_user_ids_select_chosen').css('border-color', '');
                jQuery('#arm_user_ids_error').hide();
            }
        }
        else
        {
            var arm_dl_email = jQuery('#arm_direct_logins_email').val();
            if (arm_dl_email == '') {
                jQuery('#arm_direct_logins_email_invalid_error').hide();
                jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                jQuery('#arm_direct_logins_email_error').show();
                error_count++;
            } else {
                jQuery('#arm_direct_logins_email_error').hide();
                if(!arm_dl_email.match(/^\s+|\s+$/g)) {
                    jQuery('#arm_direct_logins_email').css('border-color', '');
                    jQuery('#arm_direct_logins_email_invalid_error').hide();
                    jQuery('#arm_direct_logins_email_error').hide();
                } else {
                    jQuery('#arm_direct_logins_email_invalid_error').show();
                    jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                    error_count++;
                }
            }
        }
        if(error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_direct_logins_save&' + arm_direct_logins_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_edit_direct_logins_wrapper_frm')[0].reset();
                        jQuery('#arm_add_direct_logins_wrapper_frm')[0].reset();
                        jQuery('.arm_add_new_direct_logins').bPopup().close();
                        jQuery('#arm_direct_logins_hours').val('1');
                        jQuery('#arm_direct_logins_days').val('1');
                        jQuery('#arm_direct_logins_role').val('administrator');
                        
                        arm_dl_icheck_init();
                        arm_tipso_init();
                        jQuery('.exists_user_section').slideUp();
                        jQuery('.new_user_section').slideDown();
                        arm_load_direct_logins_grid_after_filtered(msg);
                        
                    } else if(response.type == 'msg') {
                        jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                        jQuery('#arm_direct_logins_email_error').hide();
                        jQuery('#arm_direct_logins_email_invalid_error').hide();
                        jQuery('#arm_direct_logins_email_exists_error').show();
                    } else {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsError;
                        armToast(msg, 'error');
                    }
                    jQuery('#arm_loader_img').hide();
                    $this.removeAttr('disabled');
                    $this.removeClass('arm_already_clicked');
                }
            });
        }
    }
    return false;
});

jQuery(document).on('click', '.arm_edit_direct_logins_submit', function () {
    var arm_direct_logins_data = jQuery('#arm_edit_direct_logins_wrapper_frm').serialize();
    var $this = jQuery(this);
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (!$this.hasClass('arm_already_clicked')) {
        var arm_dl_user_type = jQuery("input[name='arm_direct_logins_user_type']:checked").val();
        
        var error_count = 0;
        if( arm_dl_user_type === 'exists_user' )
        {   
            if (jQuery('#arm_direct_logins_edit_user_id').val() == '') {
                error_count++;
            }
        }
        
        if(error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_direct_logins_save&' + arm_direct_logins_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_edit_direct_logins_wrapper_frm')[0].reset();
                        jQuery('#arm_add_direct_logins_wrapper_frm')[0].reset();
                        jQuery('.arm_edit_direct_logins').bPopup().close();
                        jQuery('#arm_direct_logins_edit_user_id').val('');
                        jQuery('#arm_direct_logins_hours').val('1');
                        jQuery('#arm_direct_logins_days').val('1');
                        
                        arm_dl_icheck_init();
                        arm_tipso_init();
                        arm_load_direct_logins_grid_after_filtered(msg);
                    } else if(response.type == 'msg') {
                        jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                        jQuery('#arm_direct_logins_email_error').hide();
                        jQuery('#arm_direct_logins_email_invalid_error').hide();
                        jQuery('#arm_direct_logins_email_exists_error').show();
                    } else {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsError;
                        armToast(msg, 'error');
                    }
                    jQuery('#arm_loader_img').hide();
                    $this.removeAttr('disabled');
                    $this.removeClass('arm_already_clicked');
                }
            });
        }
    }
    return false;
});

jQuery(document).on('click','.arm_direct_logins_active_switch', function () {
    var user_id = jQuery(this).attr('data-item_id');
    var dl_status = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (jQuery(this).is(':checked')) {
	var dl_status = 1;
    }
    if (user_id == '' && dl_status == '') {
	return false;
    }
    jQuery(this).parents('.armswitch').find('.arm_status_loader_img').show();
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	dataType: 'json',
	data: {action: "arm_direct_logins_update_status", arm_dl_user_id: user_id, arm_dl_status: dl_status, _wpnonce: _wpnonce},
	success: function (res) {
	    if (res.type != 'success') {
		armToast(res.msg, 'error');
	    } else {   
                var dl_user_id = res.user_id;
                if(res.dl_status === '1')
                {
                    jQuery('.link_status_'+dl_user_id).removeClass('color_orenge');
                    jQuery('.link_status_'+dl_user_id).addClass('color_green');
                }
                else
                {
                    jQuery('.link_status_'+dl_user_id).removeClass('color_green');
                    jQuery('.link_status_'+dl_user_id).addClass('color_orenge');
                }
                jQuery('.link_status_'+dl_user_id+' span').html(res.dl_status_inword);
            }
            
	    jQuery('.arm_status_loader_img').hide();
	}
    });
});

jQuery(document).on('click', '.arm_direct_logins_delete_btn', function () {
    var user_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (user_id != '' && user_id != 0) {
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_direct_login_remove&user_id=" + user_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_direct_logins_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});

function showConfirmBoxCallback(item_id) {
    if (item_id != '') {
	var deleteBox = jQuery('#arm_confirm_box_' + item_id);
	deleteBox.addClass('armopen').toggle('slide');
	deleteBox.parents('.armGridActionTD').toggleClass('armopen');
	deleteBox.parents('tr').toggleClass('armopen');
	deleteBox.parents('.dataTables_wrapper').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
	deleteBox.parents('.armPageContainer').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
    }
    return false;
}

function hideConfirmBoxCallback() {
    jQuery('.arm_confirm_box.armopen').removeClass('armopen').toggle('slide', function () {
	jQuery('.armGridActionTD').removeClass('armopen');
	jQuery('tr').removeClass('armopen');
	jQuery('.arm_confirm_back_wrapper').remove();
	jQuery('.arm_field_content_settings_selected').removeClass('arm_field_content_settings_selected');
    });
    return false;
}

function checkNumber(e) {
    if (!((e.keyCode > 95 && e.keyCode < 106)
	|| (e.keyCode > 47 && e.keyCode < 58)
	|| (e.keyCode >= 35 && e.keyCode <= 40)
	|| e.keyCode == 46
	|| e.keyCode == 8
	|| e.keyCode == 9)) {
	return false;
    }
}

function arm_tipso_init(){
    if (jQuery.isFunction(jQuery().tipso)) {
        jQuery('.armhelptip').each(function () {
            jQuery(this).tipso({
                position: 'top',
                size: 'small',
                background: '#939393',
                color: '#ffffff',
                width: false,
                maxWidth: 400,
                useTitle: true
            });
        });
        jQuery('.arm_helptip_icon').each(function () {
            jQuery(this).tipso({
                position: 'top',
                size: 'small',
                background: '#939393',
                color: '#ffffff',
                width: false,
                maxWidth: 400,
                useTitle: true
            });
        });
    }
}

jQuery(document).ready(function(){
    arm_dl_icheck_init();
    arm_tipso_init();
    
    jQuery('.arm_direct_logins_form_role').find('dt').css('width','328px');
    jQuery('.arm_direct_logins_form_hours').find('dt').css('width','145px');
    jQuery('.arm_direct_logins_form_days').find('dt').css('width','145px');
    
    if (jQuery.isFunction(jQuery().chosen)) {
	jQuery(".arm_chosen_selectbox").chosen({
	    no_results_text: "Oops, nothing found."
	});
    }
    
    jQuery("#cb-select-all-1").click(function () {
        jQuery('input[name="item-action[]"]').attr('checked', this.checked);
    });
    jQuery('input[name="item-action[]"]').click(function () {
        if (jQuery('input[name="item-action[]"]').length == jQuery('input[name="item-action[]"]:checked').length) {
            jQuery("#cb-select-all-1").attr("checked", "checked");
        } else {
            jQuery("#cb-select-all-1").removeAttr("checked");
        }
    });
});

function armToast(message, type, time, reload){
    if (reload == '' || typeof reload == 'undefined') {
	reload = false;
    }
    if (time == '' || typeof time == 'undefined') {
	var time = 2500;
    }
    var msgWrapperID = 'arm_error_message';
    if (type == 'success') {
	var msgWrapperID = 'arm_success_message';
    } else if (type == 'error') {
	var msgWrapperID = 'arm_error_message';
    } else if (type == 'info') {
	var msgWrapperID = 'arm_error_message';
    }
    var toastHtml = '<div class="arm_toast arm_message ' + msgWrapperID + '" id="' + msgWrapperID + '"><div class="arm_message_text">' + message + '</div></div>';
    if (jQuery('.arm_toast_container .arm_toast').length > 0) {
	jQuery('.arm_toast_container .arm_toast').remove();
    }
    jQuery(toastHtml).appendTo('.arm_toast_container').show('slow').addClass('arm_toast_open').delay(time).queue(function () {
	if (type != 'error') {
	    var $toast = jQuery(this);
	    jQuery('.arm_already_clicked').removeClass('arm_already_clicked').removeAttr('disabled');
	    $toast.addClass('arm_toast_close');
	    if (reload === true) {
		location.reload();
	    }
	    setTimeout(function () {
		$toast.remove();
	    }, 1000);
	} else {
            var $toast = jQuery(this);
            $toast.addClass('arm_toast_close');
            setTimeout(function () {
		$toast.remove();
	    }, 1000);
        }
    });
}

function arm_dl_icheck_init() {    
    if (jQuery.isFunction(jQuery().iCheck))
    {
    	jQuery('.arm_icheckbox').iCheck({
    	    checkboxClass: 'icheckbox_minimal-red',
    	    radioClass: 'iradio_minimal-red',
    	    increaseArea: '20%',
    	    disabledClass: '',
    	});
    	jQuery('.arm_icheckbox').on('ifChanged', function (event) {
    	    jQuery(this).trigger('change');
    	});
    	jQuery('.arm_icheckbox').on('ifClicked', function (event) {
    	    jQuery(this).trigger('click');
    	});
    	jQuery('.arm_iradio').iCheck({
    	    checkboxClass: 'icheckbox_minimal-red',
    	    radioClass: 'iradio_minimal-red',
    	    increaseArea: '20%',
    	    disabledClass: '',
    	});
    	jQuery('.arm_iradio').on('ifChanged', function (event) {
    	    jQuery(this).trigger('change');
    	});
    	jQuery('.arm_iradio').on('ifClicked', function (event) {
    	    setTimeout(function(){ jQuery(this).trigger('click'); }, 1);
    	});
    }
}

function load_datepicker() {
    if (jQuery.isFunction(jQuery().datetimepicker)) {
        jQuery('.arm_datepicker').each(function () {
            var $this = jQuery(this);
            var dateToday = new Date();
            var locale = '';
            var curr_form = $this.attr('data-date_field');
            var dateformat = $this.attr('data-dateformat');
            var show_timepicker = $this.attr('data-show_timepicker');
            if (dateformat == '' || typeof dateformat == 'undefined') {
                dateformat = 'DD/MM/YYYY';
            }
            if (show_timepicker != '' && typeof show_timepicker != 'undefined' && show_timepicker == 1) {
                dateformat = dateformat + ' hh:mm A';
            }

            $this.datetimepicker({
                useCurrent: false,
                format: dateformat,
                locale: '',
            }).on("dp.change", function (e) {
                jQuery(this).trigger('input');
            });
        });
    }
}

jQuery(document).on('click', '.arm_dl_click_to_copy_text', function () {
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

function armCopyToClipboard(text)
{
    var textArea = document.createElement("textarea");
    textArea.id = 'armCopyTextarea';
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.getElementById("armCopyTextarea").select();
    var successful = false;
    try {
	var successful = document.execCommand('copy');
        armToast('Link Copied', 'success');
    } catch (err) {
    }
    document.body.removeChild(textArea);
    return successful;
}

jQuery(document).on('click', '.arm_dl_openpreview_popup', function () {
    var member_id = jQuery(this).attr('data-id');
    if (member_id != '' && member_id != 0) {
        jQuery('.arm_loading').fadeIn('slow');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_member_view_detail&member_id=" + member_id + "&view_type=popup",
            success: function (response) {
                if (response != '') {
                    jQuery('.arm_member_view_detail_container').html(response);
                    var bPopup = jQuery('.arm_member_view_detail_popup').bPopup({
                        opacity: 0.5,
                        follow: [false, false],
                        closeClass: 'arm_member_view_detail_close_btn',
                        onClose: function () {
                            jQuery('.arm_member_view_detail_popup').remove();
                        }
                    });
                    bPopup.reposition(100);
                    setTimeout(function () {
                        jQuery('.arm_loading').fadeOut();
                    }, 1000);
                } else {
                    jQuery('.arm_loading').fadeOut();
                }
            },
            error: function (response) {
                jQuery('.arm_loading').fadeOut();
            }
        });
    }
    return false;
});


jQuery(document).on('click', '.arm_sidebar_drawer_close_btn', function (e) {
    var current_obj = jQuery(this);
    jQuery(current_obj).parents(".arm_sidebar_drawer_main_wrapper").find(".arm_sidebar_drawer_content").removeClass("arm-slide-in");
    jQuery(current_obj).parents(".arm_sidebar_drawer_main_wrapper").find(".arm_sidebar_drawer_content").addClass("arm-slide-out");
    setTimeout(function(){
        jQuery(current_obj).parents(".arm_sidebar_drawer_main_wrapper").find(".arm_sidebar_drawer_content").removeClass("arm-slide-out");
        jQuery(current_obj).parents(".arm_sidebar_drawer_main_wrapper").removeClass("active");
    }, 500); 
});

jQuery(document).on('click', '.arm_need_help_wrapper', function (e) {
    jQuery(".arm_sidebar_drawer_main_wrapper").addClass("active");
    jQuery(".arm_sidebar_drawer_body").find(".arm_sidebar_content_body").html('');
    jQuery('.arm_sidebar_drawer_content').find(".arm_readmore_link").hide();
    setTimeout(function(){
        jQuery(".arm_sidebar_drawer_main_wrapper").find(".arm_sidebar_drawer_content").addClass("arm-slide-in");
        jQuery('.arm_sidebar_drawer_main_wrapper .arm_loading').fadeIn('slow');
    }, 300); 

    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    var page_name = jQuery(this).find('.arm_need_help_btn').attr("data-param");
    if(typeof page_name == "undefined"){
        page_name = jQuery(this).attr("data-param");
    }
    if(_wpnonce != '' && page_name!='') {
        jQuery.ajax({
            type:"POST",
            dataType: 'json',
            url:__ARMAJAXURL,
            data:"action=arm_get_need_help_content&page="+page_name+"&_wpnonce="+_wpnonce,
            success: function (response) {
                if(response.data != '') {
                    jQuery('.arm_sidebar_drawer_main_wrapper .arm_loading').fadeOut('slow');
                    jQuery('.arm_sidebar_drawer_content').find(".arm_sidebar_content_heading").html(response.data.title);
                    jQuery('.arm_sidebar_drawer_content').find(".arm_readmore_link").attr("href", response.data.url);
                    jQuery('.arm_sidebar_drawer_content').find(".arm_sidebar_content_body").html(decodeURIComponent(response.data.content));
                }
                if(response.data.content == ""){
                    jQuery('.arm_sidebar_drawer_content').find(".arm_readmore_link").hide();
                }
                else {
                    jQuery('.arm_sidebar_drawer_content').find(".arm_readmore_link").show();
                }
                return false;
            }, 
            error: function (argument) {
                jQuery('.arm_sidebar_drawer_content').find(".arm_readmore_link").hide();                
                jQuery('.arm_sidebar_drawer_main_wrapper .arm_loading').fadeOut('slow');
            }
        });
    }

});
jQuery(document).on('click', '.arm_need_help_main_wrapper_inactive', function (e) {
    jQuery('.arm_need_help_main_wrapper_active').addClass('arm_need_help_main_wrapper_active_state');
});
jQuery(document).on('click', '.arm_need_help_main_wrapper_active.arm_need_help_main_wrapper_active_state', function (e) {
    jQuery('.arm_need_help_main_wrapper_active').removeClass('arm_need_help_main_wrapper_active_state');
});