<?php
class Space48_Lazyjs_Model_Observer extends Varien_Object
{
    const MODULE_ENABLED    = false;

    protected $_allJs       = array();


    public function beforeSendResponse($output)
    {
        if (!self::MODULE_ENABLED) {
            return $output;
        }

        // do not process partial outputs (e.g. ajax requests)
        if (strripos($output, '</body>') === false) {
            return $output;
        }

        $result = preg_replace_callback(
            array('#<!--\[if(.*?)\]>(.*?)<!\[endif\]-->|<script(.*?)>(.*?)</script>|<script(.*?)/>#is'),
            array($this,'_replaceCallbackMain'), $output
        );

        $script = $this->_getResultScript();

        return str_replace('</body>', $script.'</body>', $result);
    }

    protected function _replaceCallbackMain($matches)
    {
        if (strpos($matches[0],'<!--[if')===false && strpos($matches[0],'<![endif]-->')===false) {
            $callback = array($this, '_replaceCallback');

            return call_user_func($callback, $matches);
        }

        return $matches[0];
    }

    protected function _replaceCallback($matches)
    {
        $this->_allJs[] = $matches[0];

        return '';
    }

    protected function _getResultScript()
    {
        $script = implode("\n", $this->_allJs) . "\n";

        return $script;
    }

    /*
    protected function _getFormatedInlineJsCode()
    {
        $code = implode("\n;", $this->_inlineScripts);

        return str_replace(array('<![CDATA[', ']]>'), '', $code);
    }
    */
}