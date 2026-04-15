<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Plugin;

use GDMexico\RestrictedShipping\Model\RestrictionChecker;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class PaymentInformationManagementPlugin
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

    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        ...$args
    ): array {
        $quoteId = $this->resolveQuoteId((string)$cartId);
        $quote = $this->cartRepository->getActive($quoteId);
        $shippingAddress = $quote->getShippingAddress();
        $postcode = (string)$shippingAddress->getPostcode();

        $result = $this->restrictionChecker->validateQuoteByPostcode($quote, $postcode);

        if (!empty($result['is_restricted'])) {
            throw new LocalizedException(__($result['message']));
        }

        return array_merge([$cartId], $args);
    }

    private function resolveQuoteId(string $cartId): int
    {
        if (ctype_digit($cartId)) {
            return (int)$cartId;
        }

        $quoteId = (int)$this->quoteIdMaskFactory->create()
            ->load($cartId, 'masked_id')
            ->getQuoteId();

        if (!$quoteId) {
            throw new NoSuchEntityException(__('No se encontró el carrito.'));
        }

        return $quoteId;
    }
}