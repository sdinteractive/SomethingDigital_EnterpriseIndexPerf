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
            // Avoid ever having the category visibly empty, if possible.
            $merchandiserResourceModel->beginTransaction();
            /** @var OnTap_Merchandiser_Model_Merchandiser $merchandiser */
            $merchandiser = Mage::getModel('merchandiser/merchandiser');
            $merchandiser->affectCategoryBySmartRule($categoryId);
            $merchandiserResourceModel->commit();

            $merchandiserResourceModel->applySortAction($categoryId);
        }
    }

    /**
     * Apply smart category updates for selected products.
     *
     * @param Varien_Event_Observer $observer Event data
     */
    public function updateProducts(Varien_Event_Observer $observer)
    {
        // TODO: Only reindex products which were modified.
        // $entityIds = $observer->getEvent()->getProductIds();
        $this->updateAllProducts();
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
