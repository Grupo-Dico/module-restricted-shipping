<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);
namespace GDMexico\RestrictedShipping\Plugin;

use GDMexico\RestrictedShipping\Model\Validator\RestrictedDestinationValidator;
use LeanCommerce\Sepomex\Api\AddressInterface;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class PaymentInformationManagementPlugin
{
    private CartRepositoryInterface $cartRepository;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private AddressInterface $sepomexAddress;
    private RestrictedDestinationValidator $validator;

    /**
     * @return mixed
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        AddressInterface $sepomexAddress,
        RestrictedDestinationValidator $validator
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->sepomexAddress = $sepomexAddress;
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        ...$args
    ): array {
        $quoteId = $this->resolveQuoteId((string)$cartId);
        $quote = $this->cartRepository->getActive($quoteId);
        $shippingAddress = $quote->getShippingAddress();
        $postcode = (string)$shippingAddress->getPostcode();
        $municipality = (string)$shippingAddress->getCity();

        if ($postcode !== '') {
            $address = $this->sepomexAddress->getAddressByZip($postcode);
            $municipality = (string)($address[1]['municipio'] ?? $municipality);
        }

        $result = $this->validator->validate($quote, $municipality);
        if (!empty($result['is_restricted'])) {
            throw new LocalizedException(__($result['message']));
        }

        return array_merge([$cartId], $args);
    }

    /**
     * @return mixed
     */
    private function resolveQuoteId(string $cartId): int
    {
        if (ctype_digit($cartId)) {
            return (int)$cartId;
        }

        return (int)$this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
    }
}
