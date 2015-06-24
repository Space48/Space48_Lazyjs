<?php
class Space48_Lazyjs_Model_PageCache_Processor_Category extends Enterprise_PageCache_Model_Processor_Category
{
    /**
     * Prepare response body before caching
     *
     * @param Zend_Controller_Response_Http $response
     * @return string
     */
    public function prepareContent(Zend_Controller_Response_Http $response)
    {
        return $this->replaceContentToPlaceholderReplacer(implode('', $response->getBody(true)));
    }
}