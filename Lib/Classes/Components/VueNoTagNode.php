<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueNoTagNode.php
// @date: 20230424 16:48:11
// @desc: no tag component for vue 

namespace igk\js\Vue3\Components;


use igk\js\Vue3\Components\VueComponent;


/**
 * vue c omponent 
 * @package igk\js\Vue3\Components
 */
class VueNoTagNode extends VueComponent{
    var $tagname = 'vue:no-tag-component';
    public function getCanRenderTag()
    {
        return false;
    }
    public function __construct()
    {
        parent::__construct();
    }
}