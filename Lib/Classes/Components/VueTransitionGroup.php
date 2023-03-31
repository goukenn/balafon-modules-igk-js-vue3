<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueTransitionGroup.php
// @date: 20220727 10:28:39
// @desc: 

namespace igk\js\Vue3\Components;

use function igk_resources_gets as __;

/**
 * 
 * @package igk\js\Vue3\Components
 * @property ?string $tag tag name
 * @property ?string $moveClass move class extration
 */
class VueTransitionGroup extends VueTransition{
    protected $tagname = "transition-group";
    
    public function setTag($tag){
        $this["tag"] = $tag;
        return $this;
    }
    public function bindTag($tag){
        $this->vBind("tag", $tag);
        return $this;
    }
    public function setMoveClass($value){
        $this["moveClass"] = $value;
        return $this;
    }
    public function setMode($v){
        igk_die(__("changing {0} is not allowed", "mode"));
    }
    protected function _isAllowedAttribute($attrib){  
        return ! in_array($attrib, ["mode"]);
    }
    public function noCss(){       
        $this->vBind("css", false);
        //igk_wln_e($this->render(), $this->getAttributes()->to_array());
        return $this;
    }
    
}
