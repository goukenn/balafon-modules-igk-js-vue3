<?php

namespace igk\js\Vue3\Components;

/**
 * represent vue template
 * @package igk\js\Vue3\Components
 */
class VueSlot extends VueComponent{
    protected $tagname = "slot"; 
    public function __construct(?string $name=null)
    {
        parent::__construct();
        $this['name'] = $name;
    }
}