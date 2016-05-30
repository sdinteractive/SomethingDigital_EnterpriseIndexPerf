<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Refresh_Changelog
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Refresh_Changelog
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

    /**
     * Reindex products of non anchor categories
     *
     * @param Mage_Core_Model_Store $store
     */
    protected function _reindexNonAnchorCategories(Mage_Core_Model_Store $store)
    {
        // Optimization: don't do anything if there are no categories in the limit.
        // We would add a IN (NULL) anyway, so we would do nothing.
        if (!empty($this->_limitationByProducts)) {
            return parent::_reindexNonAnchorCategories($store);
        }
    }

    /**
     * Reindex products of anchor categories
     *
     * @param Mage_Core_Model_Store $store
     */
    protected function _reindexAnchorCategories(Mage_Core_Model_Store $store)
    {
        // Optimization: don't do anything if there are no categories in the limit.
        // We would add a IN (NULL) anyway, so we would do nothing.
        if (!empty($this->_limitationByProducts)) {
            return parent::_reindexAnchorCategories($store);
        }
    }

    /**
     * Reindex all products to root category
     *
     * @param Mage_Core_Model_Store $store
     */
    protected function _reindexRootCategory(Mage_Core_Model_Store $store)
    {
        // Optimization: don't do anything if there are no categories in the limit.
        // We would add a IN (NULL) anyway, so we would do nothing.
        if (!empty($this->_limitationByProducts)) {
            return parent::_reindexRootCategory($store);
        }
    }
}
