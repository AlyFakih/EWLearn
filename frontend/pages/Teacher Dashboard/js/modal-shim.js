// Minimal shim for the Bootstrap jQuery `.modal('show'|'hide')` API used by
// grades-new.js, so it works against this app's own .modal-overlay/.open
// system without pulling in the full Bootstrap JS bundle (which was never
// actually loaded, so `$(...).modal is not a function` was thrown before).
(function ($) {
    if (!$ || !$.fn || $.fn.modal) return; // don't override a real Bootstrap if it's ever added
    $.fn.modal = function (action) {
        return this.each(function () {
            var $el = $(this);
            if (action === 'hide') {
                $el.removeClass('open').css('display', 'none');
            } else {
                $el.addClass('open').css('display', 'flex');
            }
        });
    };
})(window.jQuery);
