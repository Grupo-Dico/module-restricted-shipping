<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Plugin;

use GDMexico\RestrictedShipping\Model\RestrictionChecker;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\GuestCart\GuestShippingMethodManagement;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestShippingMethodManagementPlugin
{
    private CartRepositoryInterface $cartRepository;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private RestrictionChecker $restrictionChecker;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RestrictionChecker $restrictionChecker
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->restrictionChecker = $restrictionChecker;
    }

    public function afterEstimateByAddress(
        GuestShippingMethodManagement $subject,
        array $result,
        $cartId,
        AddressInterface $address
    ): array {
        $quoteId = $this->resolveQuoteId((string)$cartId);
        $quote = $this->cartRepository->getActive($quoteId);

        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }

    public function afterEstimateByExtendedAddress(
        GuestShippingMethodManagement $subject,
        array $result,
        $cartId,
        AddressInterface $address
    ): array {
        $quoteId = $this->resolveQuoteId((string)$cartId);
        $quote = $this->cartRepository->getActive($quoteId);

        $validation = $this->restrictionChecker->validateQuoteByPostcode(
            $quote,
            (string)$address->getPostcode()
        );

        return !empty($validation['is_restricted']) ? [] : $result;
    }

    private function resolveQuoteId(string $cartId): int
    {
        $quoteId = (int)$this->quoteIdMaskFactory->create()
            ->load($cartId, 'masked_id')
            ->getQuoteId();

        if (!$quoteId) {
            throw new NoSuchEntityException(__('No se encontró el carrito.'));
        }

        return $quoteId;
    }
}