<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Url_Rewrite_Redirect_Refresh_Changelog
    extends Enterprise_UrlRewrite_Model_Index_Action_Url_Rewrite_Redirect_Refresh_Changelog
{
    /**
     * Clean old url rewrites records from table
     *
     * @return $this
     */
    protected function _cleanOldUrlRewrite()
    {
        if (count($this->_getChangedIds()) != 0) {
            return parent::_cleanOldUrlRewrite();
        }
        return $this;
    }

    /**
     * Refresh url rewrites
     *
     * @return $this
     */
    protected function _refreshUrlRewrite()
    {
        if (count($this->_getChangedIds()) != 0) {
            return parent::_refreshUrlRewrite();
        }
        return $this;
    }

    /**
     * Refresh redirect to url rewrite relations
     *
     * @return $this
     */
    protected function _refreshRelation()
    {
        if (count($this->_getChangedIds()) != 0) {
            return parent::_refreshRelation();
        }
        return $this;
    }
}