<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Plugin;

use GDMexico\RestrictedShipping\Model\RestrictionChecker;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;

class ShippingInformationManagementPlugin
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

    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        $quote = $this->cartRepository->getActive((int)$cartId);
        $postcode = (string)$addressInformation->getShippingAddress()->getPostcode();

        $result = $this->restrictionChecker->validateQuoteByPostcode($quote, $postcode);

        if (!empty($result['is_restricted'])) {
            throw new LocalizedException(__($result['message']));
        }

        return [$cartId, $addressInformation];
    }
}