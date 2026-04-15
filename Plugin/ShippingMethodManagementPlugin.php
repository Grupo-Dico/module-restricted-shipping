<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Plugin;

use GDMexico\RestrictedShipping\Model\RestrictionChecker;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\ShippingMethodManagement;

class ShippingMethodManagementPlugin
{
    private CartRepositoryInterface $cartRepository;
    private RestrictionChecker $restrictionChecker;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        RestrictionChecker $restrictionChecker
    ) {
        $this->cartRepository = $cartRepository;
        $this->restrictionChecker = $restrictionChecker;
    }

    public function afterEstimateByAddress(
        ShippingMethodManagement $subject,
        array $result,
        $cartId,
        AddressInterface $address
    ): array {
        $quote = $this->cartRepository->getActive((int)$cartId);
        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }

    public function afterEstimateByExtendedAddress(
        ShippingMethodManagement $subject,
        array $result,
        $cartId,
        AddressInterface $address
    ): array {
        $quote = $this->cartRepository->getActive((int)$cartId);
        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }
}