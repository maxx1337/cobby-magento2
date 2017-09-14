<?php
namespace Mash2\Cobby\Model\Catalog\Product;

/**
 * Class Attribute
 * @package Mash2\Cobby\Model\Catalog\Product
 */
class Attribute implements \Mash2\Cobby\Api\CatalogProductAttributeInterface
{
    const ERROR_ATTRIBUTE_NOT_EXISTS = 'attribute_not_exists';
    const ERROR_ATTRIBUTE_SET_NOT_EXISTS = 'attribute_set_not_exists';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $productResource;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Api constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributeCollection
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributeCollection,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ){
        $this->attributeCollection = $attributeCollection;
        $this->productResource = $productResource;
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     */
    public function export($attributeSetId = null, $attributeId = null)
    {
        $attributesArray = array();
        $noAttr = array();
        $noAttrSet = array();

        if ($attributeId){
            $attribute = $this->productResource->getAttribute($attributeId);

            if (!$attribute) {
                $noAttr[] = $attributeId;
                $noAttr[] = self::ERROR_ATTRIBUTE_NOT_EXISTS;

            }else  {
                $attributesArray[] = $this->getAttribute($attribute);
            }
        }

        if ($attributeSetId){
            $attributes = $this->attributeCollection
                ->setAttributeSetFilter($attributeSetId)
                ->load();

            if (!$attributes->getItems()) {
                $noAttrSet[] = $attributeSetId;
                $noAttrSet[] = self::ERROR_ATTRIBUTE_SET_NOT_EXISTS;
            } else {
                foreach ($attributes as $attribute) {
                    $data = $this->getAttribute($attribute);

                    $transportObject = new \Magento\Framework\DataObject();
                    $transportObject->setData($data);

                    $this->eventManager->dispatch('cobby_catalog_attribute_export_after', array(
                        'attribute' => $attribute, 'transport' => $transportObject));

                    $attributesArray[] = $transportObject->getData();
                }
            }
        }
        $result = array_merge($noAttr, $noAttrSet, $attributesArray);

        return $result;
    }

    public function getAttribute($attribute){
        $storeLabels = array(
            array(
                'store_id' => 0,
                'label' => $attribute->getFrontendLabel()
            )
        );
        foreach ($attribute->getStoreLabels() as $store_id => $label) {
            $storeLabels[] = array(
                'store_id' => $store_id,
                'label' => $label
            );
        }

        $result = array_merge(
            $attribute->getData(),
            array(
                'scope' => $attribute->getScope(),
                'apply_to' => $attribute->getApplyTo(),
                'store_labels' => $storeLabels
            ));

        return $result;
    }
}