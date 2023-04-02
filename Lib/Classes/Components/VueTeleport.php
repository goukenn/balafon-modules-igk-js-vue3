<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueTransitionGroup.php
// @date: 20220727 10:28:39
// @desc: use to keep alive component

namespace igk\js\Vue3\Components;
 

/**
 * 
 * @package igk\js\Vue3\Components
 * @property ?string $to target
 * @property ?bool $disabled 
 */
class VueTeleport extends VueComponent{
    protected $tagname = "teleport";   
    
    public function setTo(string $target){
        $this["to"] = $target;
        return $this;
    }
    protected function initialize()
    {
        parent::initialize();
        $this->setTo("body");
    }  
    public function __construct()
    {
        parent::__construct();
    }   
}
