import $ from 'jquery';
import { openModal } from './modal';
import { itemRows } from './item-rows';

function localNow() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
}

$(function () {
    const $form = $('#deliveryForm');

    if (!$form.length) {
        return;
    }

    const storeUrl = $form.attr('action');
    const $method = $form.find('input[name="_method"]');
    const rows = itemRows('#deliveryItems', '#deliveryItemTemplate', '#addDeliveryItem');

    $('#addDeliveryButton').on('click', function () {
        $form.trigger('reset');
        $form.attr('action', storeUrl);
        $method.prop('disabled', true);
        $('#deliveryModalTitle').text('New delivery order');
        rows.clear();
        rows.add();
        openModal('deliveryModal');
    });

    $('.edit-delivery').on('click', function () {
        const delivery = $(this).data('delivery');

        $form.trigger('reset');
        $form.attr('action', $(this).data('url'));
        $method.prop('disabled', false);
        $('#deliveryModalTitle').text('Edit delivery order');
        $('#deliveryCustomer').val(delivery.customer_name);
        $('#deliveryContact').val(delivery.contact);
        $('#deliveryAddress').val(delivery.address);
        $('#deliveryEstimated').val(delivery.estimated_at);
        $('#deliveryAmount').val(delivery.amount);
        $('#deliveryNotes').val(delivery.notes);

        if (delivery.items.length) {
            rows.fill(delivery.items);
        } else {
            rows.clear();
            rows.add();
        }

        openModal('deliveryModal');
    });

    // Mark delivered
    $('.deliver-order').on('click', function () {
        $('#deliverForm').trigger('reset').attr('action', $(this).data('url'));
        $('#deliverCustomer').text($(this).data('customer'));
        $('#deliveredAt').val(localNow());
        openModal('deliverModal');
    });

    // View proof
    $('.view-proof').on('click', function () {
        const image = $(this).data('image');
        const note = $(this).data('note');

        $('#proofCustomer').text($(this).data('customer'));
        $('#proofImg').attr('src', image || '').toggleClass('hidden', !image);
        $('#proofText').text(note || '').toggleClass('hidden', !note);
        openModal('proofModal');
    });

    // Search + filter
    function applyFilter() {
        const query = $.trim($('#deliverySearch').val()).toLowerCase();
        const mode = $('#deliveryFilter').val();
        const $rows = $('#deliveryRows tr[data-search]');
        let shown = 0;

        $rows.each(function () {
            const $row = $(this);
            const matchesText = $row.attr('data-search').includes(query);
            const matchesMode =
                mode === 'all' ||
                (mode === 'open' && $row.attr('data-open') === '1') ||
                $row.attr('data-status') === mode;
            const visible = matchesText && matchesMode;

            $row.toggleClass('hidden', !visible);

            if (visible) {
                shown++;
            }
        });

        $('#deliveryEmpty').toggleClass('hidden', shown > 0);
        $('#deliveryCount').text('Showing ' + shown + ' of ' + $rows.length + ' orders');
    }

    $('#deliverySearch').on('input', applyFilter);
    $('#deliveryFilter').on('change', applyFilter);
    applyFilter();
});
