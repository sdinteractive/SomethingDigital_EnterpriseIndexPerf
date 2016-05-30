<?php

trait SomethingDigital_EnterpriseIndexPerf_Trait_PublishDataCheck
{
    /**
     * Publish data from tmp to index
     */
    protected function _publishDataWithCheck()
    {
        $selectCount = $this->_connection->select()
            ->from($this->_getMainTmpTable())
            ->columns('COUNT(category_id)');
        $rows = $this->_connection->fetchOne($selectCount);

        // If there's nothing to insert, let's not dirty the query cache.
        if ($rows != 0) {
            parent::_publishData();
        }
    }
}
