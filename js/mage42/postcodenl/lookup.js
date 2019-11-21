'use strict';

var MAGE42_START_FUNCTION;

var autocompleteCountries;
var autocompleteCountriesClone;
var iso3Code;
var language = "";

var postcodeField;
var cityField;
var street1Field;
var street2Field;
var regionIdField;

document.observe("dom:loaded", MAGE42_START_FUNCTION = function()
{
    if (typeof MAGE42PCNL_CONFIG === "undefined")
        return;
    if (typeof String.prototype.trim !== "function") {
        String.prototype.trim = function () {
            return this.replace(/^\s+|\s+$/g, '');
        }
    }
    var Mage42_PostcodeNL = {
        /**
         *
         * @param prefix string
         */
        setInputFields: function(prefix) {
            postcodeField = $(prefix + 'postcode') || $(prefix + 'zip');
            cityField = $(prefix + 'city');
            street1Field = $(prefix + 'street1');
            street2Field = $(prefix + 'street2');
            regionIdField = $(prefix + 'region_id');
        },
        /**
         *
         * @param prefix string
         * @param elementValue
         * @param postcodenl PostcodeNl || PostcodeNlShipping
         */
        autocomplete: function(prefix, elementValue, postcodenl) {
            if (autocompleteCountries.includes(elementValue)) {
                iso3Code = autocompleteCountriesClone[autocompleteCountries.indexOf(elementValue)].toLocaleLowerCase();
                //if (jQuery("." + prefix + "autocomplete-mage42-wrapper")) {
                if (jQuery('#'+prefix.split(':')[0] + '\\:mage42-wrapper').length === 0) {
                    $(prefix+'street1').up('li').insert({
                        before: '<div id="'+prefix+'mage42-wrapper" class="field input-postcode ' + prefix + 'autocomplete-mage42-wrapper"><label for="' + prefix + 'autocomplete-mage42" class="required">' + MAGE42PCNL_CONFIG.translations.streetNameLabel + ' <em class="required">*</em></label><div class="input-box"><div class="field-wrapper"><input type="text" title="Mage42 Postcode Autocomplete" placeholder="' + MAGE42PCNL_CONFIG.translations.streetNamePlaceholder + '" name="' + prefix + 'autocomplete-mage42" id="' + prefix + 'autocomplete-mage42" value="" class="input-text input-autocomplete-term input-autocomplete-int-term postcodenl-autocomplete-address-input postcodenl-autocomplete-address-input-blank required-entry" /></div></div></div>'
                    });
                }
                var inputElement = $(prefix + 'autocomplete-mage42');
                inputElement.observe('keyup', function (e) {
                    let autocompleteUrl = MAGE42PCNL_CONFIG.baseUrl + "autocomplete";
                    let addressDetailsUrl = MAGE42PCNL_CONFIG.baseUrl + "addressdetails";
                    postcodenl.AutocompleteAddress(inputElement, {
                        autocompleteUrl: autocompleteUrl,
                        addressDetailsUrl: addressDetailsUrl,
                        autoFocus: true,
                        autoSelect: true,
                        context: iso3Code,
                        delay: 1000
                    });
                    inputElement.addEventListener('autocomplete-select', function (e) {
                        if (e.detail.precision === 'Address') {
                            postcodenl.getDetails(e.detail.context, function (result) {
                                postcodeField.setValue(result.address.postcode);
                                cityField.setValue(result.address.locality);
                                if (MAGE42PCNL_CONFIG.useStreet2AsHouseNumber) {
                                    street1Field.setValue(result.address.street);
                                    street2Field.setValue(result.address.buildingNumber)
                                } else {
                                    street1Field.setValue(result.address.street + " " + result.address.buildingNumber);
                                }
                                if (result.details[iso3Code + "FederalState"] !== undefined) {
                                    jQuery(regionIdField).find("option[title='"+ result.details[iso3Code + 'FederalState']['name'] +"']").prop('selected', true);
                                }
                            });
                        }
                    })
                });
            } else {
                let wrapper = jQuery("." + prefix + "autocomplete-mage42-wrapper");
                if (wrapper) {
                    wrapper.remove();
                }
            }
        }
    }

    autocompleteCountries = MAGE42PCNL_CONFIG.autocompleteCountries.split(",");
    autocompleteCountriesClone = autocompleteCountries.slice();

    for (let i = 0; i < autocompleteCountries.length; i++) {
        [autocompleteCountries[i], autocompleteCountriesClone[i]] = autocompleteCountries[i].split("-");
    }

    $('billing:country_id').observe('change', function (e) {
        Mage42_PostcodeNL.setInputFields("billing:");
        let elementValue = this.value;
        Mage42_PostcodeNL.autocomplete("billing:", elementValue, PostcodeNl);
    });

    $('shipping:country_id').observe('change', function (e) {
        Mage42_PostcodeNL.setInputFields("shipping:");
        let elementValue = this.value;
        Mage42_PostcodeNL.autocomplete("shipping:", elementValue, PostcodeNlShipping);
    });
});

if (typeof MAGE42_START != "undefined")
    MAGE42_START_FUNCTION();