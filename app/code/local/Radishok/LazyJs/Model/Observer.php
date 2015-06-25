<?php
/**
 * Radishok_LazyJs
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Radishok
 * @package     Radishok_LazyJs
 * @copyright   Copyright (c) 2014 Radishok
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Radishok_LazyJs_Model_Observer extends Varien_Object{
    const MODULE_ENABLED    = 'radishoklazyjs/settings/active';
    const DEFER_JS          = 'radishoklazyjs/settings/defer_js';
    const DISABLED_PAGES    = 'radishoklazyjs/settings/disabled_pages';
    protected $_allJs       = array();
    protected $_jsScripts   = array();
    
    protected function _getResultScript(){
        if(Mage::getStoreConfig(self::DEFER_JS)){
            $script = 
                '<script type="text/javascript">'
                . "//<![CDATA[
                    ;(function(){
                        if (window.addEventListener)
                            window.addEventListener(\"load\", downloadJSAtOnload, false);
                        else if (window.attachEvent)
                            window.attachEvent(\"onload\", downloadJSAtOnload);
                        else window.onload = downloadJSAtOnload;
                        
                        var scripts = ".Mage::helper('core')->jsonEncode($this->_jsScripts).";
                        function addScript(i){
                            var script = document.createElement('script');
                            script.type = 'text/javascript';
                            if(scripts[i].inline){
                                script.text = scripts[i].text;
                                document.getElementsByTagName(\"head\")[0].appendChild(script);
                                if(++i < scripts.length){
                                    addScript(i);
                                }
                            } else {
                                script.onload = function(){
                                    if(++i < scripts.length){
                                        addScript(i);
                                    }
                                }
                                script.setAttribute('src',scripts[i].src);
                                document.getElementsByTagName(\"head\")[0].appendChild(script);
                            }
                        }
                        function downloadJSAtOnload(){
                            var i = 0;
                            addScript(i);
                        }
                    })();"
                . '//]]>'
                . '</script>';
        } else {
            $script = implode("\n",$this->_allJs)."\n";
        }
        
        return $script;
    }
    protected function _getFormatedInlineJsCode(){
        $code = implode("\n;",$this->_inlineScripts);
        return str_replace(array('<![CDATA[',']]>'), '', $code);
    }
    protected function _replaceCallback($matches) {
        $this->_allJs[] = $matches[0];
        return '';
    }
    protected function _replaceCallbackDefer($matches) {
        $script = array();
        if (isset($matches[2]) && trim($matches[2])) {
            $script['inline'] = true;
            $script['text'] = $matches[2];
        } elseif(strpos($matches[1],' src=')) {
            $src = '';
            preg_match('#(.*?)src="(.*)"#is',$matches[1],$src);
            $script['inline'] = false;
            $script['src'] = $src[2];
        } else {
            Mage::logException(new Exception('Unknown script type :'.$matches[0]));
            return $matches[0];
        }
        $this->_jsScripts[] = $script;
        return '';
    }
    protected function _replaceCallbackMain($matches) {
        if(strpos($matches[0],'<!--[if')===false && strpos($matches[0],'<![endif]-->')===false){
            if(Mage::getStoreConfig(self::DEFER_JS)){
                $callback = array($this, '_replaceCallbackDefer');
            } else {
                $callback = array($this, '_replaceCallback');
            }
            return call_user_func($callback,$matches);
        }
        return $matches[0];
    }
    public function afterToHtml($observer) {
        if ($observer->getBlock()->getNameInLayout() == 'root' && Mage::getStoreConfig(self::MODULE_ENABLED)){
            $request = Mage::app()->getRequest();
            $e = $request->getRequestString();
            $disabled_pages = explode("\n",Mage::getStoreConfig(self::DISABLED_PAGES));
            $object = $observer->getTransport();
            foreach($disabled_pages as $disabled_page){
                if(substr($disabled_page,-1)==='*'){
                    $url = substr($disabled_page,0,-1);
                    if(stripos($e, $url)===0){
                        return;
                    }
                } else {
                    if($e===$disabled_page){
                        return;
                    }
                }
            }
            if(strripos($object->getHtml(),'</body>')===false){
                return;
            }
            
            $result = preg_replace_callback(
                array('#<!--\[if(.*?)\]>(.*?)<!\[endif\]-->|<script(.*?)>(.*?)</script>|<script(.*?)/>#is'), 
                array($this,'_replaceCallbackMain'), $object->getHtml()
            );
            $script = $this->_getResultScript();
            $object->setHtml(str_replace('</body>', $script.'</body>', $result));
        }
    }
}