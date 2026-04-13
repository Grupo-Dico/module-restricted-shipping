<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);
namespace GDMexico\RestrictedShipping\Model;

use GDMexico\RestrictedShipping\Api\ValidateCheckoutInterface;
use GDMexico\RestrictedShipping\Model\Validator\RestrictedDestinationValidator;
use LeanCommerce\Sepomex\Api\AddressInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class ValidateCheckout implements ValidateCheckoutInterface
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
    public function validate(string $cartId, string $postcode): array
    {
        $quoteId = $this->resolveQuoteId($cartId);
        $quote = $this->cartRepository->getActive($quoteId);

        $address = $this->sepomexAddress->getAddressByZip($postcode);
        $municipality = (string)($address[1]['municipio'] ?? '');

        return $this->validator->validate($quote, $municipality);
    }

    /**
     * @return mixed
     */
    private function resolveQuoteId(string $cartId): int
    {
        if (ctype_digit($cartId)) {
            return (int)$cartId;
        }

        $mask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = (int)$mask->getQuoteId();

        if (!$quoteId) {
            throw new NoSuchEntityException(__('No se encontró el carrito.'));
        }

        return $quoteId;
    }
}
