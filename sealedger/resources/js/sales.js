import $ from 'jquery';
import { openModal } from './modal';
import { itemRows } from './item-rows';

$(function () {
    const $form = $('#saleForm');

    if (!$form.length) {
        return;
    }

    const storeUrl = $form.attr('action');
    const $method = $form.find('input[name="_method"]');
    const rows = itemRows('#saleItems', '#saleItemTemplate', '#addSaleItem');

    $('#newSaleButton').on('click', function () {
        $form.trigger('reset');
        $form.attr('action', storeUrl);
        $method.prop('disabled', true);
        $('#saleModalTitle').text('Record daily sales');
        $('#saleDate').val($('#saleDate').data('default'));
        rows.clear();
        openModal('saleModal');
    });

    $('.edit-sale').on('click', function () {
        const sale = $(this).data('sale');

        $form.attr('action', $(this).data('url'));
        $method.prop('disabled', false);
        $('#saleModalTitle').text('Edit sales record');
        $('#saleDate').val(sale.sale_date);
        $('#saleAmount').val(sale.amount);
        $('#saleNotes').val(sale.notes);
        rows.fill(sale.items);
        openModal('saleModal');
    });

    $('#salesFrom, #salesTo').on('change', function () {
        $('#salesFilterForm').trigger('submit');
    });
});
