<?php

trait SomethingDigital_EnterpriseIndexPerf_Trait_BuildTempCategoryTree
{
    protected function _makeTempCategoryTreeIndex()
    {
        $temporaryTable = $this->_connection->newTable($this->_getTempCategoryTreeTableName());
        $temporaryTable->addColumn(
            'parent_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true]
        );
        $temporaryTable->addColumn(
            'child_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true]
        );
        $temporaryTable->addIndex('temp_cat_tree_index_pri', array('parent_id', 'child_id'), array(
            'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY
        ));

        $this->_connection->dropTemporaryTable($this->_getTempCategoryTreeTableName());
        $this->_connection->createTemporaryTable($temporaryTable);

        $this->_fillTempCategoryTreeIndex();
    }

    protected function _fillTempCategoryTreeIndex()
    {
        $temporarySelect = $this->_connection->select()
            ->from(array('cc' => $this->_getTable('catalog/category')), array('entity_id'))
            ->joinInner(
                array('cc2' => $this->_getTable('catalog/category')),
                'cc2.path LIKE '
                    . $this->_connection->getConcatSql(
                        array(
                            $this->_connection->quoteIdentifier('cc.path'),
                            $this->_connection->quote('/%')
                        )
                    ),
                array('entity_id')
            );

        $this->_connection->query($this->_connection->insertFromSelect($temporarySelect, $this->_getTempCategoryTreeTableName(), array('parent_id', 'child_id')));
    }

    protected function _getTempCategoryTreeTableName()
    {
        return 'temp_catalog_category_tree_index';
    }
}
