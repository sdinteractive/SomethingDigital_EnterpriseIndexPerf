<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Refresh
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Refresh
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
        return $this->_getFasterAnchorCategoriesSelect($store);
    }
}
