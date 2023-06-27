<?php

namespace igk\js\Vue3\Components;



class VuePreserveNodeHost extends VueComponent{
    protected $tagname ='vue3:preserve-node-host';
    private $m_host;

    public function setHost($host){
        $this->m_host = $host;
    }
    public function getCanRenderTag(){
        return false;
    }
    function getRenderedChilds($options = null)
    {
        if ($this->m_host){
            return [$this->m_host];
        }
    }
}