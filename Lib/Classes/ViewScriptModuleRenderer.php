<?php

namespace igk\js\Vue3;
igk_require_module(\igk\js\common::class);
use igk\js\common\JSExpression;
use IGK\System\IO\StringBuilder;

/**
 * vue script module rendererer 
 * @package igk\js\Vue3
 */
class ViewScriptModuleRenderer{
    private $data;
    private $id;

    public function __construct($id, $data)
    {
        $this->id = $id;
        $this->data = $data;   
    }
    public function render($options = null){ 
        $sb = new StringBuilder();
        $sb->appendLine("import { createApp } from 'vue';");
        $sb->appendLine("createApp(");
        $sb->appendLine(JSExpression::Stringify($this->data, (object)["objectNotation"=>1]));
        $sb->appendLine(").mount('#".$this->id."');"); 
        return $sb;
    }
}