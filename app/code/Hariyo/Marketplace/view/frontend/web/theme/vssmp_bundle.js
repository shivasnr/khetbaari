var vssmp_bundle_product_list_dt = '';
var bundle_product_search_filter_id = '';
var bundle_product_search_table_id = '';

var bundle_product_dt_objArr = [];
var bundle_selected_product_ids = '';

jQuery(document).ready(function(){
    
});

function processOptionIndex()
{
    if(jQuery('#product_bundle_option_container .bundle_option_row').length == 0){
        option_index = 0;
        selection_index = 0;
    }
    var tmp = option_index;
    option_index = option_index + 1;
    return tmp;
}

function createBundleOptionBlock(json_data)
{
    var blk_str = option_block_html;
    
    var parentIndex = processOptionIndex();
    
    blk_str = blk_str.replace(/{{option_index}}/g, parentIndex);
    if(json_data != '' && json_data != '{}'){
        var data = jQuery.parseJSON(json_data);
        blk_str = blk_str.replace(/{{bundle_default_title}}/g, data['title']);
        blk_str = blk_str.replace(/{{option_id}}/g, data['option_id']);
        blk_str = blk_str.replace(/{{bundle_parent_position}}/g, parseInt(data['position']));
    }else{
        blk_str = blk_str.replace(/{{bundle_default_title}}/g, '');
        blk_str = blk_str.replace(/{{option_id}}/g, '');
        blk_str = blk_str.replace(/{{bundle_parent_position}}/g, 0);    
    }
    
    jQuery('#product_bundle_option_container').prepend(blk_str);
    jQuery('#product_bundle_option_container #'+bundle_option_field_id+'_'+parentIndex+' #'+bundle_option_field_id+'_'+parentIndex+'_block_id').attr('value', selection_index);
    selection_index = selection_index+1;
}

function removeBundleOption(block_index)
{
    var container = '#product_bundle_option_container #'+bundle_option_field_id+'_'+block_index;
    jQuery(container+' input[name="bundle_options['+block_index+'][delete]"]').attr('value', 1);
    jQuery(container).hide();
    jQuery(container+' bundle_option_'+block_index+'_search').hide();
    jQuery(container+' bundle_option_'+block_index+'_search').html('');
}

function displayProductOptionSearchBlock(block_index)
{
    
    var str1 = bundle_option_field_id+'_'+block_index+'_search';
    
    if(jQuery('#product_bundle_option_container #'+str1+' table').length == 0){
        var blk_str = vssmp_bundle_product_search_blk;
        blk_str = blk_str.replace(/{{option_index}}/g, block_index);
        jQuery('#product_bundle_option_container #'+str1).html(blk_str);
        jQuery('#product_bundle_option_container #'+str1).show();
        //getListForBundleProduct(block_index);
    }
}

function getListForBundleProduct(block_index){
    bundle_product_search_filter_id = bundle_option_field_id+'_'+block_index+'_search_filter';
    bundle_product_search_table_id = bundle_option_field_id+'_'+block_index+'_search_table';
    
    var temp_selected_ids = [];
    bundle_selected_product_ids = '';
    if(jQuery('#'+bundle_option_field_id+'_'+block_index+'_selected_option table tbody tr').length){
        jQuery('#'+bundle_option_field_id+'_'+block_index+'_selected_option table tbody tr').each(function(){
            if(jQuery(this).find('td:eq(0) input.bundle_selectedoption_p_id').length && jQuery(this).find('td:eq(0) input.bundle_selectedoption_p_id') != ''){
                temp_selected_ids.push(jQuery(this).find('td:eq(0) input.bundle_selectedoption_p_id').attr('value'));    
            }
        });
        
        bundle_selected_product_ids = ((temp_selected_ids.length > 0)? temp_selected_ids.join(',') : '');
    }
    
    var table_dt_index = getBundleDtObjName(block_index);
    
    var dtObjIndex = getVssDtObj(table_dt_index);
    if(dtObjIndex > -1){
        bundle_product_dt_objArr[dtObjIndex][1].fnDraw();
    }else{
        bundle_product_dt_objArr.push([table_dt_index, '']);
        dtObjIndex = getVssDtObj(table_dt_index);
        bundle_product_dt_objArr[dtObjIndex][1] = jQuery('#'+bundle_product_search_table_id).dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "serverSide": true,
                "ajax": {
                        "url": vssmp_bundle_product_dt_url+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            param.list_params = product_list_params,
                            param.list_type = 'bundle',
                            param.selected_product_ids = bundle_selected_product_ids,
                            param.product_id = ((jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_id"]').val() != jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_id"]').attr('data-placeholder'))? jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_id"]').val() : '');
                            param.sku = ((jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_sku"]').val() != jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_sku"]').attr('data-placeholder'))? jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_sku"]').val() : '');
                            param.name = ((jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_name"]').val() != jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_name"]').attr('data-placeholder'))? jQuery('#'+bundle_product_search_filter_id+' input[name="sub_pro_filter_name"]').val() : '');
                            param.inv_status = ((jQuery('#'+bundle_product_search_filter_id+' select[name="sub_pro_filter_inv_status"]').val() != '')? jQuery('#'+bundle_product_search_filter_id+' select[name="sub_pro_filter_inv_status"]').val() : '');
                            param.attr_set = ((jQuery('#'+bundle_product_search_filter_id+' select[name="sub_pro_filter_attr_set"]').val() != '')? jQuery('#'+bundle_product_search_filter_id+' select[name="sub_pro_filter_attr_set"]').val() : '');
                        }
                    },
                "columnDefs": [ 
                    {
                        className: "text-center",
                        orderable: false,
                        searchable: false,
                        "targets": 0
                    },
                    {name: 'entity_id', "targets": 1, className: "text-center"},
                    {name: 'name', "targets": 2},
                    {name: 'sku', "targets": 3},
                    {name: 'attribute_set_id', "targets": 4},
                    {name: 'price', "targets": 5, className: "text-center"},
                    {name: 'inventory_in_stock', "targets": 6},
                    {"targets": 7, className: "text-center", "width": '100'}
                ]
        } );
    }
}

function getBundleDtObjName(block_index)
{
    return 'table_dt_index'+block_index;
}

function getVssDtObj(obj_name)
{
    for (var i in bundle_product_dt_objArr){
        if(bundle_product_dt_objArr[i][0] == obj_name){
            return i;
        }
    }
    return -1;
}

function removedtObj(obj_name)
{
    var temp = [];
    for (var i in bundle_product_dt_objArr){
        if(bundle_product_dt_objArr[i][0] != obj_name){
            temp.push(bundle_product_dt_objArr[i]);
        }
    }
    bundle_product_dt_objArr = temp;
}

function addSelectedBundleOption(block_index, json_data, from)
{
    var data = '';
    var block_container = '#product_bundle_option_container #'+bundle_option_field_id+'_'+block_index;
    if(from == 'db'){
        data = json_data; //jQuery.parseJSON(json_data);
    }else {
        if(jQuery('#'+bundle_option_field_id+'_'+block_index+'_search_table tbody tr input[type="checkbox"]:checked').length){
            jQuery('#'+bundle_option_field_id+'_'+block_index+'_search_table tbody tr').each(function(){
                if(jQuery(this).find('input[type="checkbox"]:checked').length){
                    var p_name = jQuery(this).find('td:eq(2) a').html();
                    var p_sku = jQuery(this).find('td:eq(3)').html();
                    var p_id = jQuery(this).find('input[type="checkbox"]:checked').attr('value');
                    var p_qty = jQuery(this).find('input[name="bundle_entered_qty"]').val();
                    var numeric_reg = validation_regex.numeric;
                    if(!numeric_reg.test(p_qty) || p_qty == 0){
                        p_qty = 1;
                    }
                    
                    data = {
                        option_id: jQuery('#'+bundle_option_field_id+'_id_'+block_index).val(),
                        selection_id: '',
                        name: p_name,
                        sku: p_sku,
                        entity_id: p_id,
                        selection_price_value: 0,
                        selection_price_type: 0,
                        selection_qty: p_qty,
                        selection_can_change_qty: 1,
                        position: 0,
                        is_default: 0
                    };
                }
            });
        }
        removedtObj(getBundleDtObjName(block_index));
        jQuery('#'+bundle_option_field_id+'_'+block_index+'_search').hide();
        jQuery('#'+bundle_option_field_id+'_'+block_index+'_search').html('');
    }
    if(data.entity_id != undefined){
        var row_html = vssmp_bundle_product_selection_blk;
        var current_selection_index = parseInt(jQuery(block_container+' #'+bundle_option_field_id+'_'+block_index+'_block_id').attr('value'));
        row_html = row_html.replace(/{{option_index}}/g, block_index);
        row_html = row_html.replace(/{{selection_index}}/g, current_selection_index);
        
        row_html = row_html.replace(/{{selection_id}}/g, data.selection_id);
        row_html = row_html.replace(/{{option_id}}/g, data.option_id);
        row_html = row_html.replace(/{{product_name}}/g, data.name);
        row_html = row_html.replace(/{{product_sku}}/g, data.sku);
        row_html = row_html.replace(/{{product_id}}/g, data.entity_id);
        row_html = row_html.replace(/{{selection_price_value}}/g, data.selection_price_value);
        row_html = row_html.replace(/{{bundle_entered_quantity}}/g, parseInt(data.selection_qty));
        row_html = row_html.replace(/{{bundle_sel_pro_position}}/g, data.position);
        if(data.is_default == 1)
        {
            row_html = row_html.replace(/{{checked}}/g, 'checked="checked"');
        }
        
        jQuery(block_container+' #'+bundle_option_field_id+'_'+block_index+'_selected_option tbody#vssmp-bundle-selected-subproduct-'+block_index).append(row_html);
        
        jQuery('#vssmp-bundle-selected-subproduct-'+block_index+' #'+bundle_option_selection_id+'_'+current_selection_index+'_price_type option').each(function(){
            if(data.selection_price_type == jQuery(this).val()){
                jQuery(this).attr('selected','selected');
            }
        });
        
        jQuery('#vssmp-bundle-selected-subproduct-'+block_index+' #'+bundle_option_selection_id+'_'+current_selection_index+'_can_change_qty option').each(function(){
            if(data.selection_can_change_qty == jQuery(this).val()){
                jQuery(this).attr('selected','selected');
            }
        });
        
        jQuery(block_container+' #'+bundle_option_field_id+'_'+block_index+'_selected_option').show();
        current_selection_index = current_selection_index +1
        jQuery(block_container+' #'+bundle_option_field_id+'_'+block_index+'_block_id').attr('value', current_selection_index);
    }
}

function removeSelectedOption(block_index, selection_index)
{
    var block_container = '#product_bundle_option_container #'+bundle_option_field_id+'_'+block_index;
    var row_container = block_container+' #'+bundle_option_field_id+'_'+block_index+'_selected_option tbody#vssmp-bundle-selected-subproduct-'+block_index+' tr#bundle_selection_row_'+selection_index;
    jQuery(row_container+' input[name="bundle_selections[1][1][delete]"]').attr('value', 1);
    jQuery(row_container).hide();
};

