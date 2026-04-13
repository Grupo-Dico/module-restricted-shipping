<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);
namespace GDMexico\RestrictedShipping\Model\Validator;

use GDMexico\RestrictedShipping\Model\StringNormalizer;
use GDMexico\RestrictedShipping\Model\Config;
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


    /**
     * @return mixed
     */
    public function __construct(
        Config $config,
        stringNormalizer $stringNormalizer,
        ProductResource $productResource,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->stringNormalizer = $stringNormalizer;
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function validate(CartInterface $quote, string $municipality): array
    {
        $storeId = (int)$quote->getStoreId();

        $this->logger->info('RestrictedShipping validate start', [
            'quote_id' => $quote->getId(),
            'store_id' => $storeId,
            'municipality' => $municipality
        ]);

        if (!$this->config->isEnabled($storeId)) {
            $this->logger->info('RestrictedShipping disabled by config', ['store_id' => $storeId]);
            return $this->buildResult(false, $municipality, []);
        }

        $normalizedMunicipality = $this->stringNormalizer->normalize($municipality);
        $restrictedMunicipalities = $this->config->getRestrictedMunicipalities($storeId);

        $this->logger->info('RestrictedShipping municipality check', [
            'normalized_municipality' => $normalizedMunicipality,
            'restricted_municipalities' => $restrictedMunicipalities
        ]);

        if ($normalizedMunicipality === '') {
            return $this->buildResult(false, $municipality, []);
        }

        if (!in_array($normalizedMunicipality, $restrictedMunicipalities, true)) {
            return $this->buildResult(false, $municipality, []);
        }

        if (!$this->config->isProductRuleEnabled($storeId)) {
            $this->logger->info('RestrictedShipping product rule disabled', ['store_id' => $storeId]);
            return $this->buildResult(false, $municipality, []);
        }

        $matched = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $debug = $this->debugItem($item, $storeId);
            $this->logger->info('RestrictedShipping item debug', $debug);

            if ((int)$debug['resolved_attribute_value'] !== 1) {
                continue;
            }

            $matched[] = [
                'item_id' => (int)$item->getItemId(),
                'sku'     => (string)$item->getSku(),
                'name'    => (string)$item->getName()
            ];
        }

        $this->logger->info('RestrictedShipping final result', [
            'matched_items' => $matched,
            'is_restricted' => !empty($matched)
        ]);

        return $this->buildResult(!empty($matched), $municipality, $matched);
    }

    /**
     * @return mixed
     */
    private function debugItem(CartItemInterface $item, int $storeId): array
    {
        $parentProductId = (int)$item->getProductId();
        $parentSku = (string)$item->getSku();

        $simpleProductId = null;
        $simpleSku = null;

        $simpleOption = $item->getOptionByCode('simple_product');
        if ($simpleOption && $simpleOption->getProduct()) {
            $simpleProductId = (int)$simpleOption->getProduct()->getId();
            $simpleSku = (string)$simpleOption->getProduct()->getSku();
        }

        $candidateIds = array_values(array_unique(array_filter([
            $parentProductId,
            $simpleProductId
        ])));

        $values = [];

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

            $values[] = [
                'product_id' => $candidateId,
                'store_value' => $storeValue,
                'default_value' => $defaultValue,
                'resolved_value' => $resolved
            ];

            if ((int)$resolved === 1) {
                return [
                    'item_id' => (int)$item->getItemId(),
                    'item_name' => (string)$item->getName(),
                    'parent_product_id' => $parentProductId,
                    'parent_sku' => $parentSku,
                    'simple_product_id' => $simpleProductId,
                    'simple_sku' => $simpleSku,
                    'evaluated_values' => $values,
                    'resolved_attribute_value' => 1
                ];
            }
        }

        return [
            'item_id' => (int)$item->getItemId(),
            'item_name' => (string)$item->getName(),
            'parent_product_id' => $parentProductId,
            'parent_sku' => $parentSku,
            'simple_product_id' => $simpleProductId,
            'simple_sku' => $simpleSku,
            'evaluated_values' => $values,
            'resolved_attribute_value' => 0
        ];
    }

    /**
     * @return mixed
     */
    private function hasValue($value): bool
    {
        return !($value === false || $value === null || $value === '');
    }

    /**
     * @return mixed
     */
    private function buildResult(bool $isRestricted, string $municipality, array $matchedItems): array
    {
        return [
            'is_restricted' => $isRestricted,
            'municipality'  => $municipality,
            'matched_items' => $matchedItems,
            'message'       => $isRestricted ? $this->config->getCustomerMessage() : ''
        ];
    }
}
