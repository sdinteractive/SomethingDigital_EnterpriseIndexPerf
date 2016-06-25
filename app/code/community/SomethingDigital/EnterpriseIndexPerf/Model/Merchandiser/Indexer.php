<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Merchandiser_Indexer extends OnTap_Merchandiser_Model_Merchandiser
{
    public function reindexCategory($categoryId)
    {
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');
        $merchandiserResourceModel->beginTransaction();
        $this->affectCategoryBySmartRule($categoryId);
        $merchandiserResourceModel->commit();
    }

    public function reindexCategoryProducts($categoryId, $productIds)
    {
        $this->reindexCategory($categoryId);
        return true;
    }
}
