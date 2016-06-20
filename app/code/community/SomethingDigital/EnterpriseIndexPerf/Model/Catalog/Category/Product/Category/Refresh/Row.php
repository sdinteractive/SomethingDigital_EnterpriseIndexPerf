<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Category_Refresh_Row
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Category_Refresh_Row
{
    use SomethingDigital_EnterpriseIndexPerf_Trait_FasterAnchorCategoriesSelect;
    use SomethingDigital_EnterpriseIndexPerf_Trait_PublishDataCheck;

    /**
     * Run reindex
     *
     * @return $this
     * @throws Enterprise_Index_Model_Action_Exception
     */
    public function execute()
    {
        if (!empty($this->_limitationByCategories)) {
            // Sometimes the same category will be in the changelog many times over.
            $this->_limitationByCategories = array_values(array_unique($this->_limitationByCategories));
        }

        return parent::execute();
    }

    /**
     * Publish data from tmp to index
     */
    protected function _publishData()
    {
        return $this->_publishDataWithCheck();
    }

    /**
     * Retrieve a select for reindexing products of anchor categories
     *
     * @param Mage_Core_Model_Store $store
     * @return Varien_Db_Select
     */
    protected function _getAnchorCategoriesSelect(Mage_Core_Model_Store $store)
    {
        $select = $this->_getFasterAnchorCategoriesSelect($store);
        return $select->where('cc.entity_id IN (?)', $this->_limitationByCategories);
    }
}
