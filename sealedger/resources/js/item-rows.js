import $ from 'jquery';

export function itemRows(containerSelector, templateSelector, addSelector) {
    const $container = $(containerSelector);
    let index = 0;

    function add(productId = '', quantity = '') {
        const html = $(templateSelector).html().replaceAll('__index__', index++);
        const $row = $(html);

        $row.find('select').val(productId);
        $row.find('input[type="number"]').val(quantity);
        $container.append($row);
    }

    $(addSelector).on('click', function () {
        add();
    });

    $container.on('click', '.remove-item', function () {
        $(this).closest('.item-row').remove();
    });

    return {
        add,
        clear() {
            $container.empty();
        },
        fill(items) {
            $container.empty();
            items.forEach(function (item) {
                add(item.product_id, item.quantity);
            });
        },
    };
}
