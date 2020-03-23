<?php
/**
 *
 * @copyright Copyright © 2019 Codilar Technologies Pvt. Ltd.. All rights reserved.
 */

namespace Codilar\Core\Helper;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends AbstractHelper
{
    /**
     * @var AttributeManagementInterface
     */
    protected $attributeManagement;

    /**
     * Data constructor.
     * @param Context $context
     * @param AttributeManagementInterface $attributeManagement
     */
    public function __construct(
        Context $context,
        AttributeManagementInterface $attributeManagement
    ) {
        $this->attributeManagement = $attributeManagement;
        parent::__construct($context);
    }

    /**
     * @param $eavSetup
     * @param $attributeCode
     * @param $groupName
     * @param $attributeSetName
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function assignAttributeToAttributeSet($eavSetup, array $attributeCode, $groupName, $attributeSetName)
    {
        if ($eavSetup) {
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
            $attributeSet = $eavSetup->getAttributeSet($entityTypeId, $attributeSetName);
            $attributeSetId = isset($attributeSet['attribute_set_id']) ? $attributeSet['attribute_set_id'] : '';
            if ($attributeSetId !== '') {
                $group_id = $eavSetup->getAttributeGroup(
                    $entityTypeId,
                    $attributeSet['attribute_set_id'],
                    $eavSetup->convertToAttributeGroupCode($groupName),
                    'attribute_group_id'
                );
                if (!$group_id) {
                    $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 19);
                    $group_id = $eavSetup->getAttributeGroup(
                        $entityTypeId,
                        $attributeSet['attribute_set_id'],
                        $eavSetup->convertToAttributeGroupCode($groupName),
                        'attribute_group_id'
                    );
                }
                if (is_array($attributeCode) && count($attributeCode) > 0) {
                    foreach ($attributeCode as $attribute) {
                        $this->attributeManagement->assign(
                            'catalog_product',
                            $attributeSetId,
                            $group_id,
                            $attribute['code'],
                            $attribute['sortOrder']
                        );
                    }
                }
            }
        }
    }
}
