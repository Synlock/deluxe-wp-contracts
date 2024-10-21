document.addEventListener('DOMContentLoaded', function () {
    requestPremiumContent('dpcms-footer', 'footer', '12345');

    var sellerTypeSelect = document.getElementById('dpcms_seller_type');
    var buyerTypeSelect = document.getElementById('dpcms_buyer_type');
    var saleTypeSelect = document.getElementById('dpcms_sale_type');
    var signedBySelect = document.getElementById('dpcms_signed_by');

    if (sellerTypeSelect) {
        toggleCustomOption(sellerTypeSelect, 'dpcms_custom_seller_type');
    }
    if (buyerTypeSelect) {
        toggleCustomOption(buyerTypeSelect, 'dpcms_custom_buyer_type');
    }
    if (saleTypeSelect) {
        toggleCustomOption(saleTypeSelect, 'dpcms_custom_sale_type');
    }
    if (signedBySelect) {
        toggleCustomOption(signedBySelect, 'dpcms_custom_signed_by');
    }
});

function toggleCustomOption(selectElement, customInputId) {
    var customInput = document.getElementById(customInputId);
    if (selectElement.value === 'Other') {
        customInput.style.display = 'block';
    } else {
        customInput.style.display = 'none';
    }
}

function updateContractTypeDropdown() {
    var industrySelect = document.getElementById("dpcms_industry_select");
    var contractTypeSelect = document.getElementById("dpcms_terms_select");
    var selectedIndustry = industrySelect.value;

    contractTypeSelect.innerHTML = "<option value=''>Select a contract type</option>";

    // Make sure to use the localized object name
    if (selectedIndustry && dpcmsAdminAjax.contract_examples[selectedIndustry]) {
        var contracts = dpcmsAdminAjax.contract_examples[selectedIndustry];
        for (var contractType in contracts) {
            if (contracts.hasOwnProperty(contractType)) {
                var option = document.createElement("option");
                option.value = contracts[contractType];
                option.text = ucwords(contractType.replace(/_/g, " "));
                contractTypeSelect.appendChild(option);
            }
        }
    }
}

function populateTerms() {
    var select = document.getElementById("dpcms_terms_select");
    var textarea = document.getElementById("dpcms_terms_and_conditions");
    textarea.value = select.value;
}

function ucwords(str) {
    return str.replace(/\b\w/g, function (l) { return l.toUpperCase(); });
}
