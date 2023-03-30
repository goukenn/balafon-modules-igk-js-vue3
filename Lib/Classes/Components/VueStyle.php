<?php

namespace igk\js\Vue3\Components;

use IGK\System\Html\Dom\Traits\ScopedAttributeTrait;

class VueStyle extends VueComponent{
    protected $tagname = "style";
    use ScopedAttributeTrait; 
}