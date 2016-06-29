<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Observer_Pagecache
{
    /**
     * Clean cache for affected products
     *
     * @param Varien_Event_Observer $observer Event data
     */
    public function cleanProductsCacheAfterPartialReindex(Varien_Event_Observer $observer)
    {
        // Only if the PageCache is enabled.
        if (!Mage::helper('core')->isModuleEnabled('Enterprise_PageCache')) {
            return;
        }

        $entityIds = $observer->getEvent()->getProductIds();
        if (is_array($entityIds) && !empty($entityIds)) {
            $this->_cleanProductsCache(Mage::getModel('catalog/product'), $entityIds);
        }
    }

    /**
     * Clean cache by specified product and its ids
     *
     * @param Mage_Catalog_Model_Product $entity Base entity model
     * @param int[] $ids Product IDs
     */
    protected function _cleanProductsCache(Mage_Catalog_Model_Product $entity, array $ids)
    {
        $cacheTags = array();
        $ids = array_unique($ids);
        foreach ($ids as $entityId) {
            $entity->setId($entityId);
            $productTags = $entity->getCacheIdTagsWithCategories();
            foreach ($productTags as $tag) {
                $cacheTags[$tag] = $tag;
            }
        }
        if (!empty($cacheTags)) {
            $cacheTags = array_values($cacheTags);
            Enterprise_PageCache_Model_Cache::getCacheInstance()->clean($cacheTags);
        }
    }
}
