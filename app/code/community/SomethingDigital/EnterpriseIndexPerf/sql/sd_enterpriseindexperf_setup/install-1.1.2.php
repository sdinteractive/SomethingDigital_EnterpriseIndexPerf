<?php

/** @var Mage_Core_Model_Resource_Setup $this */
$this->startSetup();

$selectionTable = $this->getTable('bundle/selection');
$indexColumns = array('parent_product_id', 'product_id');
$indexName = $this->getIdxName('bundle/selection', $indexColumns);
$this->getConnection()->addIndex($selectionTable, $indexName, $indexColumns);

$this->endSetup();
