<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueComponentNode.php
// @date: 20230330 23:47:50
// @desc: 

namespace igk\js\Vue3\Components;

/**
 * represent a built-in 'vue3' 'component' node
 * @package igk\js\Vue3\Components
 */
class VueComponentNode extends VueComponent{ 
    protected $tagname = 'component';
    public function __construct(){
        parent::__construct();
    }
}