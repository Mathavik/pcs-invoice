$(function () {
    bindInvoiceEvents();
    initializeInvoiceState();
});

function bindInvoiceEvents() {
    $('#currency').on('change', onInvoiceControlChange);
    $(document).on('change', 'input[name="gst_type"]', onInvoiceControlChange);
    $(document).on('input change blur', "[id^='price_'], [id^='taxableValue_']", calculateTotal);
    $(document).on('click', '#addRows, #removeRows', function () {
        setTimeout(function () {
            calculateTotal();
            toggleGSTVisibility();
        }, 100);
    });
}

function onInvoiceControlChange() {
    toggleGSTVisibility();
    calculateTotal();
}

function initializeInvoiceState() {
    if ($('#currency').length === 0) {
        return;
    }

    if ($('#currency').val().trim().toUpperCase() === 'USD') {
        $('#without_gst').prop('checked', true);
    } else if (!$('input[name="gst_type"]:checked').length) {
        $('#with_gst').prop('checked', true);
    }

    toggleGSTVisibility();
    calculateTotal();
}

function toggleGSTVisibility() {
    const currency = ($('#currency').val() || '').trim().toUpperCase();
    const gstType = $('input[name="gst_type"]:checked').val() || 'with_gst';
    const isInr = currency === 'INR';
    const showGstControls = isInr;
    const showGstFields = isInr && gstType === 'with_gst';
    const symbol = currency === 'USD' ? '$' : '₹';

    $('.currency-symbol').text(symbol);

    if (showGstControls) {
        $('#gst-toggle-wrapper').removeClass('d-none').show();
    } else {
        $('#gst-toggle-wrapper').addClass('d-none').hide();
        $('#without_gst').prop('checked', true);
    }

    if (showGstFields) {
        $('#gst-fields-wrapper').removeClass('d-none').show();
    } else {
        $('#gst-fields-wrapper').addClass('d-none').hide();
        $('#cgstTax, #sgstTax, #cgstAmount, #sgstAmount').val(0);
    }
}

function calculateTotal() {
    let subtotal = 0;

    $("[id^='price_']").each(function () {
        const id = $(this).attr('id').split('_')[1] || '';
        const price = parseFloat($(this).val()) || 0;
        const taxableInput = $('#taxableValue_' + id);
        let taxableValue = parseFloat(taxableInput.val()) || 0;

        if (taxableValue <= 0 && price > 0) {
            taxableValue = price;
        }

        taxableInput.val(taxableValue.toFixed(2));
        subtotal += taxableValue;
    });

    $('#rate').val(subtotal.toFixed(2));

    const currency = ($('#currency').val() || '').trim().toUpperCase();
    const gstType = $('input[name="gst_type"]:checked').val() || 'with_gst';

    if (currency === 'INR' && gstType === 'with_gst') {
        const cgstRate = 9;
        const sgstRate = 9;
        const cgstAmount = subtotal * cgstRate / 100;
        const sgstAmount = subtotal * sgstRate / 100;

        $('#cgstTax').val(cgstRate);
        $('#sgstTax').val(sgstRate);
        $('#cgstAmount').val(cgstAmount.toFixed(2));
        $('#sgstAmount').val(sgstAmount.toFixed(2));
        $('#total').val((subtotal + cgstAmount + sgstAmount).toFixed(2));
    } else {
        $('#cgstTax').val(0);
        $('#sgstTax').val(0);
        $('#cgstAmount').val('0.00');
        $('#sgstAmount').val('0.00');
        $('#total').val(subtotal.toFixed(2));
    }
}

