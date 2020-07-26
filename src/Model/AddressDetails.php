<?php

namespace Picqer\BolRetailer\Model;

/**
 * The address details of an order.
 *
 * @property string $salutationCode          The salutation code.
 * @property string $firstName               The first name.
 * @property string $surName                 The surname.
 * @property string $streetName              The street name.
 * @property string $houseNumber             The house number.
 * @property string $houseNumberExtended     The extension on the house number.
 * @property string $addressSupplement       The address supplement.
 * @property string $extraAddressInformation Extra information about the address.
 * @property string $zipCode                 The ZIP code.
 * @property string $city                    The name of the city.
 * @property string $countryCode             The country code.
 * @property string $email                   The e-mail address.
 * @property string $company                 The company name.
 * @property string $vatNumber               The VAT number.
 * @property string $deliveryPhoneNumber     The delivery phone number.
 */
class AddressDetails extends AbstractModel
{
}
