<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Changelog extends Enterprise_Index_Model_Changelog
{
    /**
     * Load changelog ids by metadata.
     *
     * This differs from the parent by adding a DISTINCT.
     *
     * @param null|int $currentVersion Version id to stop at.
     * @return int[]
     */
    public function loadByMetadata($currentVersion = null)
    {
        $select = $this->_connection->select()
            ->from(array('changelog' => $this->_metadata->getChangelogName()), array())
            ->where('version_id >= ?', $this->_metadata->getVersionId())
            ->columns(array($this->_metadata->getKeyColumn()))
            ->distinct();

        if ($currentVersion) {
            $select->where('version_id < ?', $currentVersion);
        }

        return $this->_connection->fetchCol($select);
    }
}
