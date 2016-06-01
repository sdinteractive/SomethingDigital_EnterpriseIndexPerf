<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Bundle_Price_Refresh
    extends Mage_Bundle_Model_Resource_Indexer_Price
{
    const ENTITY_CHUNK_SIZE = 200;

    /**
     * Calculate bundle product selections price by product type
     *
     * @param int $priceType
     * @return Mage_Bundle_Model_Resource_Indexer_Price
     */
    protected function _calculateBundleSelectionPrice($priceType)
    {
        $write = $this->_getWriteAdapter();

        if ($priceType == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {

            $selectionPriceValue = $write->getCheckSql(
                'bsp.selection_price_value IS NULL',
                'bs.selection_price_value',
                'bsp.selection_price_value'
            );
            $selectionPriceType = $write->getCheckSql(
                'bsp.selection_price_type IS NULL',
                'bs.selection_price_type',
                'bsp.selection_price_type'
            );
            $priceExpr = new Zend_Db_Expr(
                $write->getCheckSql(
                    $selectionPriceType . ' = 1',
                    'ROUND(i.price * (' . $selectionPriceValue . ' / 100),2)',
                    $write->getCheckSql(
                        'i.special_price > 0 AND i.special_price < 100',
                        'ROUND(' . $selectionPriceValue . ' * (i.special_price / 100),2)',
                        $selectionPriceValue
                    )
                ) . '* bs.selection_qty'
            );

            $tierExpr = $write->getCheckSql(
                'i.base_tier IS NOT NULL',
                $write->getCheckSql(
                    $selectionPriceType .' = 1',
                    'ROUND(i.base_tier - (i.base_tier * (' . $selectionPriceValue . ' / 100)),2)',
                    $write->getCheckSql(
                        'i.tier_percent > 0',
                        'ROUND(' . $selectionPriceValue
                        . ' - (' . $selectionPriceValue . ' * (i.tier_percent / 100)),2)',
                        $selectionPriceValue
                    )
                ) . ' * bs.selection_qty',
                'NULL'
            );

            $groupExpr = $write->getCheckSql(
                'i.base_group_price IS NOT NULL',
                $write->getCheckSql(
                    $selectionPriceType .' = 1',
                    $priceExpr,
                    $write->getCheckSql(
                        'i.group_price_percent > 0',
                        'ROUND(' . $selectionPriceValue
                        . ' - (' . $selectionPriceValue . ' * (i.group_price_percent / 100)),2)',
                        $selectionPriceValue
                    )
                ) . ' * bs.selection_qty',
                'NULL'
            );
            $priceExpr = new Zend_Db_Expr(
                $write->getCheckSql("{$groupExpr} < {$priceExpr}", $groupExpr, $priceExpr)
            );
        } else {
            $priceExpr = new Zend_Db_Expr(
                $write->getCheckSql(
                    'i.special_price > 0 AND i.special_price < 100',
                    'ROUND(idx.min_price * (i.special_price / 100), 2)',
                    'idx.min_price'
                ) . ' * bs.selection_qty'
            );
            $tierExpr = $write->getCheckSql(
                'i.base_tier IS NOT NULL',
                'ROUND(idx.min_price * (i.base_tier / 100), 2)* bs.selection_qty',
                'NULL'
            );
            $groupExpr = $write->getCheckSql(
                'i.base_group_price IS NOT NULL',
                'ROUND(idx.min_price * (i.base_group_price / 100), 2)* bs.selection_qty',
                'NULL'
            );
            $groupPriceExpr = new Zend_Db_Expr(
                $write->getCheckSql(
                    'i.base_group_price IS NOT NULL AND i.base_group_price > 0 AND i.base_group_price < 100',
                    'ROUND(idx.min_price - idx.min_price * (i.base_group_price / 100), 2)',
                    'idx.min_price'
                ) . ' * bs.selection_qty'
            );
            $priceExpr = new Zend_Db_Expr(
                $write->getCheckSql("{$groupPriceExpr} < {$priceExpr}", $groupPriceExpr, $priceExpr)
            );
        }

        $select = $write->select()
            ->from(
                array('i' => $this->_getBundlePriceTable()),
                array('entity_id', 'customer_group_id', 'website_id')
            )
            ->join(
                array('bo' => $this->getTable('bundle/option')),
                'bo.parent_id = i.entity_id',
                array('option_id')
            )
            ->join(
                array('bs' => $this->getTable('bundle/selection')),
                'bs.option_id = bo.option_id',
                array('selection_id')
            )
            ->join(
                array('e' => $this->getTable('catalog/product')),
                'bs.product_id = e.entity_id AND e.required_options=0',
                array()
            )
            ->where('i.price_type=?', $priceType)
            ->columns(array(
                'group_type'    => $write->getCheckSql(
                    "bo.type = 'select' OR bo.type = 'radio'",
                    '0',
                    '1'
                ),
                'is_required'   => 'bo.required',
                'price'         => $priceExpr,
                'tier_price'    => $tierExpr,
                'group_price'   => $groupExpr,
            ));

        if ($priceType == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
            $select->joinLeft(
                array('bsp' => $this->getTable('bundle/selection_price')),
                'bs.selection_id = bsp.selection_id AND bsp.website_id = i.website_id',
                array('')
            );
        } else {
            // Using an inner join here will sometimes cause MySQL 5.6 to plan the execution around idx.
            // When it does that, the query may take hours and lock many rows of data.
            // We switch to LEFT and HAVING in order to force it to build the query around another table.
            // (this issue may likely exist in other versions of MySQL, such as 5.7, too.)
            $select->joinLeft(
                array('idx' => $this->getIdxTable()),
                'bs.product_id = idx.entity_id AND i.customer_group_id = idx.customer_group_id'
                . ' AND i.website_id = idx.website_id',
                array()
            )->having('price IS NOT NULL');
        }

        $selectEntityIds = $write->select()
            ->distinct()
            ->from($this->_getBundlePriceTable(), array('entity_id'));
        $entityIds = $write->fetchCol($selectEntityIds);

        $entityIdChunks = array_chunk($entityIds, static::ENTITY_CHUNK_SIZE);
        foreach ($entityIdChunks as $entityIds) {
            $chunkSelect = clone $select;
            $chunkSelect->where('i.entity_id IN (?)', $entityIds);
            $write->query($chunkSelect->insertFromSelect($this->_getBundleSelectionTable()));
        }

        return $this;
    }
}