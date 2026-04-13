<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);
namespace GDMexico\RestrictedShipping\Api;

interface ValidateCheckoutInterface
{
    /**
     * @param string $cartId
     * @param string $postcode
     * @return array
     */
    public function validate(string $cartId, string $postcode): array;
}