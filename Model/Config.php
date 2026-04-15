<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_ENABLED = 'gdmexico_restricted_shipping/general/enabled';
    private const XML_CUSTOMER_MESSAGE = 'gdmexico_restricted_shipping/general/customer_message';
    private const XML_MUNICIPALITIES = 'gdmexico_restricted_shipping/areas/restricted_municipalities';
    private const XML_PRODUCT_RULE_ENABLED = 'gdmexico_restricted_shipping/rules/enable_product_rule';

    private StringNormalizer $stringNormalizer;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StringNormalizer $stringNormalizer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->stringNormalizer = $stringNormalizer;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->getFlagWithFallback(self::XML_ENABLED, $storeId);
    }

    public function isProductRuleEnabled(?int $storeId = null): bool
    {
        return $this->getFlagWithFallback(self::XML_PRODUCT_RULE_ENABLED, $storeId);
    }

    public function getCustomerMessage(?int $storeId = null): string
    {
        $value = $this->getValueWithFallback(self::XML_CUSTOMER_MESSAGE, $storeId);

        return $value !== ''
            ? $value
            : 'Algunos productos en tu carrito no pueden enviarse al municipio seleccionado. Por favor, verifica tu dirección o retira los productos no disponibles para esta zona.';
    }

    public function getRestrictedMunicipalities(?int $storeId = null): array
    {
        $value = $this->getValueWithFallback(self::XML_MUNICIPALITIES, $storeId);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,]+/', $value) ?: [];
        $result = [];

        foreach ($parts as $part) {
            $normalized = $this->stringNormalizer->normalize(trim($part));
            if ($normalized !== '') {
                $result[] = $normalized;
            }
        }

        return array_values(array_unique($result));
    }

    private function getFlagWithFallback(string $path, ?int $storeId = null): bool
    {
        $storeValue = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($storeValue !== null && $storeValue !== '') {
            return (bool)$storeValue;
        }

        $defaultValue = $this->scopeConfig->getValue($path);
        return (bool)$defaultValue;
    }

    private function getValueWithFallback(string $path, ?int $storeId = null): string
    {
        $storeValue = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($storeValue !== null && trim((string)$storeValue) !== '') {
            return (string)$storeValue;
        }

        $defaultValue = $this->scopeConfig->getValue($path);
        return (string)$defaultValue;
    }
}