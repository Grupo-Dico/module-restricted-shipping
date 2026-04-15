<?php
/**
 * @author GDMexico Team
 * @package GDMexico_RestrictedShipping
 * @copyright Copyright (c) 2026 GDMexico.
 */
declare(strict_types=1);

namespace GDMexico\RestrictedShipping\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddExternalCarrierRestrictionAttribute implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        $attributeCode = 'is_external_carrier_restricted';
        $attributeData = [
            'type' => 'int',
            'label' => 'Restringir envío en municipios bloqueados',
            'input' => 'boolean',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'required' => false,
            'default' => 0,
            'sort_order' => 999,
            'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
            'group' => 'General',
            'visible' => true,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => ''
        ];

        if (!$eavSetup->getAttributeId($entityTypeId, $attributeCode)) {
            $eavSetup->addAttribute(Product::ENTITY, $attributeCode, $attributeData);
        }

        $this->assignAttributeToAllSets($eavSetup, $entityTypeId, $attributeCode, $attributeData['group']);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    private function assignAttributeToAllSets(
        EavSetup $eavSetup,
        int $entityTypeId,
        string $attributeCode,
        string $groupName
    ): void {
        $attributeId = (int)$eavSetup->getAttributeId($entityTypeId, $attributeCode);
        if (!$attributeId) {
            return;
        }

        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);

        foreach ($attributeSetIds as $attributeSetId) {
            $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, $groupName);

            if (!$groupId) {
                $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 100);
                $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, $groupName);
            }

            $eavSetup->addAttributeToSet($entityTypeId, (int)$attributeSetId, (int)$groupId, $attributeId);
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}