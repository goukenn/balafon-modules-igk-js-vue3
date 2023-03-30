<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueTransitionGroup.php
// @date: 20220727 10:28:39
// @desc: use to keep alive component

namespace igk\js\Vue3\Components;
 

/**
 * 
 * @package igk\js\Vue3\Components
 * @property ?string $tag tag name
 * @property ?string $moveClass move class extration
 */
class VueKeepAlive extends VueComponent{
    protected $tagname = "keep-alive";   
    
    public function max($i){
        $this->vBind("max", $i);
        return $this;
    }
    public function include(string $data){
        return $this->setAttribute("include", $data);
    }
    /**
     * 
     * @param null|string $data regex | array
     * @return mixed 
     */
    public function bindInclude(?string $data){
        return $this->vBind("include", $data);
    }
     
}
