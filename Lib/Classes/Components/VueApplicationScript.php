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
    /**
     * 
     * @var callable
     */
    private $m_data;
    /**
     * 
     * @var VueApplicationNode
     */
    private $m_app;

    public function __construct(VueApplicationNode $app, callable $data)
    {
        $this->m_data = $data;
        $this->m_app = $app;
        parent::__construct();
    }
    protected function initialize()
    {
        parent::initialize();
        $this['type'] = 'module';
        $this['async'] = true;
        $this->activate('defer');

    }
    protected function __AcceptRender($options = null):bool
    {
        if ($this->m_app->getNoRenderedScript()){
            return false;
        }        
        $this['async'] = $this->m_app->getAsyncScript() ? true : null; 
        return parent::__AcceptRender($options);
    }
    public function getRenderedChilds($options = null)
    {
        $data = null;
        if (($fc = $this->m_data))
            $data = $fc();
        $v_appName = $this->m_app->getApplicationName();

        if ($this->m_app->isModuleApp()) {
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
        $sc->sharedUses = $this->m_app->getSharedUses(); 
        if ($this['async']){
            $sc = new AsyncRender($sc);  
        }
        return [$sc];
    }
}
class AsyncRender{
    var $sc;
    public function __construct($sc){
        $this->sc = $sc;
    }
    public function render($options=null){
        return sprintf("igk.ready(async function(){%s});", $this->sc->render($options).'');
        // return  $this->sc->render($options);
    }
}
