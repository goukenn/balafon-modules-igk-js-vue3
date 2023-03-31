<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueComponentHost.php
// @date: 20230331 19:57:24
namespace igk\js\Vue3\Components;

use IGK\System\Html\Dom\HtmlItemBase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Components
*/
class VueComponentHost extends VueComponent{
    use IHtmlNodeContainerTrait;
    var $host;  
    var $tagname ="vue-component-host";
    public function getCanRenderTag()
    {
        return false;
    }
    function getRenderedChilds($options = null)
    {
        return [$this->host];
    }

    public function __construct(HtmlItemBase $host)
    {
        parent::__construct();
        $this->host = $host;
    }
    public function _add($n, $force = false):bool{
        if ($g = $this->host->_add($n, $force)){
            if ($n instanceof VueComponentHost){
                $n->m_parent = $this->getParentNode();
            }
        }
        return $g;
    }
    public function setAttribute($n, $value){
        $this->host->setAttribute($n, $value);
        return $this;
    } 

    // public function getParentNode():?HtmlItemBase{
    //     return $this->host->getParentNode();
    // }
}