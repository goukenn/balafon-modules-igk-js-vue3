<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCTemplate.php
// @date: 20230418 13:14:58
namespace igk\js\Vue3\System\Html\Dom;

use igk\js\Vue3\Components\VueComponent;

///<summary></summary>
/**
* represent a vue single file component template node
* @package igk\js\Vue3\System\Html\Dom
*/
class VueSFCTemplate extends VueComponent{
    var $tagname='template';
    /**
     * support close tag
     * @return bool 
     */
    public function closeTag(){
        return true;
    }
    public function __construct()
    {
        parent::__construct();
    } 
}