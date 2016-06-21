<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Refresh_Row
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Refresh_Row
{
    use SomethingDigital_EnterpriseIndexPerf_Trait_FasterAnchorCategoriesSelect;
    use SomethingDigital_EnterpriseIndexPerf_Trait_PublishDataCheck;

    /**
     * @var int Product count at which to use faster category tree index.
     *
     * At lower product counts, generating the tree index may not be worth it.
     */
    const MIN_PRODUCTS_FOR_CAT_TREE_INDEX = 300;

    /**
     * Run reindex
     *
     * @return $this
     * @throws Enterprise_Index_Model_Action_Exception
     */
    public function execute()
    {
        if (!empty($this->_limitationByProducts)) {
            // Sometimes the same product will be in the changelog many times over.
            $this->_limitationByProducts = array_values(array_unique($this->_limitationByProducts));
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
        if (count($this->_limitationByProducts) < static::MIN_PRODUCTS_FOR_CAT_TREE_INDEX) {
            return parent::_getAnchorCategoriesSelect($store);
        }
        $select = $this->_getFasterAnchorCategoriesSelect($store);
        return $select->where('ccp.product_id IN (?)', $this->_limitationByProducts);
    }
}
