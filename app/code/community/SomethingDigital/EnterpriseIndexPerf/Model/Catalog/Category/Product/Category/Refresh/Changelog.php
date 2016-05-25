<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Category_Refresh_Changelog
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Category_Refresh_Changelog
{
    use SomethingDigital_EnterpriseIndexPerf_Trait_FasterAnchorCategoriesSelect;

    /**
     * Retrieve select for reindex products of non anchor categories
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
