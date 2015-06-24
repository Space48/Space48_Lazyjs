<?php
class Space48_Lazyjs_Model_PageCache_Processor_Product extends Enterprise_PageCache_Model_Processor_Product
{
    /**
     * Prepare response body before caching
     *
     * @param Zend_Controller_Response_Http $response
     * @return string
     */
    public function prepareContent(Zend_Controller_Response_Http $response)
    {
        $cacheInstance = Enterprise_PageCache_Model_Cache::getCacheInstance();

        /** @var Enterprise_PageCache_Model_Processor */
        $processor = Mage::getSingleton('enterprise_pagecache/processor');
        $countLimit = Mage::getStoreConfig(Mage_Reports_Block_Product_Viewed::XML_PATH_RECENTLY_VIEWED_COUNT);
        // save recently viewed product count limit
        $cacheId = $processor->getRecentlyViewedCountCacheId();
        if (!$cacheInstance->getFrontend()->test($cacheId)) {
            $cacheInstance->save($countLimit, $cacheId, array(Enterprise_PageCache_Model_Processor::CACHE_TAG));
        }
        // save current product id
        $product = Mage::registry('current_product');
        if ($product) {
            $cacheId = $processor->getRequestCacheId() . '_current_product_id';
            $cacheInstance->save($product->getId(), $cacheId, array(Enterprise_PageCache_Model_Processor::CACHE_TAG));
            $processor->setMetadata(self::METADATA_PRODUCT_ID, $product->getId());
            Enterprise_PageCache_Model_Cookie::registerViewedProducts($product->getId(), $countLimit);
        }

        return $this->replaceContentToPlaceholderReplacer(implode('', $response->getBody(true)));
    }
}