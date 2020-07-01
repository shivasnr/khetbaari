var import_file_is_valid_file = false;
        
function handleImportFileSelected(elem){
    import_file_is_valid_file = false;
    jQuery('#import_uploaded_file_name').html('');
    if (elem.files && elem.files[0]) {
        jQuery('#import_uploaded_file_name').html(elem.files[0].name);
        var reader = new FileReader();
//        reader.onload = validateImportFile;
        reader.readAsDataURL(elem.files[0]);
    }
}

//jQuery('#import_file_custom_uploader').on('click', function() {
//    jQuery('#import_file').trigger('click');
//});

function validateImportFile(e)
{
    var supported_format = ['xls', 'xlsx'];
    var data = e.target.result;
    var is_valid_extension = false;
    for (var i in supported_format)
    {
        var str = supported_format[i];
        if (data.indexOf(str) > -1) {
            is_valid_extension = true;
            break;
        }
    }
    if (!is_valid_extension) {
        jQuery('#import_uploaded_file_name').html('');
        alert(import_invalid_file_error);
    } else if (Number(e.total) > (import_max_file_size * 1024 * 1024)) {
        jQuery('#import_uploaded_file_name').html('');
        alert(import_max_file_size_error);
    }
}

function closeVssModal(elem){
    jQuery('#'+elem).hide();
}


function validateRequestForm()
{
    jQuery('#vssmp-request-comment').removeClass('vssmp-hlyt-inv-field');
    jQuery(".validation_error_msg").remove();
    var comment = jQuery('#vssmp-request-comment').val();
    if(comment == ''){
        jQuery('#vssmp-request-comment').addClass('vssmp-hlyt-inv-field');
        jQuery('#vssmp-request-comment').after('<span class="validation_error_msg">'+required_text+'</span>');
    }else if (comment.length > 200 || comment == '') {
        jQuery('#vssmp-request-comment').addClass('vssmp-hlyt-inv-field');
        jQuery('#vssmp-request-comment').after('<span class="validation_error_msg">'+imp_exp_chars_length_error+'</span>');
    } else {
        jQuery('#request_feature_form').submit();
    }
}

function openImportFeatureRequestForm()
{
    jQuery('#request_bulkimport_feature_form').slideDown('fast');
}

function toggleDownloadActionFieldBlock()
{
    var open = false;

    if (jQuery('#import_product_type_field').val() != '' && jQuery('#import_attribute_set_field').val() != '') {
        open = true;
    }

    if (open) {
        jQuery('#download_template_row button').attr('disabled', false);
    } else {
        jQuery('#download_template_row button').attr('disabled', true);
    }
}

function toggleExportProductAttributes(e)
{
    if (jQuery(e).val() == 'product') {
        jQuery('.product_export_field').show();
    } else {
        jQuery('.product_export_field').hide();
    }
}

function displayImportExportInstructions()
{
    jQuery('#vssmp-importexport-instruction-popup #vssmp_popup_content').show();
    jQuery('#vssmp-importexport-instruction-popup').show();
}

function downloadTemplateFile(url)
{
    var url = url + 'product_type/' + jQuery('#import_product_type_field').val() + '/attribute_set/' + jQuery('#import_attribute_set_field').val();
    location.href = url;
}

function submitImportValidation()
{
    jQuery('[name="bulkimport[import_action]"]').removeClass('vssmp-hlyt-inv-field');
    jQuery('.validation_error_msg').remove();
    jQuery('#import_file_custom_uploader').removeClass('vssmp-hlyt-inv-field');
    var is_error = false;
    if(!jQuery('[name="bulkimport[import_action]"]').val()){
        jQuery('[name="bulkimport[import_action]"]').addClass('vssmp-hlyt-inv-field');
        jQuery('[name="bulkimport[import_action]"]').after('<span class="validation_error_msg">'+required_text+'</span>');
        is_error = true;
    }
    
    if(!jQuery('[name="import_file"]').val()){
        jQuery('#import_file_custom_uploader').addClass('vssmp-hlyt-inv-field');
        jQuery('#import_file_custom_uploader').after('<span class="validation_error_msg">'+required_text+'</span>');
        is_error = true;
    } else {
        var allowedExtensions = ['xls', 'xlsx'];
        var value = jQuery('[name="import_file"]').val();
        var file = value.toLowerCase();
        var extension = file.substring(file.lastIndexOf('.') + 1);
        if (jQuery.inArray(extension, allowedExtensions) == -1) {
            jQuery('#import_file_custom_uploader').addClass('vssmp-hlyt-inv-field');
            jQuery('#import_uploaded_file_name').after('<span class="validation_error_msg">'+import_file_type_error+'</span>');
            is_error = true;
        } 
    }
    
    if(!is_error){
        jQuery('#vss_mp_import_form').submit();
    }
}

function startImportProducts(url, path)
{
    jQuery.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: {path: path},
        beforeSend: function() {
            jQuery('#import_upload_prgressbar').show();
            jQuery('#import_response_container').hide();
            jQuery('#import_response_container').html('');
        },
        success: function(response) {
            var html = '';
            if (response['error'] == false) {
                html = '<div class="vssmp-glob-success">' + response['response'] + '</div>';
                setTimeout(function() {
                    location.href = back_to_importform_url;
                }, 5000);
            } else {
                html = '<div class="vssmp-glob-warning">' + response['response'] + '</div>';
            }
            jQuery('#import_response_container').html(html);
            jQuery('#import_response_container').show();
            jQuery('#import_upload_prgressbar').hide();
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            jQuery('#import_upload_prgressbar').hide();
            alert(textStatus);
        }
    });
}

function validateExportAction(url)
{
    var validate = true;
    jQuery('#vss_mp_export_tab_form').find('.vssmp-hlyt-inv-field').removeClass('vssmp-hlyt-inv-field');
    jQuery('#vss_mp_export_tab_form .validation_error_msg').remove();
    var type = jQuery('#vss_mp_export_tab_form select[name="bulkexport[export_type]"]').val();
    if (type == '') {
        alert('Select Export Type');
        return false;
    }
    if (type == 'product') {
        var obj = jQuery('#vss_mp_export_tab_form select[name="bulkexport[product_type]"]');
        if (obj.val() == '') {
            validate = false;
            obj.addClass('vssmp-hlyt-inv-field');
            obj.parent().append('<span class="validation_error_msg">Select Product Type</span>');
        }
        obj = jQuery('#vss_mp_export_tab_form select[name="bulkexport[attribute_set]"]');
        if (obj.val() == '') {
            validate = false;
            obj.addClass('vssmp-hlyt-inv-field');
            obj.parent().append('<span class="validation_error_msg">Select Attribute Set</span>');
        }
    }

    if (validate) {
        //jQuery('#vss_mp_export_tab_form').submit();
        jQuery.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: jQuery('#vss_mp_export_tab_form').serialize(),
            beforeSend: function() {
                jQuery('#import_upload_prgressbar').show();
                jQuery('#import_response_container').hide();
                jQuery('#import_response_container').html('');
            },
            success: function(response) {
                var html = '';
                if (response.error == false) {
                    location.href = response.response;
                } else {
                    html = '<div class="vssmp-glob-warning">' + response.response + '</div>';
                }
                jQuery('#import_response_container').html(html);
                jQuery('#import_response_container').show();
                jQuery('#import_upload_prgressbar').hide();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                jQuery('#import_upload_prgressbar').hide();
                alert(textStatus);
            }
        });
    }
};