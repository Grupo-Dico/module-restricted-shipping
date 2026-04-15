<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Controller\Ajax;

use GDMexico\RestrictedShipping\Model\RestrictionChecker;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Validate implements HttpGetActionInterface
{
    private JsonFactory $resultJsonFactory;
    private CheckoutSession $checkoutSession;
    private RestrictionChecker $restrictionChecker;
    private RequestInterface $request;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        RestrictionChecker $restrictionChecker,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->restrictionChecker = $restrictionChecker;
        $this->request = $request;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $quote = $this->checkoutSession->getQuote();
        $postcode = trim((string)$this->request->getParam('postcode', ''));

        if (!$quote->getId() || $postcode === '') {
            return $result->setData([
                'is_restricted' => false,
                'municipality' => '',
                'matched_items' => [],
                'message' => ''
            ]);
        }

        $validation = $this->restrictionChecker->validateQuoteByPostcode($quote, $postcode);

        return $result->setData([
            'is_restricted' => !empty($validation['is_restricted']),
            'municipality' => (string)($validation['municipality'] ?? ''),
            'matched_items' => is_array($validation['matched_items'] ?? null)
                ? $validation['matched_items']
                : [],
            'message' => (string)($validation['message'] ?? '')
        ]);
    }
}