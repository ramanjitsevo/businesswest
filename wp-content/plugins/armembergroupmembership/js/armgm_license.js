jQuery(document).ready(function(){
    jQuery(document).on('click', '#armgm-verify-purchase-code', function () {
        var ajaxurl = jQuery("#ajaxurl").val();
        var arm_frm_id=jQuery(this).parents('form').attr('id');
        var arm_license_key = jQuery('#'+arm_frm_id+' #arm_license_key').val();
        var arm_license_status=jQuery(this).parents('form').find('#arm_license_deactive').val();
        
        jQuery('#'+arm_frm_id+' #license_error').html('').hide();
        if (arm_license_key == '') {
            jQuery('#'+arm_frm_id+' .arm_addon_license_field_wrapper').addClass('arm_error');
            jQuery('#'+arm_frm_id+' #arm_license_key_error').css('display', 'block');
            jQuery('#'+arm_frm_id+' #arm_license_key').focus();
            setTimeout(function () {
                jQuery('#'+arm_frm_id+' .arm_addon_license_field_wrapper').removeClass('arm_error');
            },5000);
        } else {
            jQuery('#'+arm_frm_id+' .arm_addon_license_field_wrapper').removeClass('arm_error');
            jQuery('#'+arm_frm_id+' #arm_license_key_error').css('display', 'none');
        }
        if (arm_license_key == '')
            return false;

        var arm_frm_license_data=jQuery('#'+arm_frm_id).serialize();
            arm_frm_license_data +='&_wpnonce='+jQuery('input[name="arm_wp_nonce"]').val();
        jQuery('#'+arm_frm_id+' #license_loader').css('display', 'flex');        
        jQuery.ajax({
            type: "POST",
            dataType: 'json',
            url: __ARMAJAXURL,
            data: "action=armgmactivatelicense&" + arm_frm_license_data,
            success: function (response) {
                jQuery('#'+arm_frm_id+' #license_loader').css('display', 'none');
                var msg = response.msg;
                if (response.type == "success") {
                    jQuery('#'+arm_frm_id+' #arm_license_deactive').val(response.arm_license_status);
                    if(response.arm_license_status=='1'){
                        jQuery('#'+arm_frm_id+' .arm_license_purchase_code').html('<strong>'+response.license_purchase_code_lbl+'</strong>'+arm_license_key.trim());
                        jQuery('#'+arm_frm_id+' .armgm_add_license_section').css('display', 'none');
                        jQuery('#'+arm_frm_id+' .armgm_remove_license_section').css('display', 'flex');
                    }else{                        
                        jQuery('#'+arm_frm_id+' #arm_license_key').val('');
                        jQuery('#'+arm_frm_id+' .armgm_remove_license_section').css('display', 'none');
                        jQuery('#'+arm_frm_id+' .armgm_add_license_section').css('display', 'flex');
                    }
                    armToast(msg, 'success' );
                    jQuery('#'+arm_frm_id+' #license_error').css('display', 'none');
                    jQuery('#'+arm_frm_id+' #license_reset').css('display', 'none');                    
                } else {
                    jQuery('#'+arm_frm_id+' #license_error').html(msg).css('display', 'block');
                    jQuery('#'+arm_frm_id+' #license_success').css('display', 'none');
                }
                setTimeout(function () {
                    jQuery('#'+arm_frm_id+' #license_error').html('');
                    jQuery('#'+arm_frm_id+' #license_success').html('');
                }, 5000);
            },
            error: function () {
                alert("error in activation of license");
            }
            
        });
        return false;
    });
    jQuery(document).on('click', '#armgm-remove-verify-purchase-code', function () {
        var arm_frm_id=jQuery(this).parents('form').attr('id');
        jQuery('#armgm_remove_license_flag').val(arm_frm_id);
        jQuery('#armgm_remove_license_form_message').bPopup({
            closeClass: 'popup_close_btn',
            onClose: function () {
                jQuery('#armgm_remove_license_flag').val('');
            }
        });
    });
});
function armgm_deactivate_license(){
    var arm_frm_id=jQuery('#armgm_remove_license_flag').val();
    if(arm_frm_id!=''){
        jQuery('#'+arm_frm_id+' #armgm-verify-purchase-code').trigger('click');
        jQuery('#armgm_remove_license_form_message').bPopup().close();
    }
}
