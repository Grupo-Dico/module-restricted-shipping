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
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\GuestShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestShippingInformationManagementPlugin
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
    public function beforeSaveAddressInformation(
        GuestShippingInformationManagement $subject,
        $cartId,
        $email,
        ShippingInformationInterface $addressInformation
    ): array {
        $quoteId = (int)$this->quoteIdMaskFactory->create()
            ->load($cartId, 'masked_id')
            ->getQuoteId();

        $quote = $this->cartRepository->getActive($quoteId);

        $postcode = (string)$addressInformation->getShippingAddress()->getPostcode();
        $address = $this->sepomexAddress->getAddressByZip($postcode);
        $municipality = (string)($address[1]['municipio'] ?? '');

        $result = $this->validator->validate($quote, $municipality);

        if (!empty($result['is_restricted'])) {
            throw new LocalizedException(__($result['message']));
        }

        return [$cartId, $email, $addressInformation];
    }
}
