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
        }
    }

    /**
     * Apply smart category updates for all products.
     */
    public function updateAllProducts()
    {
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');
        $categoryValues = $merchandiserResourceModel->fetchCategoriesValues();

        // Full reindex: rebuild all categories.
        foreach ($categoryValues as $categoryVal) {
            // Avoid ever having the category visibly empty, if possible.
            $merchandiserResourceModel->beginTransaction();

            /** @var OnTap_Merchandiser_Model_Merchandiser $merchandiser */
            $merchandiser = Mage::getModel('merchandiser/merchandiser');
            $merchandiser->affectCategoryBySmartRule($categoryVal['category_id']);
            $merchandiserResourceModel->applySortAction($categoryVal['category_id']);

            $merchandiserResourceModel->commit();
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
}
