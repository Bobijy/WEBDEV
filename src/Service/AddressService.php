<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;

final class AddressService
{
    public function update(Address $address, array $data): Address
    {
        $fields = [];
        foreach (['firstName', 'lastName', 'phone', 'addressLine1', 'city', 'region', 'country', 'postalCode', 'barangay'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                throw new \DomainException('Invalid delivery fields.');
            }
            $fields[$field] = trim($data[$field] ?? '');
            $max = in_array($field, ['phone', 'postalCode'], true) ? 50 : 255;
            if (mb_strlen($fields[$field]) > $max || ($field !== 'barangay' && $fields[$field] === '')) {
                throw new \DomainException('Please complete all delivery fields using valid values.');
            }
        }
        $fullName = trim($fields['firstName'] . ' ' . $fields['lastName']);
        $street = implode(', ', array_filter([$fields['addressLine1'], $fields['barangay'], $fields['city'], $fields['region'], $fields['postalCode'], $fields['country']]));
        if (mb_strlen($fullName) > 255 || mb_strlen($street) > 255) {
            throw new \DomainException('The delivery name or address is too long.');
        }

        return $address->setFullName($fullName)->setFirstName($fields['firstName'])->setLastName($fields['lastName'])
            ->setPhone($fields['phone'])->setStreetAddress($street)->setAddressLine1($fields['addressLine1'])
            ->setBarangay($fields['barangay'])->setCity($fields['city'])->setRegion($fields['region'])
            ->setCountry($fields['country'])->setPostalCode($fields['postalCode']);
    }
}
