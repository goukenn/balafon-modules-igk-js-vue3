<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueSFCScriptComponent.php
// @date: 20230607 13:54:22
// @desc: 

namespace igk\js\Vue3\Components;

use igk\js\Vue3\Components\VueComponent;

class VueSFCStyleComponent extends VueComponent{
    public function getcanLoadContent($value):bool{
        return false;
    }
    protected $tagname = 'style';
}