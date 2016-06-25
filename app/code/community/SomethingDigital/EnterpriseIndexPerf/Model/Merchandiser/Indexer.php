<?php

class SomethingDigital_EnterpriseIndexPerf_Model_Merchandiser_Indexer extends OnTap_Merchandiser_Model_Merchandiser
{
    public function reindexCategory($categoryId)
    {
        /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
        $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');
        /** @var OnTap_Merchandiser_Helper_Data $helper */
        $helper = Mage::helper('merchandiser');

        $categoryValues = $merchandiserResourceModel->getCategoryValues($categoryId);
        if ($categoryValues['smart_attributes'] == '') {
            $categoryValues['ruled_only'] = 0;
        }

        $heroProducts = $this->getHeroProductIds($categoryValues);

        if (empty($heroProducts) && $categoryValues['smart_attributes'] == '') {
            // Nothing to do, this is probably only configured to sort.
            // This is a common configuration in some cases.
            return false;
        }

        $categoryProductsResult = $merchandiserResourceModel->getCategoryProduct($categoryId);
        $existingProducts = array();
        foreach ($categoryProductsResult as $productInfo) {
            $existingProducts[$productInfo['product_id']] = $productInfo['position'];
        }
        asort($existingProducts);

        $newProducts = array();
        $iCounter = 1;

        foreach ($heroProducts as $productId) {
            $newProducts[$productId] = $iCounter++;
        }

        $ruledProductIds = $helper->smartFilter($categoryId, $categoryValues['smart_attributes']);

        $addTo = $helper->newProductsHandler(); // 1= TOP , 2 = BOTTOM
        if ($addTo <= 1) {
            foreach ($ruledProductIds as $productId) {
                // Move any new products to the top.  Leave existing where they are.
                if (!isset($newProducts[$productId]) && !isset($existingProducts[$productId])) {
                    $newProducts[$productId] = $iCounter++;
                }
            }
        }

        if ($categoryValues['ruled_only'] == 0) {
            // These are already in sorted order.  Don't move hero products down, though.
            foreach ($existingProducts as $productId => $position) {
                if (!isset($newProducts[$productId])) {
                    $newProducts[$productId] = $iCounter++;
                }
            }
        }

        // Place any remaining products at the bottom.  This may include existing products when ruled_only.
        foreach ($ruledProductIds as $productId) {
            if (!isset($newProducts[$productId])) {
                $newProducts[$productId] = $iCounter++;
            }
        }

        // Don't apply sorting logic if it's going to be resorted later anyway.
        $shouldApplySort = !in_array($categoryValues['automatic_sort'], array(
            'newest',
            'highest_margin',
        ));
        return $this->applyChanges($categoryId, $shouldApplySort, $existingProducts, $newProducts);
    }

    public function reindexCategoryProducts($categoryId, $productIds)
    {
        // TODO: Make this smarter?  Have to rewrite more for any extra perf.
        return $this->reindexCategory($categoryId);
    }

    protected function getHeroProductIds($categoryValues)
    {
        $skus = explode(',', $categoryValues['heroproducts']);
        $skus = array_filter(array_map('trim', $skus));

        $productIds = array();
        if (!empty($skus)) {
            $productObject = Mage::getModel('catalog/product');
            foreach ($skus as $sku) {
                $productId = $productObject->getIdBySku($sku);
                if ($productId) {
                    // Let's keep it unique.  This will sort by first appearance.
                    $productIds[$productId] = $productId;
                }
            }
        }

        return array_values($productIds);
    }

    protected function applyChanges($categoryId, $shouldApplySort, $existingProducts, $newProducts)
    {
        $deletes = array();
        $inserts = array();

        foreach ($newProducts as $productId => $position) {
            if (!isset($existingProducts[$productId])) {
                // New product: insert.
                $inserts[] = array(
                    'category_id' => $categoryId,
                    'product_id' => $productId,
                    'position' => $position,
                );
            } elseif ($shouldApplySort && $existingProducts[$productId] != $position) {
                // Changed position: delete and then insert.
                $deletes[] = $productId;
                $inserts[] = array(
                    'category_id' => $categoryId,
                    'product_id' => $productId,
                    'position' => $position,
                );
            }
        }
        foreach ($existingProducts as $productId => $position) {
            if (!isset($newProducts[$productId])) {
                // Removed product: delete.
                $deletes[] = $productId;
            }
        }

        if (!empty($deletes) || !empty($inserts)) {
            /** @var OnTap_Merchandiser_Model_Resource_Merchandiser $merchandiserResourceModel */
            $merchandiserResourceModel = Mage::getResourceModel('merchandiser/merchandiser');

            $merchandiserResourceModel->beginTransaction();
            if (!empty($deletes)) {
                $merchandiserResourceModel->deleteSpecificProducts($categoryId, implode(', ', $deletes));
            }
            if (!empty($inserts)) {
                $merchandiserResourceModel->insertMultipleProductsToCategory($inserts);
            }
            $merchandiserResourceModel->commit();

            return true;
        }
        return false;
    }
}
