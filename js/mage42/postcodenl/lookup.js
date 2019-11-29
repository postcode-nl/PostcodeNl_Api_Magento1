'use strict';

var MAGE42_START_FUNCTION;

var moveCountry;
var autocompleteCountries, autocompleteCountriesClone, iso3Code;

var postcodeField, cityField, street1Field, street2Field, regionIdField, countryField, wrapper;
var isUsed = false;

document.observe("dom:loaded", MAGE42_START_FUNCTION = function () {
    if (typeof MAGE42PCNL_CONFIG === "undefined")
        return;

    autocompleteCountries = MAGE42PCNL_CONFIG.autocompleteCountries.split(",");
    moveCountry = MAGE42PCNL_CONFIG.moveCountry;
    autocompleteCountriesClone = autocompleteCountries.slice();

    for (let i = 0; i < autocompleteCountries.length; i++) {
        [autocompleteCountries[i], autocompleteCountriesClone[i]] = autocompleteCountries[i].split("-");
    }
    
    var Mage42_PostcodeNL = {
        /**
         *
         * @param prefix string
         */
        setInputFields: function (prefix, typeField) {
            if (typeField == "country") {
                postcodeField = $('postcode') || $('zip');
                cityField = $('city');
                street1Field = $('street_1');
                street2Field = $('street_2');
                regionIdField = $('region');
            } else {

                postcodeField = $(prefix + 'postcode') || $(prefix + 'zip');
                cityField = $(prefix + 'city');
                street1Field = $(prefix + 'street1');
                street2Field = $(prefix + 'street2');
                regionIdField = $(prefix + 'region_id');
            }
        },
        /**
         *
         * @param prefix string
         */
        resetFields: function(prefix) {
            postcodeField.setValue("");
            cityField.setValue("");
            street1Field.setValue("");
            street2Field.setValue("");
            regionIdField.setValue("");
        },
        /**
         *
         * @param prefix string
         * @param elementValue
         * @param postcodenl PostcodeNl || PostcodeNlShipping
         */
        autocomplete: function (prefix, elementValue, postcodenl) {
            isUsed = true;
            if (autocompleteCountries.includes(elementValue)) {
                iso3Code = autocompleteCountriesClone[autocompleteCountries.indexOf(elementValue)].toLocaleLowerCase();
                let wrapper = jQuery('#'+prefix.split(':')[0] + '\\:mage42-wrapper');
                $(wrapper).remove();
                Mage42_PostcodeNL.resetFields(prefix);
                street1Field.up('li').insert({
                    before: '<div id="' + prefix + 'mage42-wrapper" class="field input-postcode ' + prefix + 'autocomplete-mage42-wrapper"><label for="' + prefix + 'autocomplete-mage42" class="required">' + MAGE42PCNL_CONFIG.translations.streetNameLabel + ' <em class="required">*</em></label><div class="input-box"><div class="field-wrapper"><input type="text" title="Mage42 Postcode Autocomplete" placeholder="' + MAGE42PCNL_CONFIG.translations.streetNamePlaceholder + '" name="' + prefix + 'autocomplete-mage42" id="' + prefix + 'autocomplete-mage42" value="" class="input-text input-autocomplete-term input-autocomplete-int-term  required-entry" /></div></div></div>'
                });
                var inputElement = $(prefix + 'autocomplete-mage42');
                let autocompleteUrl = MAGE42PCNL_CONFIG.baseUrl + "autocomplete";
                let addressDetailsUrl = MAGE42PCNL_CONFIG.baseUrl + "addressdetails";
                var autocomplete = new postcodenl.AutocompleteAddress(inputElement, {
                    autocompleteUrl: autocompleteUrl,
                    addressDetailsUrl: addressDetailsUrl,
                    autoFocus: true,
                    autoSelect: true,
                    context: iso3Code,
                    delay: 300
                });
                inputElement.addEventListener('autocomplete-select', function (e) {
                    if (e.detail.precision === 'Address') {
                        autocomplete.getDetails(e.detail.context, function (result) {
                            postcodeField.setValue(result.address.postcode);
                            cityField.setValue(result.address.locality);

                            postcodeField.setAttribute("type", "hidden");
                            cityField.setAttribute("type", "hidden");
                            postcodeField.parentNode.parentNode.firstChild.nextSibling.style.display = "none";
                            cityField.parentNode.parentNode.firstChild.nextSibling.style.display = "none";

                            street1Field.setAttribute("type", "hidden");
                            street1Field.parentNode.parentNode.firstChild.nextSibling.style.display = "none";
                            if (MAGE42PCNL_CONFIG.useStreet2AsHouseNumber) {
                                street1Field.setValue(result.address.street);
                                street2Field.setValue(result.address.buildingNumber);
                                street2Field.setAttribute("type", "hidden");
                                street2Field.parentNode.parentNode.firstChild.nextSibling.style.display = "none";
                            } else {
                                street1Field.setValue(result.address.street + " " + result.address.buildingNumber);
                            }
                            if (result.details[iso3Code + "FederalState"] !== undefined) {
                                jQuery(regionIdField).find("option[title='" + result.details[iso3Code + 'FederalState']['name'] + "']").prop('selected', true);
                            }
                        });
                    }
                });
            } else {

                if (jQuery('#' + prefix.split(':')[0] + '\\:mage42-wrapper').length === 1) {
                    street1Field.up('li').previousElementSibling.remove();
                }
                postcodeField.setAttribute("type", "text");
                cityField.setAttribute("type", "text");
                postcodeField.parentNode.parentNode.firstChild.nextSibling.style.display = "block";
                cityField.parentNode.parentNode.firstChild.nextSibling.style.display = "block";

                street1Field.setAttribute("type", "text");
                street1Field.parentNode.parentNode.firstChild.nextSibling.style.display = "block";
                street2Field.setAttribute("type", "text");
                street2Field.parentNode.parentNode.firstChild.nextSibling.style.display = "block";
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
    if (autocompleteCountries.includes(elementValue)) {
        Mage42_PostcodeNL.setInputFields("billing:", countryField);
        Mage42_PostcodeNL.autocomplete("billing:", elementValue, Object.assign({}, PostcodeNl));
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
});

if (typeof MAGE42_START != "undefined")
    MAGE42_START_FUNCTION();