<?php
namespace igk\js\Vue3\Components;

use igk\js\Vue3\VueConstants;

/**
 * 
 * @package igk\js\Vue3\Components
 */
class VueTemplateScriptNode extends VueComponent{
    var $tagname="script";
    protected function initialize()
    {
        parent::initialize();
        $this->setAttribute("type", VueConstants::TEMPLATE_JS_TYPE);
    }
}