<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Url_Rewrite_Redirect_Refresh
    extends Enterprise_UrlRewrite_Model_Index_Action_Url_Rewrite_Redirect_Refresh
{
    const REWRITE_CHUNK_SIZE = 5000;

    /**
     * Clean old url rewrites records from table
     *
     * @return Enterprise_UrlRewrite_Model_Index_Action_Url_Rewrite_RefreshAbstract
     */
    protected function _cleanOldUrlRewrite()
    {
        $selects = $this->_prepareSelectsByRange($this->_getCleanOldUrlRewriteSelect(), 'url_rewrite_id');
        foreach ($selects as $select) {
            $this->_connection->query($select->deleteFromSelect('ur'));
        }
        return $this;
    }

    /**
     * Refresh url rewrites
     *
     * @return Enterprise_UrlRewrite_Model_Index_Action_Url_Rewrite_RefreshAbstract
     */
    protected function _refreshUrlRewrite()
    {
        $selects = $this->_prepareSelectsByRange($this->_getUrlRewriteSelectSql(), 'redirect_id');
        foreach ($selects as $select) {
            $insert = $this->_connection->insertFromSelect($select,
                $this->_getTable('enterprise_urlrewrite/url_rewrite'),
                array(
                    'request_path',
                    'target_path',
                    'guid',
                    'is_system',
                    'identifier',
                    'value_id',
                    'store_id',
                    'entity_type'
                )
            );

            $insert .= sprintf(' ON DUPLICATE KEY UPDATE %1$s = %1$s + 1',
                $this->_getTable('enterprise_urlrewrite/url_rewrite') . '.inc');

            $this->_connection->query($insert);
        }
        return $this;
    }

    /**
     * Return selects cut by min and max
     *
     * @param Varien_Db_Select $select
     * @param string $field
     * @param int $range
     * @return array
     */
    protected function _prepareSelectsByRange(Varien_Db_Select $select, $field, $range = self::REWRITE_CHUNK_SIZE)
    {
        return $this->_connection->selectsByRange($field, $select, $range);
    }
}