import $ from 'jquery';

export function openModal(id) {
    $('#' + id).removeClass('hidden').addClass('flex');
}

export function closeModal($modal) {
    $modal.removeClass('flex').addClass('hidden');
}
