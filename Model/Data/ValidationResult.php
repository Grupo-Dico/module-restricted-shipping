<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model\Data;

use GDMexico\RestrictedShipping\Api\Data\RestrictedItemInterface;
use GDMexico\RestrictedShipping\Api\Data\ValidationResultInterface;
use Magento\Framework\DataObject;

class ValidationResult extends DataObject implements ValidationResultInterface
{
    public function getIsRestricted(): bool
    {
        return (bool)$this->getData(self::IS_RESTRICTED);
    }

    public function setIsRestricted(bool $isRestricted): ValidationResultInterface
    {
        return $this->setData(self::IS_RESTRICTED, $isRestricted);
    }

    public function getMunicipality(): string
    {
        return (string)$this->getData(self::MUNICIPALITY);
    }

    public function setMunicipality(string $municipality): ValidationResultInterface
    {
        return $this->setData(self::MUNICIPALITY, $municipality);
    }

    public function getMatchedItems(): array
    {
        return $this->getData(self::MATCHED_ITEMS) ?: [];
    }

    public function setMatchedItems(array $items): ValidationResultInterface
    {
        return $this->setData(self::MATCHED_ITEMS, $items);
    }

    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    public function setMessage(string $message): ValidationResultInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }
}
