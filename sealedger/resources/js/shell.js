import $ from 'jquery';
import { closeModal } from './modal';

$(function () {
    const $sidebar = $('#sidebar');
    const $overlay = $('#sidebarOverlay');
    const $toast = $('#appToast');

    $('#mobileMenuButton').on('click', function () {
        $sidebar.toggleClass('-translate-x-full');
        $overlay.toggleClass('hidden');
    });

    $overlay.on('click', function () {
        $sidebar.addClass('-translate-x-full');
        $overlay.addClass('hidden');
    });

    $(document).on('click', '[data-close-modal]', function () {
        closeModal($(this).closest('.modal-root'));
    });

    $('.modal-root').on('mousedown', function (event) {
        if (event.target === this) {
            closeModal($(this));
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal($('.modal-root'));
        }
    });

    $('form[data-confirm]').on('submit', function (event) {
        if (!window.confirm($(this).data('confirm'))) {
            event.preventDefault();
        }
    });

    if ($toast.length && $.trim($toast.text()) !== '') {
        $toast.removeClass('translate-y-3 opacity-0').addClass('translate-y-0 opacity-100');

        setTimeout(function () {
            $toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-3 opacity-0');
        }, 3000);
    }
});
