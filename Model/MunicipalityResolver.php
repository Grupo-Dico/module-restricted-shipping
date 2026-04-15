<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Model;

use GDMexico\RestrictedShipping\Model\Exception\MunicipalityResolutionException;
use LeanCommerce\Sepomex\Api\AddressInterface;
use Throwable;

class MunicipalityResolver
{
    private AddressInterface $sepomexAddress;

    public function __construct(
        AddressInterface $sepomexAddress
    ) {
        $this->sepomexAddress = $sepomexAddress;
    }

    /**
     * @throws MunicipalityResolutionException
     */
    public function resolveByPostcode(string $postcode): string
    {
        $postcode = trim($postcode);

        if ($postcode === '') {
            return '';
        }

        try {
            $address = $this->sepomexAddress->getAddressByZip($postcode);
        } catch (Throwable $e) {
            throw new MunicipalityResolutionException(
                'No fue posible validar el municipio con Sepomex.',
                0,
                $e
            );
        }

        if (!is_array($address)) {
            throw new MunicipalityResolutionException(
                'Respuesta inválida de Sepomex al resolver el código postal.'
            );
        }

        if (empty($address)) {
            return '';
        }

        foreach ($address as $row) {
            if (is_array($row) && !empty($row['municipio'])) {
                return trim((string)$row['municipio']);
            }
        }

        throw new MunicipalityResolutionException(
            'No fue posible obtener el municipio desde la respuesta de Sepomex.'
        );
    }
}