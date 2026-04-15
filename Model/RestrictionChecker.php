<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model;

use GDMexico\RestrictedShipping\Model\Exception\MunicipalityResolutionException;
use GDMexico\RestrictedShipping\Model\Validator\RestrictedDestinationValidator;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

class RestrictionChecker
{
    private MunicipalityResolver $municipalityResolver;
    private RestrictedDestinationValidator $validator;
    private LoggerInterface $logger;

    public function __construct(
        MunicipalityResolver $municipalityResolver,
        RestrictedDestinationValidator $validator,
        LoggerInterface $logger
    ) {
        $this->municipalityResolver = $municipalityResolver;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    public function validateQuoteByPostcode(CartInterface $quote, string $postcode): array
    {
        $postcode = trim($postcode);

        if ($postcode === '') {
            return [
                'is_restricted' => false,
                'municipality' => '',
                'matched_items' => [],
                'message' => ''
            ];
        }

        try {
            $municipality = $this->municipalityResolver->resolveByPostcode($postcode);
        } catch (MunicipalityResolutionException $e) {
            $this->logger->error('RestrictedShipping municipality resolution failed', [
                'postcode' => $postcode,
                'quote_id' => (int)$quote->getId(),
                'error' => $e->getMessage()
            ]);

            return [
                'is_restricted' => true,
                'municipality' => '',
                'matched_items' => [],
                'message' => 'No fue posible validar la cobertura de envío en este momento. Intenta nuevamente en unos minutos.'
            ];
        }

        return $this->validator->validate($quote, $municipality);
    }
}