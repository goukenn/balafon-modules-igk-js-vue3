<?php

namespace igk\js\Vue3\Components;

use IGK\System\Html\Dom\Traits\ScopedAttributeTrait;

class VueScript extends VueComponent{
    protected $tagname = "script";
    use ScopedAttributeTrait;
    public function getSetup(){
        return $this->isActive("setup");
    }
    public function setSetup(bool $activeSetup){
        $activeSetup ? $this->activate('setup') : $this->deactivate('setup');
    }
    public function __construct(){
        parent::__construct();        
    }
  
}