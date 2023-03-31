<?php
namespace igk\js\Vue3;

use igk\js\Vue3\Components\VueScript;
use igk\js\Vue3\Components\VueStyle;
use igk\js\Vue3\Components\VueTemplate;

class VueFileBuilder{
    private $m_template;
    private $m_script;
    private $m_style;

    public function __construct(){

    }
    public function template(){
        if (is_null($this->m_template)){
            $this->m_template = new VueTemplate();
        }
        return $this->m_template;
    }
    public function script(){
        if (is_null($this->m_script)){
            $this->m_script = new VueScript();
        }
        return $this->m_script;
    }
    public function style(){
        if (is_null($this->m_style)){
            $this->m_style = new VueStyle();
        }
        return $this->m_style;
    }

    public function render(){
        $l = array_filter([$this->m_template, $this->m_style, $this->m_script]);
        $out = "";
        $options = igk_xml_create_render_option();
        $options->Indent = true;
        foreach($l as $g){
            $out .= $g->render($options).PHP_EOL;
        } 
        return $out;
    }
}