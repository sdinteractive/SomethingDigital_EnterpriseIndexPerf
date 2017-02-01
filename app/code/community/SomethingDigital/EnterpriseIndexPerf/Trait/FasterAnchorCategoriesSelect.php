<?php

trait SomethingDigital_EnterpriseIndexPerf_Trait_FasterAnchorCategoriesSelect
{
    use SomethingDigital_EnterpriseIndexPerf_Trait_BuildTempCategoryTree;

    /**
     * Retrieve select for reindex products of non anchor categories
     *
     * @param Mage_Core_Model_Store $store
     * @return Varien_Db_Select
     */
    protected function _getFasterAnchorCategoriesSelect(Mage_Core_Model_Store $store)
    {
        if (!isset($this->_anchorCategoriesSelect[$store->getId()])) {
            /** @var $eavConfig Mage_Eav_Model_Config */
            $eavConfig = $this->_factory->getSingleton('eav/config');
            $isAnchorAttributeId = $eavConfig->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'is_anchor')->getId();
            $statusAttributeId = $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'status')->getId();
            $visibilityAttributeId = $eavConfig->getAttribute(
                Mage_Catalog_Model_Product::ENTITY, 'visibility'
            )->getId();
            $entityTypeId = (int)$eavConfig->getEntityType(Mage_Catalog_Model_Category::ENTITY)->getEntityTypeId();

            $rootCatIds = explode('/', $this->_getPathFromCategoryId($store->getRootCategoryId()));
            array_pop($rootCatIds);

            $this->_makeTempCategoryTreeIndex();

            $select = $this->_connection->select()
                ->from(array('cc' => $this->_getTable('catalog/category')), array())
                ->joinInner(
                    array('cc2' => $this->_getTempCategoryTreeTableName()),
                    'cc2.parent_id = cc.entity_id AND cc.entity_id NOT IN (' . implode(',', $rootCatIds) . ')',
                    array()
                )
                ->joinInner(
                    array('ccp' => $this->_getTable('catalog/category_product')),
                    'ccp.category_id = cc2.child_id',
                    array()
                )
                ->joinInner(
                    array('cpw' => $this->_getTable('catalog/product_website')),
                    'cpw.product_id = ccp.product_id',
                    array()
                )
                ->joinInner(
                    array('cpsd' => $this->_getTable(array('catalog/product', 'int'))),
                    'cpsd.entity_id = ccp.product_id AND cpsd.store_id = 0 AND cpsd.attribute_id = '
                        . $statusAttributeId,
                    array()
                )
                ->joinLeft(
                    array('cpss' => $this->_getTable(array('catalog/product', 'int'))),
                    'cpss.entity_id = ccp.product_id AND cpss.attribute_id = cpsd.attribute_id'
                        . ' AND cpss.store_id = ' . $store->getId(),
                    array()
                )
                ->joinInner(
                    array('cpvd' => $this->_getTable(array('catalog/product', 'int'))),
                    'cpvd.entity_id = ccp.product_id AND cpvd.store_id = 0'
                        . ' AND cpvd.attribute_id = ' . $visibilityAttributeId,
                    array()
                )
                ->joinLeft(
                    array('cpvs' => $this->_getTable(array('catalog/product', 'int'))),
                    'cpvs.entity_id = ccp.product_id AND cpvs.attribute_id = cpvd.attribute_id '
                        . 'AND cpvs.store_id = ' . $store->getId(),
                    array()
                )
                ->joinInner(
                    array('ccad' => $this->_getTable(array('catalog/category', 'int'))),
                    'ccad.entity_id = cc.entity_id AND ccad.store_id = 0'
                        . ' AND ccad.entity_type_id = ' . $entityTypeId
                        . ' AND ccad.attribute_id = ' . $isAnchorAttributeId,
                    array()
                )
                ->joinLeft(
                    array('ccas' => $this->_getTable(array('catalog/category', 'int'))),
                    'ccas.entity_id = cc.entity_id AND ccas.attribute_id = ccad.attribute_id'
                        . ' AND ccas.entity_type_id = ' . $entityTypeId
                        . ' AND ccas.store_id = ' . $store->getId(),
                    array()
                )
                ->where('cpw.website_id = ?', $store->getWebsiteId())
                ->where(
                    $this->_connection->getIfNullSql('cpss.value', 'cpsd.value') . ' = ?',
                    Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                )
                ->where(
                    $this->_connection->getIfNullSql('cpvs.value', 'cpvd.value') . ' IN (?)',
                    array(
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                    )
                )
                ->where(
                    $this->_connection->getIfNullSql('ccas.value', 'ccad.value') . ' = ?',
                    1
                )
                ->columns(
                    array(
                        'category_id'   => 'cc.entity_id',
                        'product_id'    => 'ccp.product_id',
                        'position'      => new Zend_Db_Expr('ccp.position + 10000'),
                        'is_parent'     => new Zend_Db_Expr('0'),
                        'store_id'      => new Zend_Db_Expr($store->getId()),
                        'visibility'    => new Zend_Db_Expr(
                            $this->_connection->getIfNullSql('cpvs.value', 'cpvd.value')
                        )
                    )
                );

            $this->_anchorCategoriesSelect[$store->getId()] = $select;
        }

        return $this->_anchorCategoriesSelect[$store->getId()];
    }

    protected function _getAnchorCategoriesSubSelect($store)
    {
        // Ignore EE 1.14.3.0's subselect slicing - our method is faster.
        return $this->_getFasterAnchorCategoriesSelect($store);
    }

    protected function _getAnchorCategoriesSelectBySubSelect($select, $store)
    {
        // Ignore EE 1.14.3.0's subselect slicing - our method is faster.
        return $select;
    }
}
