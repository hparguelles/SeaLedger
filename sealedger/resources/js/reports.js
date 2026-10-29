import $ from 'jquery';

$(function () {
    $('#printReport').on('click', function () {
        window.print();
    });
});
