<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Merchandiser
{
    /**
     * newestFirst sort action.
     *
     * @param array $params Sorting parameters.
     * @return void
     */
    public function newestFirst($params)
    {
        $catId = $params['catId'];
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');

        $categoryProducts = $merchandiserResourceModel->getCategoryProduct($catId, "product_id DESC");
        $position = 1;
        foreach ($categoryProducts as $product) {
            // Only apply an update if it actually changed.  It's common that this won't change.
            if ($product['position'] != $position) {
                $merchandiserResourceModel->updateProductPosition($catId, $product['product_id'], $position);
            }
            $position++;
        }
    }

    /**
     * moveInStockToTheTop sort action.
     *
     * @param array $params Sorting parameters.
     * @return void
     */
    public function moveInStockToTheTop($params)
    {
        $catId = $params['catId'];
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');
        $outStockProducts = $merchandiserResourceModel->getOutofStockProducts($catId);

        $maxPosition = $merchandiserResourceModel->getMaxInstockPositionFromCategory($catId);

        if (count($outStockProducts)) {
            foreach ($outStockProducts as $outStockProduct) {
                $outStockProductId = $outStockProduct['product_id'];
                ++$maxPosition;
                if ($outStockProduct['position'] != $maxPosition) {
                    $merchandiserResourceModel->updateProductPosition($catId, $outStockProductId, $maxPosition);
                }
            }
        }
    }
}
