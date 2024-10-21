jQuery(document).ready(function ($) {
    // Toggle sections
    sectionDropdownHandler($);
    licenseKeyActivationHandler($);

    // Handle AJAX for assigning rep roles
    repRolesAjaxHandler($);
    assignRepRolesAjaxHandler($);
    repRolesTableHandler();
    repRolesTableSearchAddListeners();
    userSearchDropdown();

    contractsTableAddListenerHandler();
    addScrollButtonEventListeners();

    addListToContract($);

    noPermissionDenial();
});

function sectionDropdownHandler($) {
    $('.dpcms-section-title').on('click', function () {
        $(this).toggleClass('open').next('.dpcms-section-content').slideToggle('fast');
    });

    // Start sections in open state
    $('.dpcms-section-title').addClass('open');
    $('.dpcms-section-content').show();
}

function repRolesAjaxHandler($) {
    populateCapabilities('#dpcms-add-capability-container', 'add');
    populateCapabilities('#dpcms-existing-capability-container', 'existing');
    roleDropdownHandler($);
    addNewRoleButtonHandler();
    updateRoleButtonHandler();
    removeRoleButtonHandler();

    function addNewRoleButtonHandler() {
        $('#dpcms-add-new-role').click(function (event) {
            event.preventDefault();

            let newRoleName = $('#dpcms-new-role-name').val();
            if (!newRoleName) {
                alert('Please enter a name for the new role.');
                return;
            }

            if (!confirm('Are you sure you want to add the role "' + newRoleName + '"?')) {
                return;
            }

            let capabilities = [];
            $('#dpcms-add-capability-container .dpcms-capability-checkbox:checked').each(function () {
                capabilities.push($(this).val());
            });

            $.post(dpcmsAdminAjax.ajax_url, {
                action: 'dpcms_add_new_role_ajax',
                role_name: newRoleName,
                capabilities: capabilities,
                dpcms_role_actions_nonce: dpcmsAdminAjax.role_actions_nonce,
            }, function (response) {
                if (response.success) {
                    let newRoleName = response.data.role_name;
                    if ($('#dpcms-role-dropdown option[value="' + newRoleName + '"]').length === 0) {
                        $('#dpcms-role-dropdown').append('<option value="' + newRoleName + '">' + newRoleName + '</option>');
                    }

                    // Clear input fields
                    $('#dpcms-new-role-name').val('');
                    $('.dpcms-capability-checkbox').prop('checked', false);

                    // Update roles data
                    dpcmsAdminAjax.plugin_roles[newRoleName] = {
                        capabilities: capabilities.reduce((acc, cap) => {
                            acc[cap] = true;
                            return acc;
                        }, {})
                    };
                    alert('New role was added successfully.');
                    location.reload();
                } else {
                    alert('Failed to add role. ' + response.data.message);
                }
            });
        });
    }

    function updateRoleButtonHandler() {
        $('#dpcms-update-role').click(function (event) {
            event.preventDefault();

            let selectedRole = $('#dpcms-role-dropdown').val();
            if (!selectedRole) {
                alert('Please select a role to update.');
                return;
            }

            if (!confirm('Are you sure you want to update the role "' + selectedRole + '"?')) {
                return;
            }

            let capabilities = [];
            $('#dpcms-existing-capability-container .dpcms-capability-checkbox:checked').each(function () {
                capabilities.push($(this).val());
            });

            $.post(dpcmsAdminAjax.ajax_url, {
                action: 'dpcms_update_role_ajax',
                role_name: selectedRole,
                capabilities: capabilities,
                dpcms_role_actions_nonce: dpcmsAdminAjax.role_actions_nonce,
            }, function (response) {
                if (response.success) {
                    // Update roles data
                    dpcmsAdminAjax.plugin_roles[selectedRole] = {
                        capabilities: capabilities.reduce((acc, cap) => {
                            acc[cap] = true;
                            return acc;
                        }, {})
                    };

                    alert('Role updated successfully.');
                } else {
                    alert('Failed to update role. ' + response.data.message);
                }
            });
        });
    }

    function removeRoleButtonHandler() {
        $('#dpcms-remove-role').click(function (event) {
            event.preventDefault();

            let selectedRole = $('#dpcms-role-dropdown').val();
            if (!selectedRole) {
                alert('Please select a role to remove.');
                return;
            }

            if (!confirm('Are you sure you want to remove the role "' + selectedRole + '"?')) {
                return;
            }

            $.post(dpcmsAdminAjax.ajax_url, {
                action: 'dpcms_delete_role_ajax',
                role_name: selectedRole,
                dpcms_role_actions_nonce: dpcmsAdminAjax.role_actions_nonce,
            }, function (response) {
                console.log('Remove Role Response:', response); // Debugging line
                if (response.success) {
                    // Remove the role from the dropdown
                    $('#dpcms-role-dropdown option[value="' + selectedRole + '"]').remove();
                    // Clear checkboxes
                    $('.dpcms-capability-checkbox').prop('checked', false);
                    alert('Role removed successfully.');
                    location.reload();
                } else {
                    alert('Failed to remove role. ' + response.data.message);
                }
            });
        });
    }

    function roleDropdownHandler($) {
        $('#dpcms-role-dropdown').change(function () {
            let selectedRole = $(this).val();
            let roles = dpcmsAdminAjax.plugin_roles;

            if (selectedRole) {
                let roleInfo = roles[selectedRole];

                // Check the boxes for the capabilities of the selected role
                $('#dpcms-existing-capability-container .dpcms-capability-checkbox').each(function () {
                    let capability = $(this).val();
                    let capabilityId = $(this).attr('id');
                    if (roleInfo && roleInfo.capabilities && roleInfo.capabilities[capability]) {
                        $(this).prop('checked', true);
                        $('label[for="' + capabilityId + '"]').css("font-weight", "bold");
                    } else {
                        $(this).prop('checked', false);
                        $('label[for="' + capabilityId + '"]').css("font-weight", "normal");
                    }
                });
            } else {
                $('.dpcms-capability-checkbox').prop('checked', false);
                $('label[for="' + capabilityId + '"]').css("font-weight", "normal");
            }
        });
        populateRoleDropdown($, 'dpcms-role-dropdown');
    }

    // Initial population of the checkboxes
    function populateCapabilities(elementId, sectionPrefix) {
        let capabilities = dpcmsAdminAjax.default_capabilities;
        let capabilityContainer = $(elementId);
        capabilityContainer.empty();
        $.each(capabilities, function (index, capability) {
            let capabilityId = sectionPrefix + '_cap_' + capability;
            let formattedCapability = formatCapabilityName(capability);
            capabilityContainer.append(
                '<div class="dpcms-capability-checkbox-container">' +
                '<input type="checkbox" id="' + capabilityId + '" class="dpcms-capability-checkbox" value="' + capability + '">' +
                '<label for="' + capabilityId + '">' + formattedCapability + '</label>' +
                '</div>'
            );
            $('#' + capabilityId).change(function () {
                if ($(this).is(':checked')) {
                    $('label[for="' + capabilityId + '"]').css("font-weight", "bold");
                } else {
                    $('label[for="' + capabilityId + '"]').css("font-weight", "normal");
                }
            });
        });
    }
    function formatCapabilityName(capability) {
        return capability
            .replace(/_/g, ' ')              // Replace underscores with spaces
            .replace(/\b\w/g, char => char.toUpperCase()); // Capitalize the first letter of each word
    }
}

function assignRepRolesAjaxHandler($) {
    populateRoleDropdown($, 'dpcms-role');

    $('#dpcms-assign-role-button').on('click', function () {
        var data = {
            action: 'dpcms_assign_roles_ajax',
            nonce: dpcmsAdminAjax.assign_nonce,
            user_id: $('#user_id').val(),
            role: $('#dpcms-role').val()
        };

        console.log(data);

        $.post(dpcmsAdminAjax.ajax_url, data, function (response) {
            if (response.success) {
                alert('Role assigned successfully');
                location.reload();
            } else {
                alert('Failed to assign role: ' + response.data.message);
            }
        });
    });

    $('#dpcms-unassign-role-button').click(function () {
        var data = {
            action: 'dpcms_unassign_roles_ajax',
            nonce: dpcmsAdminAjax.assign_nonce,
            user_id: $('#user_id').val(),
            role: $('#dpcms-role').val()
        };
        $.post(dpcmsAdminAjax.ajax_url, data, function (response) {
            if (response.success) {
                alert('Role unassigned successfully');
                location.reload();
            } else {
                alert('Failed to unassign role: ' + response.data.message);
            }
        });
    });
}

function populateRoleDropdown($, roleDropdownId) {
    let roles = dpcmsAdminAjax.plugin_roles;
    let roleDropdown = $('#' + roleDropdownId);
    roleDropdown.empty();
    roleDropdown.append('<option value="">Select a role</option>');

    $.each(roles, function (roleName, roleInfo) {
        let displayName = roleName.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        roleDropdown.append('<option value="' + roleName + '">' + displayName + '</option>');
    });
}

function repRolesTableHandler() {
    const toggleTableButton = document.getElementById('dpcms-toggleTableButton');
    if (toggleTableButton) {
        toggleTableButton.addEventListener('click', function () {
            var table = document.getElementById('dpcms-userTable');
            var tableElements = document.getElementsByClassName("dpcms-table-toggle");

            var tableElementsArray = Array.prototype.slice.call(tableElements);

            if (table.style.display === 'none' || table.style.display === '') {
                tableElementsArray.forEach(function (element) {
                    if (element === table) {
                        table.style.display = 'table';
                    } else {
                        element.style.display = "block";
                    }
                });
            } else {
                tableElementsArray.forEach(function (element) {
                    element.style.display = "none";
                });
            }
        });
    }
    var inputId = document.getElementById('dpcms-searchId')?.value.toLowerCase() ?? '';
    var inputName = document.getElementById('dpcms-searchName')?.value.toLowerCase() ?? '';
    var inputEmail = document.getElementById('dpcms-searchEmail')?.value.toLowerCase() ?? '';
    var inputRole = document.getElementById('dpcms-searchRole')?.value.toLowerCase() ?? '';
    var table = document.getElementById('dpcms-userTable');
    var tr = table?.getElementsByTagName('tr') ?? [];

    for (var i = 1; i < tr.length; i++) {
        var tdId = tr[i].getElementsByTagName('td')[0];
        var tdName = tr[i].getElementsByTagName('td')[1];
        var tdEmail = tr[i].getElementsByTagName('td')[2];
        var tdRole = tr[i].getElementsByTagName('td')[3];

        if (tdId && tdName && tdEmail && tdRole) {
            var idText = tdId.innerHTML.toLowerCase();
            var nameText = tdName.innerHTML.toLowerCase();
            var emailText = tdEmail.innerHTML.toLowerCase();
            var roleText = tdRole.innerHTML.toLowerCase();

            if (idText.indexOf(inputId) > -1 && nameText.indexOf(inputName) > -1 &&
                emailText.indexOf(inputEmail) > -1 && roleText.indexOf(inputRole) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

function repRolesTableSearchAddListeners() {
    document.getElementById('dpcms-searchId')?.addEventListener('keyup', repRolesTableHandler);
    document.getElementById('dpcms-searchName')?.addEventListener('keyup', repRolesTableHandler);
    document.getElementById('dpcms-searchEmail')?.addEventListener('keyup', repRolesTableHandler);
    document.getElementById('dpcms-searchRole')?.addEventListener('keyup', repRolesTableHandler);
}

function userSearchDropdown() {
    var searchInput = document.getElementById('dpcms-user_search');
    var select = document.getElementById('user_id');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            var filter = searchInput.value.toLowerCase();
            var options = select.options;
            var firstVisibleOption = null;

            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                var text = option.text.toLowerCase();
                if (text.includes(filter)) {
                    option.style.display = '';
                    if (firstVisibleOption === null) {
                        firstVisibleOption = option;
                    }
                } else {
                    option.style.display = 'none';
                }
            }

            // Select the first visible option
            if (firstVisibleOption) {
                select.value = firstVisibleOption.value;
            } else {
                select.value = '';
            }
        });
    }
}

function filterContractsTable() {
    var inputDocumentIdElem = document.getElementById('dpcms-searchDocumentId');
    var inputFirstNameElem = document.getElementById('dpcms-searchFirstName');
    var inputLastNameElem = document.getElementById('dpcms-searchLastName');
    var inputStartDateElem = document.getElementById('dpcms-searchStartDate');
    var inputEndDateElem = document.getElementById('dpcms-searchEndDate');
    var inputNoEmailDownloadElem = document.getElementById('dpcms-searchNoEmailDownload');
    var excludeNoEmailDownloadElem = document.getElementById('dpcms-excludeNoEmailDownload');

    var inputDocumentId = inputDocumentIdElem ? inputDocumentIdElem.value.toLowerCase() : '';
    var inputFirstName = inputFirstNameElem ? inputFirstNameElem.value.toLowerCase() : '';
    var inputLastName = inputLastNameElem ? inputLastNameElem.value.toLowerCase() : '';
    var inputStartDate = inputStartDateElem ? inputStartDateElem.value : '';
    var inputEndDate = inputEndDateElem ? inputEndDateElem.value : '';
    var inputNoEmailDownload = inputNoEmailDownloadElem ? inputNoEmailDownloadElem.checked : false;
    var excludeNoEmailDownload = excludeNoEmailDownloadElem ? excludeNoEmailDownloadElem.checked : false;

    var table = document.getElementById('dpcms-userTable');
    if (!table) return;

    var tr = table.getElementsByTagName('tr');

    for (var i = 1; i < tr.length; i++) {
        var tdDocumentId = tr[i].getElementsByTagName('td')[0];
        var tdFirstName = tr[i].getElementsByTagName('td')[1];
        var tdLastName = tr[i].getElementsByTagName('td')[2];
        var tdEmail = tr[i].getElementsByTagName('td')[3];
        var tdDateCreated = tr[i].getElementsByTagName('td')[4];
        var tdNoEmailDownload = tr[i].getElementsByTagName('td')[5];

        if (tdDocumentId && tdFirstName && tdLastName && tdDateCreated && tdNoEmailDownload) {
            var documentIdText = tdDocumentId.innerHTML.toLowerCase();
            var firstNameText = tdFirstName.innerHTML.toLowerCase();
            var lastNameText = tdLastName.innerHTML.toLowerCase();
            var dateCreatedText = tdDateCreated.innerHTML;
            var noEmailDownloadInput = tdNoEmailDownload.getElementsByTagName('input')[0];
            var noEmailDownloadChecked = noEmailDownloadInput ? noEmailDownloadInput.checked : false;

            var matchesFilters =
                (documentIdText.indexOf(inputDocumentId) > -1 || !inputDocumentId) &&
                (firstNameText.indexOf(inputFirstName) > -1 || !inputFirstName) &&
                (lastNameText.indexOf(inputLastName) > -1 || !inputLastName);

            if (inputStartDate && inputEndDate) {
                var dateCreated = new Date(dateCreatedText);
                var startDate = new Date(inputStartDate);
                var endDate = new Date(inputEndDate);
                matchesFilters = matchesFilters && dateCreated >= startDate && dateCreated <= endDate;
            }

            matchesFilters = matchesFilters &&
                (noEmailDownloadChecked == inputNoEmailDownload || !inputNoEmailDownload);

            if (excludeNoEmailDownload) {
                matchesFilters = matchesFilters && !noEmailDownloadChecked;
            }

            tr[i].style.display = matchesFilters ? '' : 'none';
        }
    }
}

function contractsTableAddListenerHandler() {
    var searchButton = document.getElementById('dpcms-searchButton');
    if (searchButton) {
        searchButton.addEventListener('click', filterContractsTable);
    }

    var searchDocumentId = document.getElementById('dpcms-searchDocumentId');
    if (searchDocumentId) {
        searchDocumentId.addEventListener('input', filterContractsTable);
    }

    var searchFirstName = document.getElementById('dpcms-searchFirstName');
    if (searchFirstName) {
        searchFirstName.addEventListener('input', filterContractsTable);
    }

    var searchLastName = document.getElementById('dpcms-searchLastName');
    if (searchLastName) {
        searchLastName.addEventListener('input', filterContractsTable);
    }

    var searchStartDate = document.getElementById('dpcms-searchStartDate');
    if (searchStartDate) {
        searchStartDate.addEventListener('input', filterContractsTable);
    }

    var searchEndDate = document.getElementById('dpcms-searchEndDate');
    if (searchEndDate) {
        searchEndDate.addEventListener('input', filterContractsTable);
    }

    var searchNoEmailDownload = document.getElementById('dpcms-searchNoEmailDownload');
    if (searchNoEmailDownload) {
        searchNoEmailDownload.addEventListener('change', filterContractsTable);
    }

    var excludeNoEmailDownload = document.getElementById('dpcms-excludeNoEmailDownload');
    if (excludeNoEmailDownload) {
        excludeNoEmailDownload.addEventListener('change', filterContractsTable);
    }
}

function licenseKeyActivationHandler($) {
    var consent = dpcmsAdminAjax.consent;
    var consentCheckboxValue = $('#dpcms_data_consent').is(':checked') ? 1 : 0;

    if (consent != consentCheckboxValue) {
        consent = consentCheckboxValue;
    }

    $('#dpcms_license_key_activate').on('click', function () {
        var licenseKey = $('#dpcms_license_key').val();
        var apiKey = $('#dpcms_api_key').val();
        var messageDiv = $('#dpcms_license_key_message');

        if (!consent) {
            alert('Please mark the consent to privacy policy checkbox and save the settings.');
            return;
        }

        messageDiv.html('Validating...');

        var requestData = {
            license_key: licenseKey,
            api_key: apiKey,
            id: dpcmsAdminAjax.id
        };

        $.post('https://deluxeplugins.com/wp-json/dp-license/v1/activate', requestData, function (response) {
            if (response.valid) {
                var eLicenseKey = response.e_license_key;
                messageDiv.html(response.message || 'License key activated successfully.');

                $.post(dpcmsAdminAjax.ajax_url, {
                    action: 'dpcms_save_keys',
                    e_license_key: eLicenseKey,
                    api_key: apiKey,
                    security: dpcmsAdminAjax.license_nonce
                }, function (saveResponse) {
                    if (saveResponse.success) {
                        messageDiv.html('License key validated and saved successfully. Premium features unlocked.');
                        location.reload();
                    } else {
                        messageDiv.html('Failed to save license key. Please try again.');
                    }
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    messageDiv.html(jqXHR.responseJSON.message || 'Error saving license key. Please try again.');
                });
            } else {
                messageDiv.html(response.message || 'Failed to validate license.');
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            messageDiv.html(jqXHR.responseJSON.message || 'Error activating license. Please try again.');
        });
    });
}

function requestPremiumContent(elementId, contentType, contentId) {
    if (document.getElementsByClassName("dpcms-premium-content").length <= 0) return false;

    var requestData = {
        action: 'dpcms_request_premium_content',
        nonce: dpcmsAdminAjax.premium_nonce,
        content_type: contentType,
        content_id: contentId
    };

    jQuery.post(dpcmsAdminAjax.ajax_url, requestData, function (response) {
        if (response.success) {
            jQuery('#' + elementId).html(response.data.content);
        } else {
            jQuery('#' + elementId).html('Failed to load premium content.');
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        jQuery('#' + elementId).html(jqXHR.responseJSON.message || 'Error loading premium content. Please try again.');
    });
}

function addListToContract($) {
    const dynamicContainer = document.getElementById('dpcms-dynamic-fields-container');

    $(dynamicContainer).find('.dpcms-field-type-select').each(function () {
        let $field = $(this).closest('.dpcms-field');
        let $group = $(this).closest('.dpcms-field-group');
        let listSelected = $(this).find('option:selected[value="list"]').length > 0;

        if (listSelected) {
            $field.find('.dpcms-dynamic-list-notice').text('This must be the only item in the header').show();
            $group.find('.dpcms-add-field').prop('disabled', true).css({ 'background-color': '#cccccc', 'color': '#666666' });
        } else {
            $field.find('.dpcms-dynamic-list-notice').hide();
            let otherListsSelected = $group.find('.dpcms-field-type-select option:selected[value="list"]').length > 0;
            if (!otherListsSelected) {
                $group.find('.dpcms-add-field').prop('disabled', false).css({ 'background-color': '', 'color': '' });
            }
        }
    });

    $('#dpcms-add-field-group').on('click', function () {
        let usedIndices = [];
        Array.from(dynamicContainer.children).forEach(child => {
            usedIndices.push(parseInt(child.getAttribute('data-index')));
        });

        // Find the smallest missing index
        let i = 0;
        while (usedIndices.includes(i)) {
            i++;
        }
        fieldGroupIndex = i;

        $('#dpcms-dynamic-fields-container').append(`
            <div class="dpcms-field-group" data-index="${fieldGroupIndex}" style="margin-bottom: 15px;">
                <label for="dpcms_dynamic_fields[${fieldGroupIndex}][header]">Header:</label>
                <input type="text" name="dpcms_dynamic_fields[${fieldGroupIndex}][header]" value="" style="width: 60%;" required>
                <div class="dpcms-fields"></div>
                <button type="button" class="dpcms-add-field" style="margin-top: 10px;">Add Field</button>
                <button type="button" class="dpcms-remove-field-group" style="margin-top: 10px; margin-left: 10px;">Remove Header</button>
                <div class="dpcms-move-buttons">
                    <button type="button" class="dpcms-move-up">↑</button>
                    <button type="button" class="dpcms-move-down">↓</button>
                </div>
            </div>
        `);
        fieldGroupIndex++;
        dpcmsAdminAjax.fieldGroupIndex = fieldGroupIndex;
    });

    $(document).on('click', '.dpcms-add-field', function () {
        let $group = $(this).closest('.dpcms-field-group');
        let index = $group.data('index');
        let fieldIndex = $group.find('.dpcms-field').length;


        $group.find('.dpcms-fields').append(`
            <div class="dpcms-field" data-index="${fieldIndex}" style="display: flex; align-items: center; margin-bottom: 10px;">
                <label for="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][type]" style="margin-right: 5px;">Field Type:</label>
                <select name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][type]" class="dpcms-field-type-select" style="margin-right: 10px;">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="list">Dynamic List</option>
                </select>
                <label for="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][label]" style="margin-right: 5px;">Field Label:</label>
                <input type="text" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][label]" value="" style="margin-right: 10px;" required>
                <label style="margin-right: 5px;">Required:</label>
                <input type="checkbox" name="dpcms_dynamic_fields[${index}][fields][${fieldIndex}][required]" value="1">
                <span class="dpcms-dynamic-list-notice" style="display: none;"></span> 
                <button type="button" class="dpcms-remove-field" style="margin-left: auto;">Remove Field</button>
            </div>
        `);
    });

    $(document).on('click', '.dpcms-remove-field', function () {
        let $group = $(this).closest('.dpcms-field-group');
        $(this).closest('.dpcms-field').remove();

        let listSelected = $group.find('.dpcms-field-type-select option:selected[value="list"]').length > 0;
        if (!listSelected) {
            $group.find('.dpcms-add-field').prop('disabled', false).css({ 'background-color': '', 'color': '' });
        }
    });

    $(document).on('click', '.dpcms-remove-field-group', function () {
        $(this).closest('.dpcms-field-group').remove();
    });

    $(document).on('click', '.dpcms-move-up', function () {
        let $group = $(this).closest('.dpcms-field-group');
        $group.prev('.dpcms-field-group').before($group);
    });

    $(document).on('click', '.dpcms-move-down', function () {
        let $group = $(this).closest('.dpcms-field-group');
        $group.next('.dpcms-field-group').after($group);
    });

    $(document).on('change', '.dpcms-field-type-select', function () {
        let $field = $(this).closest('.dpcms-field');
        let $group = $(this).closest('.dpcms-field-group');
        let listSelected = $(this).find('option:selected[value="list"]').length > 0;

        if (listSelected) {
            $field.find('.dpcms-dynamic-list-notice').text('This must be the only item in the header').show();
            $group.find('.dpcms-add-field').prop('disabled', true).css({ 'background-color': '#cccccc', 'color': '#666666' });
        } else {
            $field.find('.dpcms-dynamic-list-notice').hide();
            $group.find('.dpcms-add-field').prop('disabled', false);
            let otherListsSelected = $group.find('.dpcms-field-type-select option:selected[value="list"]').length > 0;
            if (!otherListsSelected) {
                $group.find('.dpcms-add-field').prop('disabled', false).css({ 'background-color': '', 'color': '' });
            }
        }
    });

    $('#dpcms-main-settings-form').on('submit', function (e) {

    });

    $('#dpcms-create-contract-settings-form').on('submit', function (e) {
        let isValid = true;
        let errorMessage = '';

        $('.dpcms-field-group').each(function () {
            const fieldsContainer = $(this).find('.dpcms-fields');
            if (fieldsContainer.children().length === 0) {
                isValid = false;
                errorMessage = 'Each field group must have at least one field.';
                return false;
            }

            let listCount = fieldsContainer.find('.dpcms-field-type-select option:selected[value="list"]').length;
            let containerChildCount = fieldsContainer.children('.dpcms-field').length;
            if (listCount > 0 && containerChildCount > 1) {
                isValid = false;
                errorMessage = 'If dynamic list is selected, it must be the only item in the group.';
                $('html, body').animate({
                    scrollTop: $(this).offset().top
                }, 500);
                return false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
}

function addScrollButtonEventListeners() {
    var buttonTop = document.getElementById('dpcms-scroll-to-top');
    var buttonBottom = document.getElementById('dpcms-scroll-to-bottom');

    if (buttonTop) {
        buttonTop.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    if (buttonBottom) {
        buttonBottom.addEventListener('click', function () {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        });
    }
}

function noPermissionDenial() {
    var noPermissionElement = document.getElementById('dpcms-no-permission');
    if (noPermissionElement) {
        alert("You do not have sufficient permissions to perform this action.");
    }
}