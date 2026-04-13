<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Api\Data;

interface ValidationResultInterface
{
    public const IS_RESTRICTED = 'is_restricted';
    public const MUNICIPALITY = 'municipality';
    public const MATCHED_ITEMS = 'matched_items';
    public const MESSAGE = 'message';

    /**
     * @return bool
     */
    public function getIsRestricted(): bool;

    /**
     * @param bool $isRestricted
     * @return $this
     */
    public function setIsRestricted(bool $isRestricted): self;

    /**
     * @return string
     */
    public function getMunicipality(): string;

    /**
     * @param string $municipality
     * @return $this
     */
    public function setMunicipality(string $municipality): self;

    /**
     * @return \GDMexico\RestrictedShipping\Api\Data\RestrictedItemInterface[]
     */
    public function getMatchedItems(): array;

    /**
     * @param \GDMexico\RestrictedShipping\Api\Data\RestrictedItemInterface[] $items
     * @return $this
     */
    public function setMatchedItems(array $items): self;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;
}
