<?php

namespace igk\js\Vue3\Components;

use igk\js\Vue3\System\Html\Dom\VueInitContentItem;
use igk\js\Vue3\ViewScriptModuleRenderer;
use igk\js\Vue3\ViewScriptRenderer;

/**
 * to render core application script 
 * @package igk\js\Vue3\Components 
 * */
class VueApplicationScript extends VueScript
{
    private $m_data;
    private $m_app;

    public function __construct(VueApplicationNode $app, callable $data)
    {
        parent::__construct();
        $this->m_data = $data;
        $this->m_app = $app;
    }
    protected function __AcceptRender($options = null):bool
    {
        if ($this->m_app->getNoRenderedScript()){
            return false;
        }
        if ($g = parent::__AcceptRender($options)) {
            if ($this->m_app->isModuleApp()) {
                $this->setAttribute("type", "module");
            }
        }
        return $g;
    }
    public function getRenderedChilds($options = null)
    {
        $data = null;
        if (($fc = $this->m_data))
            $data = $fc();
        $v_appName = $this->m_app->getApplicationName();

        if ($this->m_app->isModuleApp()) {
            $this["type"] = "module";
            return [new ViewScriptModuleRenderer($this->m_app["id"], $data, $v_appName)];
        }
        $sc = new ViewScriptRenderer(
            $this->m_app["id"],
            $data,
            $v_appName,
            $this->m_app->getUses(),
            $this->m_app->getComponents()
        );
        $sc->def = $this->m_app->getDefs();
        $rd = [];        
        $rd[] = $sc;    
        return $rd;
    }
}
