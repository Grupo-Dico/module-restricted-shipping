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
use Magento\Checkout\Model\GuestShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestShippingInformationManagementPlugin
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

    public function beforeSaveAddressInformation(
        GuestShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        $quoteId = $this->resolveQuoteId((string)$cartId);
        $quote = $this->cartRepository->getActive($quoteId);
        $postcode = (string)$addressInformation->getShippingAddress()->getPostcode();

        $result = $this->restrictionChecker->validateQuoteByPostcode($quote, $postcode);

        if (!empty($result['is_restricted'])) {
            throw new LocalizedException(__($result['message']));
        }

        return [$cartId, $addressInformation];
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