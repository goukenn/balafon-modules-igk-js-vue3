<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueInitContentItem.php
// @date: 20230126 15:11:39
namespace igk\js\Vue3\System\Html\Dom;

use igk\js\Vue3\VueConstants;
use IGK\System\Html\Dom\HtmlNode;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Html\Dom
*/
class VueInitContentItem extends HtmlNode{
    var $tagname = 'script';
    
    public function getContent(){
        return '(function(){igk.system.createNS("'.
            VueConstants::CORE_JS_NAMESPACE
            .'",{}); igk.getCurrentScript().remove(); })();'; 
    }
    public function setContent($v){        
    }
}