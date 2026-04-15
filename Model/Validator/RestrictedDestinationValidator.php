<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model\Validator;

use GDMexico\RestrictedShipping\Model\Config;
use GDMexico\RestrictedShipping\Model\StringNormalizer;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Psr\Log\LoggerInterface;

class RestrictedDestinationValidator
{
    private const ATTRIBUTE_PRODUCT_RESTRICTED = 'is_external_carrier_restricted';

    private Config $config;
    private ProductResource $productResource;
    private LoggerInterface $logger;
    private StringNormalizer $stringNormalizer;

    public function __construct(
        Config $config,
        StringNormalizer $stringNormalizer,
        ProductResource $productResource,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->stringNormalizer = $stringNormalizer;
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    public function validate(CartInterface $quote, string $municipality): array
    {
        $storeId = (int)$quote->getStoreId();

        $this->logger->debug('RestrictedShipping validate start', [
            'quote_id' => (int)$quote->getId(),
            'store_id' => $storeId,
            'municipality' => $municipality
        ]);

        if (!$this->config->isEnabled($storeId)) {
            return $this->buildResult(false, $municipality, []);
        }

        $normalizedMunicipality = $this->stringNormalizer->normalize($municipality);
        $restrictedMunicipalities = $this->config->getRestrictedMunicipalities($storeId);

        if ($normalizedMunicipality === '') {
            return $this->buildResult(false, $municipality, []);
        }

        if (!in_array($normalizedMunicipality, $restrictedMunicipalities, true)) {
            return $this->buildResult(false, $municipality, []);
        }

        if (!$this->config->isProductRuleEnabled($storeId)) {
            return $this->buildResult(false, $municipality, []);
        }

        $matched = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $debug = $this->debugItem($item, $storeId);

            if ((int)$debug['resolved_attribute_value'] !== 1) {
                continue;
            }

            $matched[] = [
                'item_id' => (int)$item->getItemId(),
                'sku' => (string)$item->getSku(),
                'name' => (string)$item->getName()
            ];
        }

        return $this->buildResult(!empty($matched), $municipality, $matched);
    }

    private function debugItem(CartItemInterface $item, int $storeId): array
    {
        $parentProductId = (int)$item->getProductId();
        $simpleProductId = null;

        $simpleOption = $item->getOptionByCode('simple_product');
        if ($simpleOption && $simpleOption->getProduct()) {
            $simpleProductId = (int)$simpleOption->getProduct()->getId();
        }

        $candidateIds = array_values(array_unique(array_filter([
            $parentProductId,
            $simpleProductId
        ])));

        foreach ($candidateIds as $candidateId) {
            $storeValue = $this->productResource->getAttributeRawValue(
                $candidateId,
                self::ATTRIBUTE_PRODUCT_RESTRICTED,
                $storeId
            );

            $defaultValue = $this->productResource->getAttributeRawValue(
                $candidateId,
                self::ATTRIBUTE_PRODUCT_RESTRICTED,
                0
            );

            $resolved = $this->hasValue($storeValue) ? $storeValue : $defaultValue;

            if ((int)$resolved === 1) {
                return [
                    'resolved_attribute_value' => 1
                ];
            }
        }

        return [
            'resolved_attribute_value' => 0
        ];
    }

    private function hasValue($value): bool
    {
        return !($value === false || $value === null || $value === '');
    }

    private function buildResult(bool $isRestricted, string $municipality, array $matchedItems): array
    {
        return [
            'is_restricted' => $isRestricted,
            'municipality' => $municipality,
            'matched_items' => $matchedItems,
            'message' => $isRestricted ? $this->config->getCustomerMessage() : ''
        ];
    }
}