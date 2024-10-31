function initSelect2MainProduct(exceptions) {
    jQuery('[data-select="main_product"]').select2({
        width: '50%',
        ajax: {
            url: ajaxurl,
            data: function(params) {
                return {
                    action: 'get_products',
                    s: params.term,
                    exceptions: exceptions.join(',')
                }
            },
            dataType: 'json',
            processResults: function(data) {
                return {
                    results: jQuery.map(data, function(value, key) {
                        return {
                            text: value,
                            id: key
                        }
                    })
                }
            }
        }
    });
};

function initSelect2MainProduct2(exceptions) {
    jQuery('[data-select="main_product2"]').select2({
        width: '50%',
        ajax: {
            url: ajaxurl,
            data: function(params) {
                return {
                    action: 'get_products',
                    s: params.term,
                    exceptions: exceptions.join(','),
                    postId: woocommerce_admin_meta_boxes.post_id
                }
            },
            dataType: 'json',
            processResults: function(data) {
                return {
                    results: jQuery.map(data, function(value, key) {
                        return {
                            text: value,
                            id: key
                        }
                    })
                }
            }
        }
    });
};

function initSelect2NewProduct(exceptions) {
    jQuery('[data-select="new_product"]').select2({
        width: '70%',
        ajax: {
            url: ajaxurl,
            data: function(params) {
                return {
                    action: 'get_products',
                    s: params.term,
                    exceptions: exceptions.join(',')
                }
            },
            dataType: 'json',
            processResults: function(data) {
                return {
                    results: jQuery.map(data, function(value, key) {
                        return {
                            text: value,
                            id: key
                        }
                    })
                }
            }
        }
    });
};

function closeBundleForm() {
    jQuery('[data-premmerce-bundle-form]').remove();
    jQuery('[data-premmerce-btn-add-bundle]').show();
}

function renderNewTable(data) {
    var bundlePanel = jQuery('[data-premmerce-bundle-panel]');

    bundlePanel.empty();
    bundlePanel.append(data);
}

function initDynamicForm(exceptions) {
    initSelect2MainProduct2(exceptions);
    initSelect2NewProduct(exceptions);

    jQuery('[data-premmerce-btn-save-bundle]').on('click', function() {

        var mainProduct = jQuery('[data-select="main_product2"]');

        if (mainProduct.length) {
            if (!mainProduct.val()) {
                alert(jQuery('[data-msg-main-products]').val());
                return false;
            }
        }

        if (jQuery('[data-bundle-product--table]').length > 0) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'premmerceSaveBundle',
                    data: jQuery('[data-premmerce-bundle-create]').find('input, select').serialize(),
                    postId: woocommerce_admin_meta_boxes.post_id,
                    product_type: jQuery('#product-type').val(),
                },
                success: function (data) {
                    closeBundleForm();

                    if (data) {
                        renderNewTable(data);
                    }
                },
            });
        } else {
            alert(jQuery('[data-msg-attached-products]').val());
            return false;
        }
    });

    jQuery('[data-premmerce-btn-cancel-bundle]').on('click', function() {
        closeBundleForm();
    });
};

jQuery(document).ready(function( $ ) {
    'use strict';

    var exceptions = [];
    var oldMainProduct;

    var $productsTable = $('[data-table="new_bundle_products"]');

    function onMainProductSelect(e) {
        var element = e.params.data;

        if (oldMainProduct) {
            var index = exceptions.indexOf(oldMainProduct);
            exceptions.splice(index, 1);
        }

        oldMainProduct = element.id;

        exceptions.push(element.id);
    }

    initSelect2MainProduct(exceptions);
    initSelect2NewProduct(exceptions);

    $(document).on('select2:select', '[data-select="main_product"]', function (e) {
        onMainProductSelect(e);
    });

    $(document).on('select2:select', '[data-select="main_product2"]', function (e) {
        onMainProductSelect(e);
    });

    $(document).on('select2:select', '[data-select="new_product"]', function (e) {
        var element = e.params.data;
        var row = this.closest('tr');

        var td1 = $('<td/>', {
            'html': [
                $('<div/>',{
                    'text': element.text,
                    'data-title': 'product_title'
                }),
                $('<input/>', {
                    'type': 'hidden',
                    'name': 'products[id][]',
                    'value': element.id
                })]
        });

        var td2 = $('<td/>', {
            'html' : $('<input/>', {
                'type': 'number',
                'min': '0',
                'max': '100',
                'name': 'products[discount][]',
                'data-input-percent' : ''
            })
        });

        var td3 = $('<td/>', {
            'html': $('<span/>', {
                'class': 'dashicons dashicons-no delete-product-row',
                'data-span': 'delete_product_row'
            })
        });

        $('<tr/>', {
            'html': [td1, td2, td3],
            'data-bundle-product--table' : ''
        }).insertBefore(row);

        $(this).val(-1);
        initSelect2NewProduct(exceptions);

        exceptions.push(element.id);

        $('[data-input-percent]').on('input',function(e){
            var value = $(this).val();

            if (value < 0) {
                $(this).val(0);
            }

            if (value > 100) {
                $(this).val(100);
            }
        });
    });

    $(document).on('click', '[data-span="delete_product_row"]', function () {
        var $this = $(this);

        var $tr = $this.closest('tr');
        var $table = $this.closest('[data-table="new_bundle_products"]');
        var countRows = $table.find('tbody tr').length;

        var index = exceptions.indexOf($tr.find('input[name="products[id][]"]').val());
        exceptions.splice(index, 1);

        $tr.remove();

        if (countRows == 1) {
            $table.hide();
        }
    });

    $(document).on('click', '[data-link="delete"]', function () {
        return confirm($('[data-lang-name="confirm-delete"]').attr('data-lang-value'));
    });

    $(document).on('click', '[data-link="ajax-delete"]', function (e) {
        e.preventDefault();

        var isConfirm = confirm($('[data-lang-name="confirm-delete"]').attr('data-lang-value'));

        if (isConfirm) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'premmerceDeleteBundle',
                    bundleId: $(this).data('id'),
                    postId: woocommerce_admin_meta_boxes.post_id
                },
                success: function (data) {
                    if (data) {
                        renderNewTable(data);
                    }
                },
            });
        }
    });

    $('[data-premmerce-btn-add-bundle]').on('click', function() {

        $(this).hide();

        var bundleCreate = $('[data-premmerce-bundle-create]');

        bundleCreate.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'premmerceGetFormAddNewBundle',
                product_type: $( '#product-type' ).val(),
            },
            success: function (data) {
                $('[data-premmerce-bundle-create]').append(data);

                exceptions = [woocommerce_admin_meta_boxes.post_id];
                initDynamicForm(exceptions);

                bundleCreate.unblock();
            }
        });

        return false;
    });

    $productsTable.find('input[name="products[id][]"]').each(function(i, elem) {
        exceptions.push(elem.value);
    });

    var mainProductVal = $('[data-select="main_product"]').val();
    if ($.inArray(mainProductVal,exceptions) == -1) {
        oldMainProduct = mainProductVal;
        exceptions.push(mainProductVal);
    }

    $('[data-span="tiptip-active"]').tipTip({
        'attribute': 'data-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200
    });

    $('[data-span="tiptip-products"]').tipTip({
        'attribute': 'data-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200
    });
});