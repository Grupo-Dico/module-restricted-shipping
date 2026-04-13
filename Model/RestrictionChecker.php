<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);
namespace GDMexico\RestrictedShipping\Model;

use GDMexico\RestrictedShipping\Model\Validator\RestrictedDestinationValidator;
use LeanCommerce\Sepomex\Api\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;

class RestrictionChecker
{
    private AddressInterface $sepomexAddress;
    private RestrictedDestinationValidator $validator;

    /**
     * @return mixed
     */
    public function __construct(
        AddressInterface $sepomexAddress,
        RestrictedDestinationValidator $validator
    ) {
        $this->sepomexAddress = $sepomexAddress;
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function validateQuoteByPostcode(CartInterface $quote, string $postcode): array
    {
        $postcode = trim($postcode);
        if ($postcode === '') {
            return [
                'is_restricted' => false,
                'municipality' => '',
                'matched_items' => [],
                'message' => ''
            ];
        }

        $address = $this->sepomexAddress->getAddressByZip($postcode);
        $municipality = (string)($address[1]['municipio'] ?? '');

        return $this->validator->validate($quote, $municipality);
    }
}
