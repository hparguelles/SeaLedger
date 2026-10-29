import $ from 'jquery';
import { openModal } from './modal';
import { itemRows } from './item-rows';

$(function () {
    const $form = $('#planForm');

    if (!$form.length) {
        return;
    }

    const storeUrl = $form.attr('action');
    const $method = $form.find('input[name="_method"]');
    const rows = itemRows('#planItems', '#planItemTemplate', '#addPlanItem');

    $('#addPlanButton').on('click', function () {
        $form.trigger('reset');
        $form.attr('action', storeUrl);
        $method.prop('disabled', true);
        $('#planModalTitle').text('Create restocking plan');
        $('#planDate').val($('#planDate').data('default'));
        $('#planTime').val('04:30');
        rows.clear();
        rows.add();
        openModal('planModal');
    });

    $('.edit-plan').on('click', function () {
        const plan = $(this).data('plan');

        $form.attr('action', $(this).data('url'));
        $method.prop('disabled', false);
        $('#planModalTitle').text('Edit restocking plan');
        $('#planTitle').val(plan.title);
        $('#planDate').val(plan.plan_date);
        $('#planTime').val(plan.plan_time);
        $('#planNotes').val(plan.notes);

        if (plan.items.length) {
            rows.fill(plan.items);
        } else {
            rows.clear();
            rows.add();
        }

        openModal('planModal');
    });

    $('#planFilter').on('change', function () {
        const status = $(this).val();
        const $cards = $('.plan-card');

        $cards.each(function () {
            $(this).toggleClass('hidden', status !== 'all' && $(this).attr('data-status') !== status);
        });

        $('#planEmpty').toggleClass('hidden', $cards.not('.hidden').length > 0);
    });
});
