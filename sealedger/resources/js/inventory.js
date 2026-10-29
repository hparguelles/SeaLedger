import $ from 'jquery';
import { openModal } from './modal';

$(function () {
    const $form = $('#productForm');

    if (!$form.length) {
        return;
    }

    const storeUrl = $form.attr('action');
    const $method = $form.find('input[name="_method"]');

    const $preview = $('#productImagePreview');
    const placeholder = $preview.html();

    function showPreview(url) {
        if (url) {
            $preview.empty().append($('<img>', { src: url, alt: '', class: 'size-full object-cover' }));
        } else {
            $preview.html(placeholder);
        }
    }

    $('#productImage').on('change', function () {
        const file = this.files && this.files[0];

        if (file) {
            showPreview(URL.createObjectURL(file));
            $('#removeImage').prop('checked', false);
        }
    });

    $('#addProductButton').on('click', function () {
        $form.trigger('reset');
        showPreview(null);
        $('#removeImageWrap').addClass('hidden').removeClass('flex');
        $form.attr('action', storeUrl);
        $method.prop('disabled', true);
        $('#productModalTitle').text('Add fish product');
        $('#productAvailable').prop('checked', true);
        openModal('productModal');
    });

    $('.edit-product').on('click', function () {
        const product = $(this).data('product');

        $form.trigger('reset');
        $form.attr('action', $(this).data('url'));
        $method.prop('disabled', false);
        $('#productModalTitle').text('Edit fish product');
        $('#productName').val(product.name);
        $('#productCategory').val(product.category);
        $('#productPrice').val(product.price);
        $('#productStock').val(product.stock);
        $('#productMinStock').val(product.min_stock);
        $('#productAvailable').prop('checked', Boolean(product.is_available));
        showPreview(product.image_url);
        $('#removeImageWrap').toggleClass('hidden', !product.image_url).toggleClass('flex', Boolean(product.image_url));
        openModal('productModal');
    });

    function applyFilter() {
        const query = $.trim($('#inventorySearch').val()).toLowerCase();
        const mode = $('#inventoryFilter').val();
        const $rows = $('#inventoryRows tr[data-search]');
        let shown = 0;

        $rows.each(function () {
            const $row = $(this);
            const isLow = $row.attr('data-low') === '1';
            const isAvailable = $row.attr('data-available') === '1';
            const matchesText = $row.attr('data-search').includes(query);

            const matchesMode =
                mode === 'all' ||
                (mode === 'low' && isLow) ||
                (mode === 'available' && isAvailable) ||
                (mode === 'unavailable' && !isAvailable);

            const visible = matchesText && matchesMode;

            $row.toggleClass('hidden', !visible);

            if (visible) {
                shown++;
            }
        });

        $('#inventoryEmpty').toggleClass('hidden', shown > 0);
        $('#inventoryCount').text('Showing ' + shown + ' of ' + $rows.length + ' products');
    }

    $('#inventorySearch').on('input', applyFilter);
    $('#inventoryFilter').on('change', applyFilter);

    applyFilter();
});
