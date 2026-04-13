<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Api\Data;

interface RestrictedItemInterface
{
    public const ITEM_ID = 'item_id';
    public const SKU = 'sku';
    public const NAME = 'name';

    /**
     * @return int
     */
    public function getItemId(): int;

    /**
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self;

    /**
     * @return string
     */
    public function getSku(): string;

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;
}
