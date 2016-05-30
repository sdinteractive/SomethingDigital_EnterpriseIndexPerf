<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Catalog_Category_Product_Category_Refresh_Changelog
    extends Enterprise_Catalog_Model_Index_Action_Catalog_Category_Product_Category_Refresh_Changelog
{
    use SomethingDigital_EnterpriseIndexPerf_Trait_FasterAnchorCategoriesSelect;

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

    /**
     * Reindex products of non anchor categories
     *
     * @param Mage_Core_Model_Store $store
     */
    protected function _reindexNonAnchorCategories(Mage_Core_Model_Store $store)
    {
        // Optimization: don't do anything if there are no categories in the limit.
        // We would add a IN (NULL) anyway, so we would do nothing.
        if (!empty($this->_limitationByCategories)) {
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
        if (!empty($this->_limitationByCategories)) {
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
        if (!empty($this->_limitationByCategories)) {
            return parent::_reindexRootCategory($store);
        }
    }
}
