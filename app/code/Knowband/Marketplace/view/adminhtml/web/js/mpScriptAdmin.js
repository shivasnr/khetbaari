///* 
// * To change this license header, choose License Headers in Project Properties.
// * To change this template file, choose Tools | Templates
// * and open the template in the editor.
// */

function validateVssMpGeneralSettingsForm() {
    var is_error = false;
    jQuery("body").loader("show");
    jQuery('.kb_error_message').remove();
    jQuery('.spin_error').remove();
    jQuery('input').removeClass('kb_error_field');
    /*Knowband validation start*/
    var commission_mand_error = velovalidation.checkMandatory(jQuery('input[name="vss_mp[commission]"]'));

    if (commission_mand_error != true) {
        is_error = true;
        jQuery('input[name="vss_mp[commission]"]').addClass('kb_error_field');
        jQuery('input[name="vss_mp[commission]"]').after('<span class="kb_error_message">' + commission_mand_error + '</span>');
    } else{
        var commission_perc_error = velovalidation.checkPercentage(jQuery('input[name="vss_mp[commission]"]'));
        if (commission_perc_error != true) {
            is_error = true;
            jQuery('input[name="vss_mp[commission]"]').addClass('kb_error_field');
            jQuery('input[name="vss_mp[commission]"]').after('<span class="kb_error_message">' + commission_perc_error + '</span>');
        } else {
            var commission_perc_amt_error = velovalidation.checkAmount(jQuery('input[name="vss_mp[commission]"]'));
            if (commission_perc_amt_error != true) {
            is_error = true;
            jQuery('input[name="vss_mp[commission]"]').addClass('kb_error_field');
            jQuery('input[name="vss_mp[commission]"]').after('<span class="kb_error_message">' + commission_perc_amt_error + '</span>');
        } 
        }
    }

    if (is_error) {
        jQuery("body").loader("hide");
        return false;
    }

    /*Knowband button validation start*/
    jQuery('#save-marketplace-general').attr('disabled', 'disabled');
    /*Knowband button validation end*/

    return true;
}

function validateVssMpEmailTemplateForm() {
    var is_error = false;
    jQuery("body").loader("show");
    jQuery('.kb_error_message').remove();
    jQuery('.spin_error').remove();
    jQuery('input').removeClass('kb_error_field');
    /*Knowband validation start*/
    
    var template_subject_mand_error = velovalidation.checkMandatory(jQuery('input[name="mpEmailTemplate[template_subject]"]'));
    if (template_subject_mand_error != true)
    {
        is_error = true;
        jQuery('input[name="mpEmailTemplate[template_subject]"]').addClass('kb_error_field');
        jQuery('input[name="mpEmailTemplate[template_subject]"]').after('<span class="kb_error_message">' + template_subject_mand_error + '</span>');
    }
    
    jQuery('#template_text').parent().removeClass('kb_error_field');
    var validate_rec_template_mandatory = checkTinyMCERequired(tinyMCE.get('template_text').getContent().trim());
    if (validate_rec_template_mandatory != true) {
        is_error = true;
        jQuery('#template_text').parent().addClass('kb_error_field');
        jQuery('#template_text').parent().parent().after('<span class="kb_error_message" style="margin-left: 270px;">' + validate_rec_template_mandatory + '</span>');
    }
    
    if (is_error) {
        jQuery("body").loader("hide");
        return false;
    }

    /*Knowband button validation start*/
    jQuery('#save-marketplace-template').attr('disabled', 'disabled');
    /*Knowband button validation end*/

    return true;
}

function validateVssMpPaypalSettingsForm() {
    var is_error = false;
    jQuery("body").loader("show");
    jQuery('.kb_error_message').remove();
    jQuery('.spin_error').remove();
    jQuery('input').removeClass('kb_error_field');
    /*Knowband validation start*/
    if (jQuery('input[name="mp_paypal[paypal_client_id]"]').val().trim() == '') {
        is_error = true;
        jQuery('input[name="mp_paypal[paypal_client_id]"]').addClass('kb_error_field');
        jQuery('input[name="mp_paypal[paypal_client_id]"]').after('<span class="kb_error_message">' + field_empty + '</span>');
    }

    if (jQuery('input[name="mp_paypal[paypal_client_secret]"]').val().trim() == '') {
        is_error = true;
        jQuery('input[name="mp_paypal[paypal_client_secret]"]').addClass('error_field');
        jQuery('input[name="mp_paypal[paypal_client_secret]"]').after('<span class="kb_error_message">' + field_empty + '</span>');
    }

    if (jQuery('input[name="mp_paypal[paypal_email_subject]"]').val().trim() == '') {
        is_error = true;
        jQuery('input[name="mp_paypal[paypal_email_subject]"]').addClass('kb_error_field');
        jQuery('input[name="mp_paypal[paypal_email_subject]"]').after('<span class="kb_error_message">' + field_empty + '</span>');
    } else {
        if (jQuery('input[name="mp_paypal[paypal_email_subject]"]').val().trim().match(/([\<])([^\>]{1,})*([\>])/i)) {
            is_error = true;
            jQuery('input[name="mp_paypal[paypal_email_subject]"]').addClass('kb_error_field');
            jQuery('input[name="mp_paypal[paypal_email_subject]"]').after('<span class="kb_error_message">' + kb_html_tags + '</span>');
        }
    }

    if (is_error) {
        jQuery("body").loader("hide");
        return false;
    }

    /*Knowband button validation start*/
    jQuery('#save-marketplace-paypal').attr('disabled', 'disabled');
    /*Knowband button validation end*/

    return true;
}

function takeReasonAction(id)
{
    jQuery('#vssmp-reason-form input[name="custom_id"]').val(id);
    jQuery('#vssmp-reason-popup-blk').show();
}

/*
 * Function for the validation of TinyMCE Editor's content
 */
function checkTinyMCERequired(val) {
    var new_str = str_replace_all(val, '<p>', '');
    new_str = str_replace_all(new_str, '</p>', '');
    new_str = new_str.trim();
    var return_val = true;
    if (new_str == '') {
        return_val = empty_field;
    }
    return return_val;
}

/*
 * Function for replacing all occurences of a sub-string inside a string
 */
function str_replace_all(string, str_find, str_replace) {
    try {
        return string.replace(new RegExp(str_find, "gi"), str_replace);
    } catch (ex) {
        return string;
    }
}

function changeFeatureStatus(e, url)
{
    var prev = jQuery(e).attr('prev-data');
    if (confirm('Are you sure to update feature status of this seller?')) {
        var selected_value = jQuery(e).val();
        jQuery.ajax({
                url: url,
                type: 'post',
                showLoader: true,
                data: 'ajax=true&status='+selected_value,
                dataType: 'json',
                success: function (response) {
                    alert(response.message);
                    if (response.error == false) {
                        jQuery('div#sellerFeatureListGrid').parent().html(response.grid);
                    }
                }
        });
    } else {
        jQuery(e).find('option').removeAttr('selected');
        jQuery(e).find('option[value="'+prev+'"]').attr('selected', 'selected');
        return false;
    }
}

function validateAndSubmitPayoutRequestForm() {
        var error = false;
        jQuery('.kb_error_message').remove();
        jQuery('input[name="kb_payout_transaction_id"]').removeClass('kb_error_field');
        jQuery('textarea[name="kb_payout_transaction_comment"]').removeClass('kb_error_field');

        var transaction_comment = jQuery('textarea[name="kb_payout_transaction_comment"]').val().trim();

        if (jQuery('input[name="kb_payout_transaction_id"]').length) {
            var transaction_id = jQuery('input[name="kb_payout_transaction_id"]').val().trim();
            if (transaction_id == '') {
                error = true;
                jQuery('input[name="kb_payout_transaction_id"]').addClass('kb_error_field');
                jQuery('input[name="kb_payout_transaction_id"]').after('<span class="kb_error_message">' + empty_field_error + '</span>');
            }
        }
        if (jQuery('textarea[name="kb_payout_transaction_comment"]').hasClass('kb-payout-comment-required')) {
            if (transaction_comment == '') {
                error = true;
                jQuery('textarea[name="kb_payout_transaction_comment"]').addClass('kb_error_field');
                jQuery('textarea[name="kb_payout_transaction_comment"]').after('<p class="kb_error_message">' + empty_field_error + '</p>');
            } else if (transaction_comment.length < vssmp_rsn_min_chars) {
                error = true;
                jQuery('textarea[name="kb_payout_transaction_comment"]').addClass('kb_error_field');
                jQuery('textarea[name="kb_payout_transaction_comment"]').after('<p class="kb_error_message">' + minimum_reason_length_error + '</p>');
            }
        }
        if (transaction_comment != '') {
            if (transaction_comment.match(/([\<])([^\>]{1,})*([\>])/i)) {
                error = true;
                jQuery('textarea[name="kb_payout_transaction_comment"]').addClass('kb_error_field');
                jQuery('textarea[name="kb_payout_transaction_comment"]').after('<p class="kb_error_message">' + kb_html_tags + '</p>');
            }
        }

        if (error) {
            return false;
        } else {
            jQuery("#submit_vssmp_payout_request").attr("disabled", "disabled");
            jQuery('form#vssmp-payout-status-update-form').submit();
        }
}

function redirectLocation(url){
    location.href = url;
}

function approveAction(url)
{
    var actionToPerform = confirm("Please confirm approval?");
    if (actionToPerform == true) {
        location.href = url;
    } else {
        return false;
    }
};
