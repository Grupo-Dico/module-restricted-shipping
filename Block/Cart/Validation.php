<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Block\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Customer\Model\Session as CustomerSession;

class Validation extends Template
{
    private CheckoutSession $checkoutSession;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private CustomerSession $customerSession;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @return string
     */
    public function getFrontendCartId(): string
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteId = (int)$quote->getId();

        if (!$quoteId) {
            return '';
        }

        if ($this->customerSession->isLoggedIn()) {
            return (string)$quoteId;
        }

        $mask = $this->quoteIdMaskFactory->create()->load($quoteId, 'quote_id');
        return (string)$mask->getMaskedId();
    }
}
