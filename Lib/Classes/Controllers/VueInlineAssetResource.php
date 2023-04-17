<?php

namespace igk\js\Vue3\Controllers;

use IGK\Controllers\BaseController;
use IGK\System\Html\IHtmlGetValue;
use IGKResourceUriResolver;

class VueInlineAssetResource implements IHtmlGetValue{
    var $value;
    var $controller;

    public function __construct(string $value, ?BaseController $ctrl=null)
    {
        $this->value = $value;
        $this->controller = $ctrl;
    }
    public function getValue($options=null){
        if ($this->controller){
            $dir = $this->controller->getVueAppDir();
            $file = $dir."/".$this->value;
            return IGKResourceUriResolver::getInstance()->resolveOnly($file);
        }
        return "::".$this->value;
    }
}