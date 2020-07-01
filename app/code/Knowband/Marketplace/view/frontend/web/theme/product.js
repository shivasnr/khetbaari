var vssmp_image_index = 0;
var vssmp_pro_img_row = '';
var sub_product_list_dt;
var rel_product_list_dt;
var rel_load_first_time = true;
var file_upload_index = 0;

require(['jquery', 
    'Knowband_Marketplace/theme/plugins/multiselect/jquery.multiple.select',
    "Knowband_Marketplace/theme/plugins/DataTables/DataTables-1.10.18/js/jquery.dataTables.min"
    ], function(jQuery){
jQuery(document).ready(function(){
    
    if(jQuery('#vssmp_category_mapping_list').length){
        jQuery("#vssmp_category_mapping_list").multipleSelect({
            placeholder: category_placeholder,
            charReplace: category_child_rel_symbol
        });
    }
    
    jQuery(document.body).on('focus', '#vssmp-product-form-inner input[type="text"], #vssmp-product-form-inner textarea, #vssmp-simple-product-popup input[type="text"], #vssmp-simple-product-popup textarea', function(){
        if(jQuery(this).attr('data-placeholder')){
            var orig_placeholder = jQuery(this).attr('data-placeholder');
            var field_placeholder = jQuery(this).attr('value');
            if(orig_placeholder.toLowerCase() == field_placeholder.toLowerCase()){
                jQuery(this).attr('value', '');
            }    
        } 
    });
    
    jQuery('body').on('change', '.vssmp-product-image-upl-file', function(){
        file_upload_index = jQuery(this).attr('data-index');
        if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = vssmpImageIsLoaded;
                reader.readAsDataURL(this.files[0]);
        }
    });
    
    //Related Product List
    if(jQuery('#vssmp_rel_products').length){
        rel_product_list_dt = jQuery('#vssmp_rel_products').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "searching": false,
                "processing": true,
                "serverSide": true,
                "ajax": {
                        "url": vssmp_relproducts_datatable_url+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            param.checked_products = jQuery('#vssmp_related_container input[name="product[rel_products]"]').attr('value');
                            param.rel_load_first_time = rel_load_first_time;
                            param.list_params = product_list_params;
                            param.product_id = ((jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_id"]').val() != jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_id"]').attr('data-placeholder'))? jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_id"]').val() : '');
                            param.sku = ((jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_sku"]').val() != jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_sku"]').attr('data-placeholder'))? jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_sku"]').val() : '');
                            param.name = ((jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_name"]').val() != jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_name"]').attr('data-placeholder'))? jQuery('#rel-pro-list-filter-list input[name="sub_pro_filter_name"]').val() : '');
                            param.inv_status = ((jQuery('#rel-pro-list-filter-list select[name="sub_pro_filter_inv_status"]').val() != '')? jQuery('#rel-pro-list-filter-list select[name="sub_pro_filter_inv_status"]').val() : '');
                            param.attr_set = ((jQuery('#rel-pro-list-filter-list select[name="sub_pro_filter_attr_set"]').val() != '')? jQuery('#rel-pro-list-filter-list select[name="sub_pro_filter_attr_set"]').val() : '');
                        }
                    },
                    "initComplete": function(settings, json) {
                        rel_load_first_time = false;
                    },
                "columnDefs": [ 
                    {
                        className: "text-center",
                        orderable: false,
                        searchable: false,
                        "targets": 0
                    },
                    {name: 'entity_id', "targets": 1, className: "text-right"},
                    {name: 'name', "targets": 2},
                    {name: 'sku', "targets": 3},
                    {name: 'attribute_set_id', "targets": 4},
                    {name: 'price', "targets": 5, className: "text-right"},
                    {name: 'inventory_in_stock', "targets": 6}
                ]
        } );    
    }
    
    //Sub Product List
    if(jQuery('#vssmp_sub_products').length){
        sub_product_list_dt = jQuery('#vssmp_sub_products').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "searching": false,
                "processing": true,
                "serverSide": true,
                "ajax": {
                        "url": vssmp_subproducts_datatable_url+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(sub_product_parent == 'grouped'){
                                param.checked_products = jQuery('#vssmp_associate_container input[name="product[sub_products]"]').attr('value');
                            }else if(sub_product_parent == 'configurable'){
                                param.checked_products = jQuery('#vssmp_associate_container input[name="sub_product_keys"]').attr('value');
                            }
                            
                            param.sub_load_first_time = sub_load_first_time;
                            param.list_params = product_list_params;
                            param.product_id = ((jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_id"]').val() != jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_id"]').attr('data-placeholder'))? jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_id"]').val() : '');
                            param.sku = ((jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_sku"]').val() != jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_sku"]').attr('data-placeholder'))? jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_sku"]').val() : '');
                            param.name = ((jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_name"]').val() != jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_name"]').attr('data-placeholder'))? jQuery('#sub-pro-list-filter-list input[name="sub_pro_filter_name"]').val() : '');
                            param.inv_status = ((jQuery('#sub-pro-list-filter-list select[name="sub_pro_filter_inv_status"]').val() != '')? jQuery('#sub-pro-list-filter-list select[name="sub_pro_filter_inv_status"]').val() : '');
                            param.attr_set = ((jQuery('#sub-pro-list-filter-list select[name="sub_pro_filter_attr_set"]').val() != '')? jQuery('#sub-pro-list-filter-list select[name="sub_pro_filter_attr_set"]').val() : '');
                        }
                    },
                "initComplete": function(settings, json) {
                    sub_load_first_time = false;
                },
                "columnDefs": [ 
                    {
                        className: "vssmp-txt-cntr",
                        orderable: false,
                        searchable: false,
                        "targets": 0
                    },
                    {name: 'entity_id', "targets": 1, className: "vssmp-txt-ryt"},
                    {name: 'name', "targets": 2},
                    {name: 'sku', "targets": 3},
                    {name: 'attribute_set_id', "targets": 4},
                    {name: 'price', "targets": 5, className: "vssmp-txt-ryt"},
                    {name: 'inventory_in_stock', "targets": 6}
                ]
        } );   
    }
    
});
});
function createImageRow(json_data)
{
    var data = {};
    jQuery('#vssmp-img-ext-err').hide();
    vssmp_image_index = vssmp_image_index+1;
    vssmp_pro_img_row = '';
    vssmp_pro_img_row += '<tr id="vssmp-image'+vssmp_image_index+'">';
    vssmp_pro_img_row += '<td class="cell-image">';
    vssmp_pro_img_row += "<input type='hidden' name='product[gallery]["+vssmp_image_index+"][delete]' value='' />";
    vssmp_pro_img_row += "<input type='hidden' name='product[gallery]["+vssmp_image_index+"][old_data]' value='"+json_data+"' />";
    if(json_data == '[]'){
        data = {
            value_id: 0,
            url: '',
            label: '',
            position: 0,
        };
        vssmp_pro_img_row += '<div class="vssmp-pro-img-preview" style="display:none;"><img src="'+data.url+'" title="'+data.label+'" width="100"></div>';
    }else{
        data = jQuery.parseJSON(json_data);
        vssmp_pro_img_row += '<div class="vssmp-pro-img-preview"><img src="'+data.url+'" title="'+data.label+'" width="100"></div>';
    }
    
    vssmp_pro_img_row += '<div class=""><input class="vssmp-product-image-upl-file" style="display:none;" name="media_gallery['+vssmp_image_index+']" type="file" value="" data-index="'+vssmp_image_index+'"/><button type="button" class="btn btn-xs btn-primary" onclick="startImageUpload(this)">' + browse_text + '</button></div>';
    vssmp_pro_img_row += '</td>';
//    vssmp_pro_img_row += '<td class="cell-label"><div class="form-group"><input type="text" name="product[gallery]['+vssmp_image_index+'][label]" class="form-control" validate="varchar" value="'+data.label+'"/></div></td>';
//    vssmp_pro_img_row += '<td class="cell-position"><div class="form-group"><input type="text" name="product[gallery]['+vssmp_image_index+'][position]" class="form-control" validate="int" value="'+data.position+'" /></div></td>';
    for(var i=0; i<vssmp_image_types.length; i++)
    {
        var checked = '';
        var value_image = vssmp_image_types[i]['id'];
        if(data[value_image] != undefined && data[value_image] == 1){
            checked = 'checked="checked"';
        }
        vssmp_pro_img_row += '<td class="text-center"><input type="radio" name="product['+vssmp_image_types[i]['id']+']" value="'+vssmp_image_index+'" '+checked+'/></td>';
    }

    vssmp_pro_img_row += '<td class="text-center"><button class="btn btn-danger btn-sm" onclick="return removeVssProductImage('+vssmp_image_index+', true)"><i class="fa fa-trash-o"></i></button></td>';
    vssmp_pro_img_row += '</tr>';
    jQuery('#vssmp-product-img-body #product-image-blank-row').remove();
    jQuery('#vssmp-product-img-body').append(vssmp_pro_img_row);
    vssmpProcessImageDefaultSelection();
}

function vssmpProcessImageDefaultSelection()
{
    if(jQuery('#vssmp-product-img-body tr').length == 1){
        jQuery('#vssmp-product-img-body tr#vssmp-image'+vssmp_image_index+' input[type="radio"]').each(function(){
            jQuery(this).attr('checked', 'checked');
        });
    }    
}

function startImageUpload(e)
{
    jQuery(e).parent().find('input[type="file"]').trigger('click');
}

function checkMediaUpload(val)
{
    for(var i=0; i<vssmp_pro_img_format.length; i++)
    {
        var str = 'image/'+vssmp_pro_img_format[i];
        if(val.indexOf(str.toLowerCase()) > -1){
            return true;
        }
    }
    return false;
}

function vssmpImageIsLoaded(e) 
{
    if(checkMediaUpload(e.target.result)){
        var container = 'tr#vssmp-image'+file_upload_index+' td.cell-image .vssmp-pro-img-preview';
        jQuery(container+' img').attr('src', e.target.result);
        jQuery(container).show();
    }else{
        removeVssProductImage(file_upload_index, false);
        jQuery('#vssmp-img-ext-err').show();
    }
    
};

function removeVssProductImage(image_row_id, hide)
{
    
    jQuery('#vssmp-img-ext-err').hide();
    jQuery('#vssmp-image'+image_row_id+' input[name="product[gallery]['+image_row_id+'][delete]"]').attr('value', 1);
    var container = 'tr#vssmp-image'+image_row_id+' td.cell-image .vssmp-pro-img-preview';
    jQuery(container+' img').attr('src', '');
    jQuery(container).hide();
    if(hide){
        jQuery('#vssmp-image'+image_row_id).hide();
    } 
    return false;
}

function drawVssmpRelProList()
{
    rel_product_list_dt.fnDraw();
}

function vssmpProcessRelatedSelect(e)
{
    var value = parseInt(jQuery(e).val());
    var index = jQuery.inArray(value, checked_related_products);
    if(jQuery(e).is(':checked')){
        if(index < 0){
            checked_related_products.push(value);
        }
    }else{
        if(index >= 0){
            checked_related_products.splice(index, 1);
        }
    }
    createRelatedProJson();
}

function createRelatedProJson()
{
    var selected_products = {};
    index = 0;
    for(index=0; index < checked_related_products.length; index++){
        selected_products[index] = checked_related_products[index];
    }

    jQuery('#vssmp_related_container input[name="product[rel_products]"]').attr('value', JSON.stringify(selected_products));
}

function drawVssmpSubProTable()
{
    sub_product_list_dt.fnDraw();
}

function vssmpProcessSubSelect(e)
{
    if(sub_product_parent == 'grouped'){
        var value = parseInt(jQuery(e).val());
        var index;
        if(jQuery(e).is(':checked')){
//            if(checked_sub_products[value] == undefined){
                checked_sub_products[value] = [];
                checked_sub_products[value]['id'] = value;
                checked_sub_products[value]['qty'] = jQuery(e).parent().parent().find('input[name="group_pro_qty[]"]').val();
//            }
        }else{
            if(checked_sub_products[value] != undefined){
                var tmp = [];
                for(index in checked_sub_products){
                    if(checked_sub_products[index]['id'] != undefined && checked_sub_products[index]['id'] != ''){
                        if(value != index){
                            tmp[index] = [];
                            tmp[index]['id'] = index;
                            tmp[index]['qty'] = checked_sub_products[index]['qty'];
                        }
                    }
                }
                checked_sub_products = tmp;
            }
        }
    }else if(sub_product_parent == 'configurable'){
        var key = jQuery(e).attr('id');
        key = parseInt(key.replace('associate_',''));
        var index = jQuery.inArray(key, checked_sub_products);
        if(jQuery(e).is(':checked')){
            if(index < 0){
                if(!jQuery('#vssmp_associate_data input[name="product[sub_products]['+key+']"]').length){
                    var htm = "<input type='hidden' name='product[sub_products]["+key+"]' value='"+jQuery(e).val()+"' />";
                    jQuery('#vssmp_associate_data').append(htm);
                }
                checked_sub_products.push(key);
            }
            
        }else{
            if(index >= 0){
                if(jQuery('#vssmp_associate_data input[name="product[sub_products]['+key+']"]').length){
                    jQuery('#vssmp_associate_data input[name="product[sub_products]['+key+']"]').remove();
                }
                checked_sub_products.splice(index, 1);
            }
        }
    }
    createSubProductJson();
}

function vssmpProcessSubProductQtyChange(e)
{
    e = jQuery(e).parent().parent().find('input[name="sub_products[]"]')
    if(sub_product_parent == 'grouped'){
        var value = parseInt(jQuery(e).val());
        var index;
        if(jQuery(e).is(':checked')){
//            if(checked_sub_products[value] == undefined){
                checked_sub_products[value] = [];
                checked_sub_products[value]['id'] = value;
                checked_sub_products[value]['qty'] = jQuery(e).parent().parent().find('input[name="group_pro_qty[]"]').val();
//            } 
        }else{
            if(checked_sub_products[value] != undefined){
                var tmp = [];
                for(index in checked_sub_products){
                    if(checked_sub_products[index]['id'] != undefined && checked_sub_products[index]['id'] != ''){
                        if(value != index){
                            tmp[index] = [];
                            tmp[index]['id'] = index;
                            tmp[index]['qty'] = checked_sub_products[index]['qty'];
                        }
                    }
                }
                checked_sub_products = tmp;
            }
        }
    }
    createSubProductJson();
}

function createSubProductJson()
{
    var selected_products = {};
    if(sub_product_parent == 'grouped'){
        for(var key in checked_sub_products){
            if(checked_sub_products[key]['id'] != undefined && checked_sub_products[key]['id'] != ''){
                selected_products[key] = {'id' : checked_sub_products[key]['id'], 'qty': checked_sub_products[key]['qty']};    
            }
        }
        jQuery('#vssmp_associate_container input[name="product[sub_products]"]').attr('value', JSON.stringify(selected_products));
    }else if(sub_product_parent == 'configurable'){
        var index = 0;
        for(index=0; index < checked_sub_products.length; index++){
            selected_products[index] = checked_sub_products[index];
        }
        jQuery('#vssmp_associate_container input[name="sub_product_keys"]').attr('value', JSON.stringify(selected_products));
    }
}

function cancelProductEditing(redirect_url)
{
    var closeForm = confirm('Saved Data will be lost.\n Are you sure?');
    if(closeForm == true){
        window.location.href = redirect_url;
    }
}

//////////////////////////// Start - Pop up Window ///////////////////////////////////

function createEmptySimpleProduct(url)
{
    if (this.win && !this.win.closed) {
            this.win.close();
    }

    this.win = window.open(url, '',
            'width=1000,height=700,resizable=1,scrollbars=1');
    this.win.focus();
}

function closeSimpleProductPopup()
{
    window.close();
    sub_product_list_dt.fnDraw();
    rel_product_list_dt.fnDraw();
}
//////////////////////////// End - Pop up Window ///////////////////////////////////

/////////////////////////// Start - Quick Create Product //////////////////////////////////////////
function vssmpCreateQuickProduct(url)
{
    var error = false;
    jQuery('#vssmp-simple-product-popup input[type="text"]').each(function(){
        //vssmpProcessValidation(this);
        var isValid = true;
        var type = '';
        var value = jQuery(this).val();
        if(jQuery(this).hasClass('vssmp_req_field') && value == ''){
            error = true;
            isValid = false;
        }else if(jQuery(this).hasClass('vssmp_req_field') && value != ''){
            type = getValidationType(this)
            if(type){
                isValid = vssmpValidateField(type, value);
                if(!isValid){
                    error = true;
                }
            }        
        }else if(!jQuery(this).hasClass('vssmp_req_field') && value != ''){
            type = getValidationType(this)
            if(type){
                isValid = vssmpValidateField(type, value);
                if(!isValid){
                    error = true;
                }
            }        
        }
        
        if(!isValid){
            jQuery(this).addClass('vssmp-hlyt-inv-field');
        }
    });
    
    if(error){
        jQuery('#vssmp-simple-product-popup .vssmp-glob-warning').html(vssmp_invalid_form_message);
        jQuery('#vssmp-simple-product-popup .vssmp-glob-warning').show();
        jQuery("#vssmp-simple-product-popup .vssmp-map-popup-content").animate({scrollTop:0}, '500');
    }else{
        jQuery.ajax({
            url: url + (url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true'),
            type: 'POST',
            dataType: 'json',
            data: jQuery('#vssmp-simple-product-popup input, #vssmp-simple-product-popup select, #vssmp-simple-product-popup textarea'),
            beforeSend: function() {
                    jQuery('#vssmp-simple-product-popup #vssmp-quick-pro-save-progress').css('display','inline-block');
            },
            success: function(json) {
                if(json['success'] != undefined){
                    jQuery('#vssmp-simple-product-popup #vssmp-quick-pro-save-progress').hide();
                    closeModalAfterReset('vssmp-simple-product-popup');
                    jQuery('#vssmp-simple-product-popup input[type="text"]').attr('value', '');
                    jQuery('#vssmp-simple-product-popup select option').removeAttr('selected');
                    jQuery('#vssmp-simple-product-popup textarea').html('');
                    jQuery('#vssmp-simple-product-popup .vssmp-glob-warning').html('');
                    if(sub_product_list_dt != ''){
                        sub_product_list_dt.fnDraw();
                    }
                    if(rel_product_list_dt != ''){
                        rel_product_list_dt.fnDraw();
                    }
                }else{
                    var msg = 'Technical Error';
                    if(json['error'] != undefined){
                        msg = json['error']['message'];
                    }
                    jQuery('#vssmp-simple-product-popup #vssmp-quick-pro-save-progress').hide();
                    jQuery('#vssmp-simple-product-popup .vssmp-glob-warning').html(msg);
                    jQuery('#vssmp-simple-product-popup .vssmp-glob-warning').show();
                    jQuery("#vssmp-simple-product-popup .vssmp-map-popup-content").animate({scrollTop:0}, '500');
                }
            }
        });
    }
}
/////////////////////////// Start - Quick Create Product //////////////////////////////////////////


function vssmpCheckSkuExistence(elem)
{
    jQuery(elem).closest('.form-group').removeClass('has-error');
    jQuery(elem).closest('.form-group').find('.help-block').remove();
    jQuery.ajax({
        url: vssmp_check_sku_url + (vssmp_check_sku_url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true'),
        type: 'POST',
        dataType: 'json',
        data: 'sku='+jQuery(elem).val()+'&product_id='+parseInt(vssmp_product_id),
        beforeSend: function() {

        },
        success: function(json) {
            if(json['error'] != undefined){
                jQuery(elem).closest('.form-group').addClass('has-error');
                jQuery(elem).closest('.form-group').append('<span class="help-block">'+json['error']+'</span>');
            }
        }
    });
}

function onUrlkeyChanged(url_key)
{
    if(jQuery('#'+url_key).val() == jQuery('#url_key_create_redirect_chk').val()){
        jQuery('#url_key_create_redirect_chk').attr('disabled', true);
        jQuery('#url_key_create_redirect_hid').attr('disabled', true);
    }else{
        jQuery('#url_key_create_redirect_chk').removeAttr('disabled');
        jQuery('#url_key_create_redirect_hid').removeAttr('disabled');
    }
}

function vssmpSaveAndContinue(e)
{
    jQuery('#vssmp-product-form-inner input[name="edit_mode"]').attr('value', 1);
    vssmpValidateForm(e);
}

function vssmpValidateForm(e)
{
    var error = false;
    jQuery('#vssmp_glob_msg_content').hide();
    jQuery('#vssmp_glob_msg_content').html('');
    jQuery('#vss_product_form_container .overlay-wrapper').show();
    jQuery('#vssmp-product-form-inner > .box').removeClass('box-danger');
    jQuery('#vssmp-product-form-inner > .box .tab-error-highlighter').hide();
    jQuery('#vssmp-product-form-inner .has-error').removeClass('has-error');
    jQuery('#vssmp-product-form-inner .help-block').remove();
    
    jQuery('#vssmp-product-form-inner input[type="text"], #vssmp-product-form-inner select, #vssmp-product-form-inner textarea').each(function(){
        var field_error = false;
        if (validateVssField(this)) {
            error = true;
            field_error = true;
        }
        if (field_error) {
            jQuery(this).closest('.form-group').addClass('has-error');
            jQuery(this).closest('form > .box').addClass('box-danger');
            jQuery(this).closest('form > .box').find('.tab-error-highlighter').show();
        }
    });
    
    //product price validation
    if(jQuery('input[name="product[price]"]').length
            && jQuery('input[name="product[price]"]').val() != ''){
        var price = jQuery('input[name="product[price]"]').val();
        if(isNaN(price) || price < 0){
            error = true;
            jQuery('input[name="product[price]"]').closest('.form-group').addClass('has-error');
            jQuery('input[name="product[price]"]').closest('form > .box').addClass('box-danger');
            jQuery('input[name="product[price]"]').closest('form > .box').find('.tab-error-highlighter').show();
        }
    }
    
    //product weight validation
    if (jQuery('input[name="product[weight]"]').length
            && jQuery('input[name="product[weight]"]').val() != '') {
        var weight = jQuery('input[name="product[weight]"]').val();
        if (isNaN(weight) || weight < 0) {
            error = true;
            jQuery('input[name="product[weight]"]').closest('.form-group').addClass('has-error');
            jQuery('input[name="product[weight]"]').closest('form > .box').addClass('box-danger');
            jQuery('input[name="product[weight]"]').closest('form > .box').find('.tab-error-highlighter').show();
        }
    }
    
    //product stock data quantity validation
    if (jQuery('input[name="product[stock_data][qty]"]').length
            && jQuery('input[name="product[stock_data][qty]"]').val() != '') {
        var qty = jQuery('input[name="product[stock_data][qty]"]').val();
        if (isNaN(qty) || qty < 0) {
            error = true;
            jQuery('input[name="product[stock_data][qty]"]').closest('.form-group').addClass('has-error');
            jQuery('input[name="product[stock_data][qty]"]').closest('form > .box').addClass('box-danger');
            jQuery('input[name="product[stock_data][qty]"]').closest('form > .box').find('.tab-error-highlighter').show();
        }
    }
        
    
    if (error) {
        jQuery('#vss_product_form_container .overlay-wrapper').hide();
        jQuery('#vssmp_glob_msg_content').html(vssmp_invalid_form_message);
        jQuery('#vssmp_glob_msg_content').show();
        jQuery("html, body").animate({scrollTop:0}, '500');
        setTimeout(function(){ jQuery('#vssmp_glob_msg_content').hide(); }, 10000);
    } else {
        
        //Product New Dates Validation
        if(jQuery('input[name="product[news_from_date]"]').length 
                && jQuery('input[name="product[news_to_date]"]').length
                && jQuery('input[name="product[news_from_date]"]').val() != ''
                && jQuery('input[name="product[news_to_date]"]').val() != ''){
            var start_date = jQuery('input[name="product[news_from_date]"]').val();
            var end_date = jQuery('input[name="product[news_to_date]"]').val();
            if((new Date(start_date).getTime()) > (new Date(end_date).getTime())){
                jQuery('input[name="product[news_from_date]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[news_to_date]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[news_to_date]"]').closest('.form-group').append('<span class="help-block">Past date not allowed</span>');
                jQuery('input[name="product[news_from_date]"]').closest('form > .box').addClass('box-danger');
                jQuery('input[name="product[news_from_date]"]').closest('form > .box').find('.tab-error-highlighter').show();
                error = true;
            }
        }
        
        
        //Product Special Dates Validation
        if(jQuery('input[name="product[special_from_date]"]').length
                && jQuery('input[name="product[special_to_date]"]').length
                && jQuery('input[name="product[special_from_date]"]').val() != ''
                && jQuery('input[name="product[special_to_date]"]').val() != ''){
            var start_date = jQuery('input[name="product[special_from_date]"]').val();
            var end_date = jQuery('input[name="product[special_to_date]"]').val();
            if((new Date(start_date).getTime()) > (new Date(end_date).getTime())){
                jQuery('input[name="product[special_from_date]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[special_to_date]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[special_to_date]"]').closest('.form-group').append('<span class="help-block">'+vssmp_invalid_date_msg+'</span>');
                jQuery('input[name="product[special_to_date]"]').closest('form > .box').addClass('box-danger');
                jQuery('input[name="product[special_to_date]"]').closest('form > .box').find('.tab-error-highlighter').show();
                error = true;
            }
        }
        
        //Product Special Price Validation
        if(jQuery('input[name="product[price]"]').length
                && jQuery('input[name="product[special_price]"]').length
                && jQuery('input[name="product[price]"]').val() != ''
                && jQuery('input[name="product[special_price]"]').val() != ''){
            var price = jQuery('input[name="product[price]"]').val();
            var special_price = jQuery('input[name="product[special_price]"]').val();
            if(parseFloat(price) < parseFloat(special_price)){
                jQuery('input[name="product[price]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[special_price]"]').closest('.form-group').addClass('has-error');
                jQuery('input[name="product[special_price]"]').closest('.form-group').append('<span class="help-block">'+vssmp_invalid_price_msg+'</span>');
                jQuery('input[name="product[special_price]"]').closest('form > .box').addClass('box-danger');
                jQuery('input[name="product[special_price]"]').closest('form > .box').find('.tab-error-highlighter').show();
                error = true;
            }
        }
        
        if(error){
            jQuery('#vss_product_form_container .overlay-wrapper').hide();
            jQuery('#vssmp_glob_msg_content').html(vssmp_invalid_form_message);
            jQuery('#vssmp_glob_msg_content').show();
            jQuery("html, body").animate({scrollTop:0}, '500');
            setTimeout(function(){ jQuery('#vssmp_glob_msg_content').hide(); }, 10000);
        }else{
            jQuery.ajax({
                url: vssmp_validate_action + (vssmp_validate_action.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true'),
                type: 'POST',
                dataType: 'json',
                data: jQuery('form#vssmp-product-form-inner input, form#vssmp-product-form-inner input[type="checkbox"]:checked, form#vssmp-product-form-inner select, form#vssmp-product-form-inner textarea'),
                beforeSend: function() {

                },
                success: function(json) {
                    if(json['error'] != undefined && json['error']){
                        jQuery('#vss_product_form_container .overlay-wrapper').hide();
                        jQuery('#vssmp_glob_msg_content').html(json['message']);
                        jQuery('#vssmp_glob_msg_content').show();
                        jQuery("html, body").animate({scrollTop:0}, '500');
                        setTimeout(function(){ jQuery('#vssmp_glob_msg_content').hide(); }, 10000);
                    }else if(json['error'] != undefined && !json['error']){
                        jQuery('#vssmp-product-form-inner').submit();
                    }
                }
            });
        }
    }
};
