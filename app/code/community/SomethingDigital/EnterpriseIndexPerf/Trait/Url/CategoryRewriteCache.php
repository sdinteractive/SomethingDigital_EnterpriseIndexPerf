<?php

trait SomethingDigital_EnterpriseIndexPerf_Trait_Url_CategoryRewriteCache
{
    protected $_cachedStoreSpecificData = array();

    /**
     * Set store specific data to category
     *
     * @param Mage_Catalog_Model_Category $category
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Category
     */
    protected function _setStoreSpecificData(Mage_Catalog_Model_Category $category, Mage_Core_Model_Store $store)
    {
        $category->setStoreId($store->getId());
        // Load preloaded store-specific data.
        $storeCategoryData = $this->_getCachedStoreSpecificData($category, $store);
        if ($storeCategoryData) {
            foreach ($storeCategoryData->toArray() as $key => $data) {
                $category->setData($key, $data);
            }
        }
        $rewrites = $this->_categoryRelation->loadByCategory($category);
        $category->setRequestPath($rewrites->getRequestPath());
        return $category;
    }

    /**
     * Recursively index categories tree
     *
     * @param Mage_Catalog_Model_Category $category
     * @param Mage_Core_Model_Store $store
     * @return Enterprise_Catalog_Model_Index_Action_Url_Rewrite_Category_Refresh
     */
    protected function _indexCategoriesRecursively(Mage_Catalog_Model_Category $category, Mage_Core_Model_Store $store)
    {
        $category = $this->_setStoreSpecificData($category, $store);
        //skip root and default categories
        if ($category->getLevel() > 1) {
            $category = $this->_formatUrlKey($category);
            if ($category->getUrlKey()) {
                $category = $this->_reindexCategoryUrlKey($category, $store);
            }
        }

        if ($category->getChildrenCount()) {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
            $categoryCollection = $category->getChildrenCategoriesWithInactive();
            $categoryCollection->setDisableFlat(true);
            // Preload child category data as an array.
            $this->_preloadStoreSpecificData($categoryCollection, $store);
            /** @var Mage_Catalog_Model_Category $childCategory */
            foreach ($categoryCollection as $childCategory) {
                $childCategory->setUrlKey($category->getUrlKey());
                $childCategory->setParentUrl($category->getRequestPath());
                $this->_indexCategoriesRecursively($childCategory, $store);
            }
        }

        return $this;
    }

    protected function _preloadStoreSpecificData($categories, $store)
    {
        $storeId = $store->getId();
        $cached = &$this->_cachedStoreSpecificData[$storeId];
        if (!isset($cached)) {
            $cached = array();
        }

        $categoryIds = array();
        foreach ($categories as $category) {
            $categoryIds[] = is_object($category) ? $category->getId() : $category;
        }
        $cached += $this->_urlResource->getCategories($categoryIds, $storeId);
    }

    protected function _getCachedStoreSpecificData($category, $store)
    {
        $storeId = $store->getId();
        $categoryId = $category->getId();
        $cached = &$this->_cachedStoreSpecificData[$storeId];
        if (empty($cached) || !isset($cached[$categoryId])) {
            return $this->_urlResource->getCategory($categoryId, $storeId);
        }
        return $cached[$categoryId];
    }
}
