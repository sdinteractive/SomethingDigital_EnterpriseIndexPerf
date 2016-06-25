<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Observer_Merchandiser
{
    /**
     * Run base cron only when necessary.
     */
    public function reindexCron()
    {
        // Following indexes only works when they're enabled.
        // But when they are, we'll use events.
        if (!$this->isFlatIndexerEnabled()) {
            /** @var OnTap_Merchandiser_Model_Adminhtml_Observer $observer */
            $observer = Mage::getSingleton('merchandiser/adminhtml_observer');
            $observer->reindexCron();
        } elseif (Mage::helper('merchandiser')->rebuildOnCron()) {
            // Let's still resort on cron, since sorting may be affected by sales, or etc.
            /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
            $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');

            // Full reindex: rebuild all categories.
            $categoryIds = $this->getSmartCategoryIds();
            foreach ($categoryIds as $categoryId) {
                $merchandiserResourceModel->applySortAction($categoryId);
            }
        }
    }

    /**
     * Apply smart category updates for all products.
     */
    public function updateAllProducts()
    {
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');

        // Full reindex: rebuild all categories.
        $categoryIds = $this->getSmartCategoryIds();
        foreach ($categoryIds as $categoryId) {
            /** @var SomethingDigital_EnterpriseIndexPerf_Model_Merchandiser_Indexer $merchandiser */
            $merchandiser = Mage::getModel('sd_enterpriseindexperf/merchandiser_indexer');
            $changed = $merchandiser->reindexCategory($categoryId);

            if ($changed) {
                $merchandiserResourceModel->applySortAction($categoryId);
            }
        }
    }

    /**
     * Apply smart category updates for selected products.
     *
     * @param Varien_Event_Observer $observer Event data
     */
    public function updateProducts(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();

        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');

        // Partial reindex: check each category for these products.
        $categoryIds = $this->getSmartCategoryIds();
        foreach ($categoryIds as $categoryId) {
            /** @var SomethingDigital_EnterpriseIndexPerf_Model_Merchandiser_Indexer $merchandiser */
            $merchandiser = Mage::getModel('sd_enterpriseindexperf/merchandiser_indexer');
            $changed = $merchandiser->reindexCategoryProducts($categoryId, $productIds);

            if ($changed) {
                $merchandiserResourceModel->applySortAction($categoryId);
            }
        }
    }

    protected function isFlatIndexerEnabled()
    {
        return Mage::getStoreConfigFlag('catalog/frontend/flat_catalog_product');
    }

    protected function getSmartCategoryIds()
    {
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');
        $categoryValues = $merchandiserResourceModel->fetchCategoriesValues();

        $categoryIds = array();
        foreach ($categoryValues as $categoryVal) {
            $categoryIds[] = $categoryVal['category_id'];
        }

        return $categoryIds;
    }
}
