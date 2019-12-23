'use strict';

var MAGE42_START_FUNCTION;

if (typeof Object.assign !== 'function') {
    // Must be writable: true, enumerable: false, configurable: true
    Object.defineProperty(Object, "assign", {
        value: function assign(target, varArgs) { // .length of function is 2
            'use strict';
            if (target === null || target === undefined) {
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource !== null && nextSource !== undefined) {
                    for (var nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        },
        writable: true,
        configurable: true
    });
}


document.observe("dom:loaded", MAGE42_START_FUNCTION = function () {
    if (typeof MAGE42PCNL_CONFIG === "undefined")
        return;

    var autocompleteCountries = MAGE42PCNL_CONFIG.autocompleteCountries.split(",");
    var moveCountry = MAGE42PCNL_CONFIG.moveCountry;
    var autocompleteCountriesClone = autocompleteCountries.slice();
    var countryField, wrapper;
    for (let i = 0; i < autocompleteCountries.length; i++) {
        [autocompleteCountries[i], autocompleteCountriesClone[i]] = autocompleteCountries[i].split("-");
    }

    var autocomplete = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };

    var checkbox = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };

    var postcodeField = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };
    var cityField = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };
    var street1Field = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };
    var street2Field = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };
    var regionIdField = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };
    var regionField = {
        'billing:': null,
        'shipping:': null,
        'account:': null
    };

    var Mage42_PostcodeNL = {
        /**
         *
         * @param prefix
         * @param typeField
         */
        setInputFields: function (prefix, typeField) {
            if (typeField === "country") {
                postcodeField[prefix] = $('postcode') || $('zip');
                cityField[prefix] = $('city');
                street1Field[prefix] = $('street_1');
                street2Field[prefix] = $('street_2');
                regionIdField[prefix] = $('region_id');
                regionField[prefix] = $('region');
            } else {
                postcodeField[prefix] = $(prefix + 'postcode') || $(prefix + 'zip');
                cityField[prefix] = $(prefix + 'city');
                street1Field[prefix] = $(prefix + 'street1');
                street2Field[prefix] = $(prefix + 'street2');
                regionIdField[prefix] = $(prefix + 'region_id');
                regionField[prefix] = $(prefix + 'region');
            }
        },
        /**
         *
         * @param prefix string
         */
        resetFields: function(prefix) {
            postcodeField[prefix].setValue("");
            cityField[prefix].setValue("");
            street1Field[prefix].setValue("");
            street2Field[prefix].setValue("");
            regionIdField[prefix].setValue("");
            regionField[prefix].setValue("");
        },
        /**
         *
         * @param type
         * @param display
         * @param prefix
         */
        displayFields: function(type, display, prefix) {
            postcodeField[prefix].setAttribute("type", type);
            cityField[prefix].setAttribute("type", type);
            postcodeField[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = display;
            cityField[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = display;
            street1Field[prefix].setAttribute("type", type);
            street1Field[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = display;
            street2Field[prefix].setAttribute("type", type);
            street2Field[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = display;
            regionIdField[prefix].setAttribute("type", type);
            //regionIdField.parentNode.parentNode.firstChild.nextSibling.style.display = display;
            regionIdField[prefix].setStyle({
                'display': display
            });
            regionField[prefix].setAttribute("type", type);
            regionField[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = display;
        },
        /**
         *
         * @param prefix string
         * @param elementValue
         * @param postcodenl PostcodeNl || PostcodeNlShipping
         */
        autocomplete: function (prefix, elementValue, postcodenl) {
            if (isAutocompleteCountry(elementValue)) {
                var iso3Code = autocompleteCountriesClone[autocompleteCountries.indexOf(elementValue)].toLocaleLowerCase();
                let wrapper;
                /*if (prefix === "account:") {
                  //  $(jQuery('#mage42-wrapper')).remove();
                    $(jQuery('#billing\\:mage42-wrapper')).remove();
                    $(jQuery('#shipping\\:mage42-wrapper')).remove();
                }*/

                /* else {
                    wrapper = jQuery('#' + prefix.split(':')[0] + '\\:mage42-wrapper');
                    $(wrapper).remove();,
                }*/
                Mage42_PostcodeNL.resetFields(prefix);
                if (jQuery('#' + prefix.split(':')[0] + '\\:mage42-wrapper').length <= 0) {
                    street1Field[prefix].up('li').insert({
                        before: '<div id="' + prefix + 'mage42-wrapper" class="field input-postcode ' + prefix + 'autocomplete-mage42-wrapper"><label for="' + prefix + 'autocomplete-mage42" class="required">' + MAGE42PCNL_CONFIG.translations.streetNameLabel + ' <em class="required">*</em></label><div class="input-box"><div class="field-wrapper"><input type="text" title="Mage42 Postcode Autocomplete" placeholder="' + MAGE42PCNL_CONFIG.translations.streetNamePlaceholder + '" name="' + prefix + 'autocomplete-mage42" id="' + prefix + 'autocomplete-mage42" value="" class="input-text input-autocomplete-term input-autocomplete-int-term required-entry" /></div></div><div id="' + prefix + 'mage42-checkbox" class="wide control"><div class="fields"><div class="input-box" style="display: block; width: 100%"><input type="checkbox" title="' + MAGE42PCNL_CONFIG.translations.checkboxTitle + '" name="' + prefix + 'mage42_checkbox" id="' + prefix + 'mage42_checkbox" value="" class="checkbox"/><label for="' + prefix + 'mage42_checkbox">' + MAGE42PCNL_CONFIG.translations.checkboxText + '</label></div></div></div></div>'
                    });
                }
                Mage42_PostcodeNL.displayFields('hidden', 'none', prefix);

                var inputElement = $(prefix + 'autocomplete-mage42');
                if (autocomplete[prefix] === null) {
                    checkbox[prefix] = $(prefix + 'mage42_checkbox');
                    checkbox[prefix].observe('change', function (e) {
                        if (checkbox[prefix].checked) {
                            Mage42_PostcodeNL.displayFields('text', 'block', prefix);
                        } else {
                            Mage42_PostcodeNL.displayFields('hidden', 'none', prefix);
                        }
                    });
                    let autocompleteUrl = MAGE42PCNL_CONFIG.baseUrl + "autocomplete";
                    let addressDetailsUrl = MAGE42PCNL_CONFIG.baseUrl + "addressdetails";
                    autocomplete[prefix] = new postcodenl.AutocompleteAddress(inputElement, {
                        autocompleteUrl: autocompleteUrl,
                        addressDetailsUrl: addressDetailsUrl,
                        autoFocus: true,
                        autoSelect: true,
                        context: iso3Code,
                        delay: 300
                    });
                } else {
                    checkbox[prefix].checked = false;
                    autocomplete[prefix].setCountry(iso3Code);
                }

                /*var autocomplete = new postcodenl.AutocompleteAddress(inputElement, {
                    autocompleteUrl: autocompleteUrl,
                    addressDetailsUrl: addressDetailsUrl,
                    autoFocus: true,
                    autoSelect: true,
                    context: iso3Code,
                    delay: 300
                });*/
                inputElement.addEventListener('autocomplete-select', function (e) {
                    if (e.detail.precision === 'Address') {
                        autocomplete[prefix].getDetails(e.detail.context, function (result) {
                            postcodeField[prefix].setValue(result.address.postcode);
                            cityField[prefix].setValue(result.address.locality);

                            if (MAGE42PCNL_CONFIG.useStreet2AsHouseNumber) {
                                street1Field[prefix].setValue(result.address.street);
                                street2Field[prefix].setValue(result.address.building);
                            } else {
                                street1Field[prefix].setValue(result.address.street + " " + result.address.building);
                            }
                            if (typeof result.details[iso3Code + "FederalState"] !== 'undefined') {
                                jQuery(regionIdField[prefix]).find("option[title='" + result.details[iso3Code + 'FederalState']['name'] + "']").prop('selected', true);
                            }
                        });
                    }
                });
            } else {

                /*if (jQuery('#' + prefix.split(':')[0] + '\\:mage42-wrapper').length === 1) {
                    street1Field.up('li').previousElementSibling.remove();
                }*/
                postcodeField[prefix].setAttribute("type", "text");
                cityField[prefix].setAttribute("type", "text");
                postcodeField[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = "block";
                cityField[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = "block";

                street1Field[prefix].setAttribute("type", "text");
                street1Field[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = "block";
                street2Field[prefix].setAttribute("type", "text");
                street2Field[prefix].parentNode.parentNode.firstChild.nextSibling.style.display = "block";
            }
        }
    };

    if (document.getElementById('country') != null) {
        countryField = "country";
        if (moveCountry) $('street_1').up('li').insert({before: $('country').parentNode.parentNode});
    } else {
        countryField = "billing:country_id";
        if (moveCountry) $('billing:street1').up('li').insert({before: $('billing:country_id').parentNode.parentNode});
    }
    let elementValue = $(countryField).options[$(countryField).selectedIndex].value;

    if (document.getElementsByClassName('my-account')[0] === undefined) {

        if (isAutocompleteCountry(elementValue)) {
            Mage42_PostcodeNL.setInputFields("billing:", 'billing:country_id');
            Mage42_PostcodeNL.autocomplete("billing:", elementValue, Object.assign({}, PostcodeNl));

            Mage42_PostcodeNL.setInputFields("shipping:", 'shipping:country_id');
            Mage42_PostcodeNL.autocomplete("shipping:", elementValue, Object.assign({}, PostcodeNl));
        }

        $('billing:country_id').observe('change', function (e) {
            Mage42_PostcodeNL.setInputFields("billing:");
            let elementValue = this.value;
            Mage42_PostcodeNL.autocomplete("billing:", elementValue, Object.assign({}, PostcodeNl));
        });

        $('shipping:country_id').observe('change', function (e) {
            Mage42_PostcodeNL.setInputFields("shipping:");
            let elementValue = this.value;
            Mage42_PostcodeNL.autocomplete("shipping:", elementValue, Object.assign({}, PostcodeNl));
        });
    } else {
        if (isAutocompleteCountry(elementValue)) {
            Mage42_PostcodeNL.setInputFields("account:", countryField);
            Mage42_PostcodeNL.autocomplete("account:", elementValue, Object.assign({}, PostcodeNl));
        }

        $('country').observe('change', function (e) {
            Mage42_PostcodeNL.setInputFields("account:", "country");
            let elementValue = this.value;
            Mage42_PostcodeNL.autocomplete("account:", elementValue, Object.assign({}, PostcodeNl));
        });
    }

    function isAutocompleteCountry(elementValue) {
        return (autocompleteCountries.indexOf(elementValue) >= 0);
    }
});

if (typeof MAGE42_START != "undefined")
    MAGE42_START_FUNCTION();