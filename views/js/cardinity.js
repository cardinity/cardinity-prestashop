/**
 * @author      Cardinity
 * @link        https://cardinity.com
 * @license     The MIT License (MIT)
 */
$(document).ready(function () {

    $('.card-pan').payment('formatCardNumber');
    $('.cvc').payment('formatCardCVC');

    $.fn.toggleInputError = function(erred) {
        this.closest('.controls').toggleClass('has-error', erred);
        return this;
    };

    $('form').submit(function(e) {

        var cardType = $.payment.cardType($('.card-pan').val());

        $('.card-pan').toggleInputError(!$.payment.validateCardNumber($('.card-pan').val()));
        $('.cvc').toggleInputError(!$.payment.validateCardCVC($('.cvc').val(), cardType));

        if (!$('.has-error').length) {
            return;
        }

        e.preventDefault();
    });

    if (!!$.prototype.fancybox) {
        $("a.iframe").fancybox({
            'type':   "iframe",
            'width':  600,
            'height': 430
        });
    }
});