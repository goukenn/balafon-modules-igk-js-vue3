<?php

namespace igk\js\Vue3\Components;

/**
 * represent vue template
 * @package igk\js\Vue3\Components
 */
class VueRouterLink extends VueComponent{
    protected $tagname = "router-link"; 
    protected function initialize()
    {
        parent::initialize();
        $this["class"]="v-router-link";
    }
}