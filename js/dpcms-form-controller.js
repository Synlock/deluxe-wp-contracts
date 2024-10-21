jQuery(document).ready(function ($) {
    if (document.getElementById('dpcms_same_as_billing')) {
        deliveryAddressHandler();
    }

    if (document.getElementById('dpcms_bedrooms')) {
        houseSpecificationsInputLimit();
    }

    if (document.getElementById('dpcms_original_price')) {
        pricingCalculationsHandler();
    }
    reviewButtonHandler($);

    // Initialize custom options for all existing lists
    $('#dpcms-contract-form').each(function () {
        createCustomOptions($, this);
    });
});

function deliveryAddressHandler() {
    document.getElementById('dpcms_same_as_billing').addEventListener('change', function () {
        if (this.checked) {
            document.getElementById('dpcms_delivery_address').value = document.getElementById('dpcms_address').value;
            document.getElementById('dpcms_delivery_city').value = document.getElementById('dpcms_city').value;
            document.getElementById('dpcms_delivery_state').value = document.getElementById('dpcms_state').value;
            document.getElementById('dpcms_delivery_zip').value = document.getElementById('dpcms_zip').value;
            document.getElementById('dpcms_delivery_country').value = document.getElementById('dpcms_country').value;
        } else {
            document.getElementById('dpcms_delivery_address').value = '';
            document.getElementById('dpcms_delivery_city').value = '';
            document.getElementById('dpcms_delivery_state').value = '';
            document.getElementById('dpcms_delivery_zip').value = '';
            document.getElementById('dpcms_delivery_country').value = '';
        }
    });
}

function houseSpecificationsInputLimit() {
    const modelNumber = document.getElementById('dpcms_model_number');
    const bedrooms = document.getElementById('dpcms_bedrooms');
    const bathrooms = document.getElementById('dpcms_bathrooms');
    const homeSqft = document.getElementById('dpcms_home_sqft');
    const deckSqft = document.getElementById('dpcms_deck_sqft');
    const garageSqft = document.getElementById('dpcms_garage_sqft');

    modelNumber.addEventListener('input', function () {
        limitInputCharacters(this, 5);
    });
    bedrooms.addEventListener('input', function () {
        limitInputCharacters(this, 2);
    });
    bathrooms.addEventListener('input', function () {
        limitInputCharacters(this, 2);
    });
    homeSqft.addEventListener('input', function () {
        limitInputCharacters(this, 6);
    });
    deckSqft.addEventListener('input', function () {
        limitInputCharacters(this, 6);
    });
    garageSqft.addEventListener('input', function () {
        limitInputCharacters(this, 6);
    });
}

function limitInputCharacters(inputField, maxChars) {
    inputField.addEventListener('input', function () {
        if (this.value.length > maxChars) {
            this.value = this.value.substring(0, maxChars);
        }
    });
}

function pricingCalculationsHandler() {
    var originalPrice = document.getElementById('dpcms_original_price');
    var freight = document.getElementById('dpcms_freight');
    var customOptionsPrice = document.getElementById('dpcms_custom_options_price');
    var deductions = document.getElementById('dpcms_deductions');
    var initialPayment = document.getElementById('dpcms_initial_payment');

    originalPrice.addEventListener('input', function () {
        dpcmsCalculateRemainingBalance();
        limitDecimalPointsToTwo(this);
        preventNegativeNumbers(this);
    });
    originalPrice.addEventListener('keypress', function (event) {
        preventNonNumericInput(event);
    });
    freight.addEventListener('input', function () {
        dpcmsCalculateRemainingBalance();
        limitDecimalPointsToTwo(this);
        preventNegativeNumbers(this);
    });
    freight.addEventListener('keypress', function (event) {
        preventNonNumericInput(event);
    });
    customOptionsPrice.addEventListener('input', function () {
        dpcmsCalculateRemainingBalance();
        limitDecimalPointsToTwo(this);
        preventNegativeNumbers(this);
    });
    customOptionsPrice.addEventListener('keypress', function (event) {
        preventNonNumericInput(event);
    });
    deductions.addEventListener('input', function () {
        dpcmsCalculateRemainingBalance();
        limitDecimalPointsToTwo(this);
        preventNegativeNumbers(this);
    });
    deductions.addEventListener('keypress', function (event) {
        preventNonNumericInput(event);
    });
    initialPayment.addEventListener('input', function () {
        dpcmsCalculateRemainingBalance();
        limitDecimalPointsToTwo(this);
        preventNegativeNumbers(this);
    });
    initialPayment.addEventListener('keypress', function (event) {
        preventNonNumericInput(event);
    });
}

function dpcmsCalculateRemainingBalance() {
    const originalPrice = parseFloat(document.getElementById('dpcms_original_price').value) || 0;
    const freight = parseFloat(document.getElementById('dpcms_freight').value) || 0;
    const customOptionsPrice = parseFloat(document.getElementById('dpcms_custom_options_price').value) || 0;
    const deductions = parseFloat(document.getElementById('dpcms_deductions').value) || 0;
    const initialPayment = parseFloat(document.getElementById('dpcms_initial_payment').value) || 0;
    const percentage = parseFloat(dpcmsData.percentage) / 100;

    let totalPurchasePrice = originalPrice + customOptionsPrice - deductions + freight;
    let remainingBalanceToStart = ((originalPrice + customOptionsPrice - deductions) * percentage) - initialPayment;
    let totalRemainingBalance = totalPurchasePrice - initialPayment;

    if (totalPurchasePrice <= 0) totalPurchasePrice = 0;
    if (remainingBalanceToStart <= 0) remainingBalanceToStart = 0;
    if (totalRemainingBalance <= 0) totalRemainingBalance = 0;

    document.getElementById('dpcms_total_purchase_price').value = totalPurchasePrice.toFixed(2);
    document.getElementById('dpcms_remaining_balance_start').value = remainingBalanceToStart.toFixed(2);
    document.getElementById('dpcms_total_remaining_balance').value = totalRemainingBalance.toFixed(2);
}

function limitDecimalPointsToTwo(inputElement) {
    let value = inputElement.value;
    let decimalIndex = value.indexOf('.');

    if (decimalIndex !== -1) {
        let integerPart = value.substring(0, decimalIndex);
        let decimalPart = value.substring(decimalIndex + 1, decimalIndex + 3); // Only keep two decimal places
        inputElement.value = integerPart + '.' + decimalPart;
    }
}

function preventNegativeNumbers(inputElement) {
    if (parseFloat(inputElement.value) < 0) {
        inputElement.value = 0;
    }
}

function preventNonNumericInput(event) {
    const charCode = event.charCode;
    if (charCode !== 0 && (charCode < 48 || charCode > 57) && charCode !== 46) {
        event.preventDefault();
    }
}

function reviewButtonHandler($) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    $('#dpcms-review-button').on('click', function () {
        // Validate required fields
        let isValid = true;
        $('#dpcms-contract-form [required]').each(function () {
            if ($(this).hasClass('dpcms-dynamic_fields_list')) {
                if ($(this).find('li').length === 0) {
                    isValid = false;
                    $(this).focus();
                    alert('Please add at least one item to all required lists.');
                    return false;
                }
            } else if ($(this).is(':input')) {
                if ($(this).val().trim() === '') {
                    isValid = false;
                    $(this).focus();
                    alert('Please fill out all required fields.');
                    return false;
                }
            }
        });

        if (!isValid) return;

        $('#dpcms-contract-form [type="email"]').each(function () {
            const emailValue = $(this).val().trim();
            if (emailValue !== '' && !emailRegex.test(emailValue)) {
                isValid = false;
                $(this).focus();
                alert('Please enter a valid email address.');
                return false;
            }
        });

        if (!isValid) return;

        // Organize review content
        let reviewContent = '';
        $('#dpcms-contract-form h3').each(function () {
            const sectionHeader = $(this).text();
            let sectionContent = '';
            let hasValidInput = false;

            // Special handling for "Other Provisions"
            if (sectionHeader === 'Other Provisions') {
                const otherProvisionsInput = $(this).next('label').next('textarea');
                if (otherProvisionsInput.val().trim() !== '') {
                    sectionContent += `<p>${otherProvisionsInput.val()}</p>`;
                    hasValidInput = true;
                }
            } else {
                let nextElement = $(this).next();
                while (nextElement.length && !nextElement.is('h3')) {
                    if (nextElement.is('label')) {
                        const label = nextElement.text().replace(' *', '');
                        const input = nextElement.next();

                        // Handle different input types
                        if (input.is(':checkbox')) {
                            sectionContent += `<p>${label}: ${input.is(':checked') ? 'Yes' : 'No'}</p>`;
                            hasValidInput = true;
                        } else if (input.is('input[type="number"]')) {
                            const value = input.val();
                            const step = input.attr('step');
                            if (value !== undefined && value !== null && value.trim() !== '') {
                                if (step && parseFloat(step) !== 1) {
                                    sectionContent += `<p>${label} ${parseFloat(value).toFixed(2)}</p>`;
                                } else {
                                    sectionContent += `<p>${label} ${parseInt(value, 10)}</p>`;
                                }
                                hasValidInput = true;
                            }
                        } else if (input.is('input[type="tel"], input[type="email"], input[type="text"]')) {
                            const value = input.val();
                            if (value !== undefined && value !== null && value.trim() !== '') {
                                sectionContent += `<p>${label} ${value}</p>`;
                                hasValidInput = true;
                            }
                            if (input.is('[required]') && (value === undefined || value === null || value.trim() === '')) {
                                isValid = false;
                                input.focus();
                                alert('Please fill out all required fields.');
                                return false;
                            }
                        } else if (input.is('textarea')) {
                            const value = input.val();
                            if (value !== undefined && value !== null && value.trim() !== '') {
                                sectionContent += `<p>${label} ${value}</p>`;
                                hasValidInput = true;
                            }
                        }
                    } else if (nextElement.is('.dpcms-checkbox-container')) {
                        const label = nextElement.find('label').text();
                        const input = nextElement.find('input');
                        sectionContent += `<p>${label} ${input.is(':checked') ? 'Yes' : 'No'}</p>`;
                        hasValidInput = true;
                    } else if (nextElement.is('.dpcms-label-input')) {
                        const val = nextElement.val();
                        if (val !== undefined && val !== null) {
                            const label = val.replace(':', '').trim();
                            const input = nextElement.next('input');
                            const value = input.val();
                            if (value !== undefined && value !== null && value.trim() !== '') {
                                sectionContent += `<p>${label}: ${value}</p>`;
                                hasValidInput = true;
                            }
                        }
                    } else if (nextElement.is('.dpcms-dynamic_fields_list')) {
                        const listId = nextElement.attr('id');
                        const listNameInput = nextElement.prevAll('input.dpcms-label-input:first');
                        const listName = listNameInput.val();
                        sectionContent += `<p><strong>${listName}</strong></p>`;

                        let customOptions = '';
                        let optionIndex = 1;
                        $(`#${listId} .dpcms-custom-option-li`).each(function () {
                            const optionLabel = $(this).find('input[name*="[label]"]').val() || '';
                            const requiresNumber = $(this).find('input[name*="[requires_text]"]').is(':checked');
                            const isPrice = $(this).find('input[name*="[is_price]"]').is(':checked');
                            const optionNumber = $(this).find('input[name*="[value]"]').val() || '';

                            if (optionLabel.trim() !== '') {
                                if (!isPrice) {
                                    customOptions += `<p>${optionIndex}. ${optionLabel}${requiresNumber ? ': ' + optionNumber : ''}</p>`;
                                } else {
                                    customOptions += `<p>${optionIndex}. ${optionLabel}${requiresNumber ? ': $' + optionNumber : ''}</p>`;
                                }
                                optionIndex++;
                                hasValidInput = true;
                            }
                        });
                        sectionContent += customOptions;
                    } else if (nextElement.is('.dpcms-price-input-wrapper')) {
                        const label = nextElement.prev('label').text();
                        const input = nextElement.find('input');
                        const value = parseFloat(input.val()).toFixed(2) || '';

                        if (value !== '') {
                            sectionContent += `<p>${label} $${value}</p>`;
                            hasValidInput = true;
                        }
                    }
                    nextElement = nextElement.next();
                }
            }

            // Add section content if there is valid input
            if (hasValidInput) {
                reviewContent += `<h4>${sectionHeader}</h4>` + sectionContent;
            }
        });

        $('#dpcms-review-content').html(reviewContent);
        $('#dpcms-review-section').show();
        $('#dpcms-contract-form').hide();
    });

    $('#dpcms-edit-button').on('click', function () {
        $('#dpcms-review-section').hide();
        $('#dpcms-contract-form').show();
    });

    $('#dpcms-send-email-button').on('click', function () {
        disableButton($, this);
        processForm($, 'send_email');
    });

    $('#dpcms-download-no-email-button').on('click', function () {
        disableButton($, this);
        processForm($, 'direct_download');
    });
}

function disableButton($, button) {
    $(button).prop('disabled', true).addClass('dpcms-loading');
}

function enableButton($, button) {
    $(button).prop('disabled', false).removeClass('dpcms-loading');
}

function processForm($, actionType) {
    $('#dpcms-contract-form').off('submit');

    $('#dpcms-contract-form').on('submit', function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        console.log(formData);

        $.post(dpcmsData.ajax_url, {
            action: 'dpcms_process_form',
            action_type: actionType,
            form_data: $('#dpcms-contract-form').serialize(),
            dpcms_contract_form_nonce: $('#dpcms_contract_form_nonce').val()
        }, function (response) {
            if (response.success) {
                $('.dpcms-contract-form').empty();

                $('.dpcms-contract-form').append('<div class="ajax-response-message">' + response.data.message + '</div>');
            } else {
                $(this).prop('disabled', false);
                alert(response.data);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            $(this).prop('disabled', false);
            var errorMessage = 'An error occurred while processing your request. Please try again.';
            try {
                var responseJson = JSON.parse(jqXHR.responseText);
                if (responseJson.data) {
                    errorMessage = responseJson.data;
                }
            } catch (e) {
                errorMessage += '<br>Details: ' + textStatus + ' - ' + errorThrown;
            }
            alert('An error occurred while processing your request.' + errorMessage);
        }).always(function () {
            enableButton($, '#dpcms-send-email-button');
            enableButton($, '#dpcms-download-no-email-button');
        });
    });

    $('#dpcms-contract-form').submit();
}

function createCustomOptions($, container) {
    let fieldIndexCounter = {};

    function getNextFieldIndex(index) {
        if (!fieldIndexCounter[index]) {
            fieldIndexCounter[index] = 0;
        }
        return fieldIndexCounter[index]++;
    }

    function addDynamicField(listId, index) {
        const fieldIndex = getNextFieldIndex(index);
        const list = $('#' + listId);
        const newItem = $(`
            <li>
                <div class="dpcms-custom-option-li">
                    <input type="text" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][label]" placeholder="Option Name" required>
                    <div class="dpcms-checkbox-wrapper">
                        <label for="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][requires_text]">Requires Text</label>
                        <input type="checkbox" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][requires_text]" class="dpcms-custom-option-requires-text">
                    </div>
                    <div class="dpcms-checkbox-wrapper" style="display:none">
                        <label for="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][is_price]">Is Price</label>
                        <input type="checkbox" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][is_price]" class="dpcms-custom-option-is-price">
                    </div>
                    <input type="text" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][value]" placeholder="Enter text or number here" class="dpcms-custom-option-price" style="display:none;">
                    <button type="button" class="dpcms-remove-custom-option">Remove</button>
                </div>
            </li>
        `);

        attachOptionEvents(newItem);
        list.append(newItem);
    }

    function attachOptionEvents(optionItem) {
        optionItem.find('.dpcms-custom-option-requires-text').on('change', function () {
            const isPriceWrapper = optionItem.find('.dpcms-checkbox-wrapper').eq(1);
            const textInput = optionItem.find('.dpcms-custom-option-price');
            if (this.checked) {
                isPriceWrapper.show();
                textInput.show().prop('required', true);
            } else {
                isPriceWrapper.hide();
                textInput.val('');
                textInput.hide().prop('required', false);
            }
        });

        optionItem.find('.dpcms-remove-custom-option').on('click', function () {
            $(this).closest('li').remove();
        });
    }

    $(container).on('click', '.dpcms-add-dynamic-field', function () {
        const listId = $(this).data('list-id');
        const index = $(this).data('index');
        console.log("listId: ", listId);
        console.log("index: ", index);
        addDynamicField(listId, index);
    });

    $(container).find('.dpcms-dynamic_fields_list').each(function () {
        const listId = $(this).attr('id');
        const index = listId.split('_').pop();
        fieldIndexCounter[index] = $(this).children('li').length;
    });

    // Attach events to existing dynamic fields
    $(container).find('.dpcms-dynamic_fields_list > li').each(function () {
        attachOptionEvents($(this));
    });
}
