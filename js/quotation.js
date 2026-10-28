(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function number(value) {
        var parsed = parseFloat(value);
        return isNaN(parsed) ? 0 : parsed;
    }

    function round(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    ready(function () {
        var form = document.getElementById('quotationForm');
        if (!form) {
            return;
        }

        var tbody = document.getElementById('quotationItemsBody');
        var template = document.getElementById('quotationItemTemplate');
        var addButton = document.getElementById('addItemRow');
        var errorBox = document.getElementById('quotationFormErrors');
        var symbol = form.getAttribute('data-currency-symbol') || '\u20B9';

        if (!tbody || !template) {
            return;
        }

        function money(value) {
            return symbol + ' ' + round(value).toFixed(2);
        }

        function setText(id, value) {
            var element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        function rows() {
            return Array.prototype.slice.call(tbody.querySelectorAll('.item-row'));
        }

        function renumberRows() {
            rows().forEach(function (row, index) {
                var indexCell = row.querySelector('.row-index');
                if (indexCell) {
                    indexCell.textContent = index + 1;
                }
            });
        }

        function calculateRow(row) {
            var quantityInput = row.querySelector('.item-qty');
            var priceInput = row.querySelector('.item-price');
            var discountInput = row.querySelector('.item-discount');
            var taxInput = row.querySelector('.item-tax');
            var lineTotalCell = row.querySelector('.line-total');

            var quantity = number(quantityInput && quantityInput.value);
            var price = number(priceInput && priceInput.value);
            var discount = number(discountInput && discountInput.value);
            var taxPercent = number(taxInput && taxInput.value);

            var gross = round(quantity * price);
            var appliedDiscount = Math.min(Math.max(discount, 0), gross);
            var lineTotal = round(Math.max(0, gross - appliedDiscount));
            var taxAmount = round(lineTotal * Math.max(0, taxPercent) / 100);

            if (lineTotalCell) {
                lineTotalCell.textContent = money(lineTotal);
            }

            return {
                gross: gross,
                discount: appliedDiscount,
                tax: taxAmount
            };
        }

        function calculateTotals() {
            var subtotal = 0;
            var discount = 0;
            var tax = 0;

            rows().forEach(function (row) {
                var totals = calculateRow(row);
                subtotal += totals.gross;
                discount += totals.discount;
                tax += totals.tax;
            });

            subtotal = round(subtotal);
            discount = round(discount);
            tax = round(tax);
            var grandTotal = round(subtotal - discount + tax);

            setText('summarySubtotal', money(subtotal));
            setText('summaryDiscount', money(discount));
            setText('summaryTax', money(tax));
            setText('summaryGrandTotal', money(grandTotal));
        }

        function addRow(values) {
            var fragment = template.content.cloneNode(true);
            var row = fragment.querySelector('.item-row');

            if (values) {
                ['product_name', 'product_description', 'quantity', 'unit_price', 'discount', 'tax_percent'].forEach(function (field) {
                    var input = row.querySelector('[data-field="' + field + '"]');
                    if (input && values[field] !== undefined && values[field] !== null) {
                        input.value = values[field];
                    }
                });
            }

            tbody.appendChild(fragment);
            renumberRows();
            calculateTotals();
        }

        function resetRow(row) {
            row.querySelectorAll('input, textarea').forEach(function (input) {
                if (input.classList.contains('item-qty')) {
                    input.value = '1';
                } else if (input.classList.contains('item-discount') || input.classList.contains('item-tax')) {
                    input.value = '0';
                } else {
                    input.value = '';
                }
            });
        }

        function showErrors(errors) {
            if (!errorBox) {
                return;
            }
            if (!errors.length) {
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';
                return;
            }

            var list = document.createElement('ul');
            list.className = 'mb-0';
            errors.forEach(function (message) {
                var item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });

            errorBox.classList.remove('d-none');
            errorBox.innerHTML = '';
            errorBox.appendChild(list);
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function validateForm() {
            var errors = [];
            var customerName = form.querySelector('[name="customer_name"]');
            var customerPhone = form.querySelector('[name="customer_phone"]');
            var customerEmail = form.querySelector('[name="customer_email"]');
            var quotationDate = form.querySelector('[name="quotation_date"]');
            var validUntil = form.querySelector('[name="valid_until"]');

            if (!customerName || customerName.value.trim() === '') {
                errors.push('Customer name is required.');
            }

            if (customerPhone && customerPhone.value.trim() !== ''
                && !/^[0-9+\-\s().]{6,20}$/.test(customerPhone.value.trim())) {
                errors.push('Customer phone number is not valid.');
            }

            if (customerEmail && customerEmail.value.trim() !== ''
                && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.value.trim())) {
                errors.push('Customer email address is not valid.');
            }

            if (!quotationDate || !quotationDate.value) {
                errors.push('A valid quotation date is required.');
            }

            if (!validUntil || !validUntil.value) {
                errors.push('A valid "valid until" date is required.');
            } else if (quotationDate && quotationDate.value && validUntil.value < quotationDate.value) {
                errors.push('"Valid until" cannot be earlier than the quotation date.');
            }

            var hasItem = false;
            var itemRows = rows();

            itemRows.forEach(function (row) {
                var name = row.querySelector('.item-name');
                var description = row.querySelector('.item-description');
                var quantity = row.querySelector('.item-qty');
                var price = row.querySelector('.item-price');
                var discount = row.querySelector('.item-discount');
                var tax = row.querySelector('.item-tax');

                var nameValue = name ? name.value.trim() : '';
                var descriptionValue = description ? description.value.trim() : '';
                var quantityValue = number(quantity && quantity.value);
                var priceValue = price ? price.value.trim() : '';
                var priceNumber = number(priceValue);
                var discountValue = number(discount && discount.value);
                var taxValue = number(tax && tax.value);

                if (nameValue === '' && descriptionValue === '' && priceValue === '' && quantityValue === 1) {
                    return;
                }

                hasItem = true;

                if (nameValue === '') {
                    errors.push('Every item row needs a product or service name.');
                }
                if (quantityValue <= 0) {
                    errors.push('Quantity must be greater than zero for all items.');
                }
                if (priceValue === '' || priceNumber < 0) {
                    errors.push('Unit price must be zero or greater for all items.');
                }
                if (discountValue < 0) {
                    errors.push('Discount must be zero or greater for all items.');
                }
                if (discountValue > round(quantityValue * priceNumber)) {
                    errors.push('Discount cannot be greater than the item amount.');
                }
                if (taxValue < 0 || taxValue > 100) {
                    errors.push('Tax percentage must be between 0 and 100.');
                }
            });

            if (!hasItem) {
                errors.push('Add at least one product or service.');
            }

            // Remove duplicate messages so the alert stays readable.
            errors = errors.filter(function (message, index) {
                return errors.indexOf(message) === index;
            });

            showErrors(errors);
            return errors.length === 0;
        }

        tbody.addEventListener('input', function (event) {
            if (event.target.matches('.item-qty, .item-price, .item-discount, .item-tax')) {
                calculateTotals();
            }
        });

        tbody.addEventListener('click', function (event) {
            var removeButton = event.target.closest('.removeItemRow');
            if (!removeButton) {
                return;
            }

            var row = removeButton.closest('.item-row');
            if (!row) {
                return;
            }

            if (rows().length <= 1) {
                resetRow(row);
            } else {
                row.remove();
            }

            renumberRows();
            calculateTotals();
        });

        if (addButton) {
            addButton.addEventListener('click', function () {
                addRow();
            });
        }

        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
            }
        });

        if (rows().length === 0) {
            addRow();
        }

        renumberRows();
        calculateTotals();
    });
}());
