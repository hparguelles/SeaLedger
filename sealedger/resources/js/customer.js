import $ from 'jquery';

$(function () {
    const $viewer = $('#photoViewer');

    if ($viewer.length) {
        const closeViewer = function () {
            $viewer.removeClass('flex').addClass('hidden');
        };

        $('.fish-photo').on('click', function () {
            $('#photoViewerImg').attr({ src: $(this).data('full'), alt: $(this).data('title') });
            $('#photoViewerTitle').text($(this).data('title'));
            $viewer.removeClass('hidden').addClass('flex');
        });

        $viewer.on('click', function (event) {
            if (event.target === this || $(event.target).closest('#photoViewerClose').length) {
                closeViewer();
            }
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeViewer();
            }
        });
    }

    const $search = $('#fishSearch');

    if (!$search.length) {
        return;
    }

    let category = 'all';

    function applyFilter() {
        const query = $.trim($search.val()).toLowerCase();
        const $cards = $('.fish-card');

        $cards.each(function () {
            const $card = $(this);
            const matchesText = $card.attr('data-search').includes(query);
            const matchesCategory = category === 'all' || $card.attr('data-category') === category;

            $card.toggleClass('hidden', !(matchesText && matchesCategory));
        });

        $('#fishEmpty').toggleClass('hidden', $cards.not('.hidden').length > 0);
    }

    $search.on('input', applyFilter);

    $('.category-chip').on('click', function () {
        category = $(this).attr('data-category');

        $('.category-chip')
            .removeClass('bg-brand text-white')
            .addClass('bg-white text-ink');

        $(this).removeClass('bg-white text-ink').addClass('bg-brand text-white');

        applyFilter();
    });
});
