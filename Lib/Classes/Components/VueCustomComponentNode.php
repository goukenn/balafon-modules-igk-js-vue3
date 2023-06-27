<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueCustomComponentNode.php
// @date: 20230101 01:49:00
// @desc: 
namespace igk\js\Vue3\Components;
// + | --------------------------------------------------------------------
// + | 
// + |
/**
 * use for custom component declaration 
 * @package igk\js\Vue3\Components
 */
class VueCustomComponentNode extends VueComponent implements IVueComponent{ 
    public function isComponent():bool{
        return true;
    }
    public function __construct(string $component){
        parent::__construct($component);
    }
    
}