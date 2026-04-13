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
use Magento\Quote\Model\GuestCart\GuestShippingMethodManagement;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestShippingMethodManagementPlugin
{
    private CartRepositoryInterface $cartRepository;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private RestrictionChecker $restrictionChecker;

    /**
     * @return mixed
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RestrictionChecker $restrictionChecker
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->restrictionChecker = $restrictionChecker;
    }

    /**
     * @return mixed
     */
    public function afterEstimateByAddress(
        GuestShippingMethodManagement $subject,
        array $result,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address
    ): array {
        $quoteId = (int)$this->quoteIdMaskFactory->create()
            ->load($cartId, 'masked_id')
            ->getQuoteId();

        $quote = $this->cartRepository->getActive($quoteId);
        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }

    /**
     * @return mixed
     */
    public function afterEstimateByExtendedAddress(
        GuestShippingMethodManagement $subject,
        array $result,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address
    ): array {
        $quoteId = (int)$this->quoteIdMaskFactory->create()
            ->load($cartId, 'masked_id')
            ->getQuoteId();

        $quote = $this->cartRepository->getActive($quoteId);
        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }
}
