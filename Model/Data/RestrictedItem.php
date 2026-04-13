<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model\Data;

use GDMexico\RestrictedShipping\Api\Data\RestrictedItemInterface;
use Magento\Framework\DataObject;

class RestrictedItem extends DataObject implements RestrictedItemInterface
{
    public function getItemId(): int
    {
        return (int)$this->getData(self::ITEM_ID);
    }

    public function setItemId(int $itemId): RestrictedItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    public function getSku(): string
    {
        return (string)$this->getData(self::SKU);
    }

    public function setSku(string $sku): RestrictedItemInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): RestrictedItemInterface
    {
        return $this->setData(self::NAME, $name);
    }
}
