<?php

namespace igk\js\Vue3\Components;

use igk\js\common\JSExpression;
use igk\js\Vue3\Libraries\VueLibraryBase;
use igk\js\Vue3\System\Html\Dom\VueInitContentItem;
use IGKException;

use function igk_resources_gets as __;

/**
 * use to create vue node application with option API
 * @package igk\js\Vue3\Components
 */
class VueApplicationNode extends VueComponent{
    protected $tagname = "div";
    protected $appScript;
    /**
     * no script node
     * @var mixed
     */
    protected $noscript;
    private $m_data;
    private $m_isModuleApp;
    private $m_appname;
    private $m_uses;
    private $m_components;
    private $m_def;
    /**
     * enable or not inline script rendering
     * @var bool
     */
    private $m_noScriptRenderer;

    public function noScript(bool $noscript){
        $this->m_noScriptRenderer = $noscript;
        return $this;
    }
    public function getNoRenderedScript(){
        return $this->m_noScriptRenderer;
    }
    /**
     * set custom header script definition
     * @param null|string $value 
     * @return $this 
     */
    public function setDefs(?string $value){
        $this->m_def = $value; 
        return $this;
    }
    /**
     * get custom header script definition
     * @return ?string
     */
    public function getDefs(): ?string{
        return $this->m_def;        
    }
    public function isModuleApp(){
        return $this->m_isModuleApp;
    }
    public function setIsModuleApp($v){
        $this->m_isModuleApp = $v;
        return $this;
    }
    /**
     * append data
     * @param array $option 
     * @return $this 
     */
    public function setData(array $option){
        if (is_array($this->m_data)){
            $this->m_data = array_merge($this->m_data, $option);
        }else{
            $this->m_data = $option;
        }
        return $this;
    }
    public function clearData(){
        $this->m_data = null;
        return $this;
    }
    /**
     * get the stored data
     * @return mixed 
     */
    public function getData(){
        return $this->m_data;
    }
    /**
     * set content
     * @param mixed $value 
     * @return $this 
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }
    /**
     * initialize the component
     * @return void 
     * @throws IGKException 
     */
    protected function initialize()
    {
        parent::initialize();
        $this->appScript = new VueApplicationScript($this, function(){
            return $this->m_data;
        });
        $_self = $this;
        $this->m_components = [];
        $this->noscript = igk_create_node("noscript");
        $this->noscript->strong()->onrenderCallback(function()use($_self){
            $this->Content = __("We're sorry but {0} doesn't work properly without JavaScript enabled. Please enable it to continue.", $_self["id"]);
            return true;        
        });
        $this->m_isModuleApp = igk_environment()->{"vue3.importmap"};
    }
    /**
     * get rendering childs 
     * @param mixed $options 
     * @return array 
     */
    public function getRenderedChilds($options = null)
    {
        $p = parent::getRenderedChilds() ?? [];
        // + | --------------------------------------------------------------------
        // + | append aside - items 
        // + |        
        $aside = [];
        if ($options && (igk_getv($options, 'Document'))){
            $_key = 'vue3.init_js_context';
            if (!igk_getv($options , $_key)){
                $aside[] = new VueInitContentItem; 
                $options->{$_key} = true;
            }
        }
        $aside[] = $this->appScript; 
        $aside[] = $this->noscript;        
        $options->aside = $aside;
        return $p; 
    } 
    /**
     * set application nname
     * @param null|string $appName 
     * @return void 
     */
    public function setApplicationName(?string $appName)   
    {
        if (!is_null($appName)){
            if (!igk_is_identifier($appName)){
                igk_die("name must be a valid identifier");
            }
        }
        $this->m_appname = $appName;
        return $this;
    }
    /**
     * get application name
     * @return mixed 
     */
    public function getApplicationName()   
    {
        return $this->m_appname;
    }
    public function getUses(){
        return $this->m_uses;
    }
    /**
     * uses vue libraries
     * @param mixed|null|string|array $lib 
     * @return $this 
     */
    public function uses($lib){ 
        if (is_null($lib)){
            $this->m_uses =  null; 
        } else {
            if (is_string($lib)){
                $lib = explode(",", $lib);
            }
            if (is_null($this->m_uses)){
                $this->m_uses = [];
            }
            if (is_array($lib)){
                $t = [];     
                $tab = array_unique(array_merge($this->m_uses , $lib), SORT_REGULAR);
                while(count($tab)>0){
                    $q = array_shift($tab);
                    if (!$q)continue;
                    if ($q instanceof VueLibraryBase){
                        $t[$q->getName()] = $q;
                    }else {
                        $t[] = $q;
                    }
                }
                $this->m_uses = $t; 
            } else {
                if ($lib instanceof VueLibraryBase){
                    $this->m_uses[$lib->getName()] = $lib;
                }else{
                    igk_array_set($this->m_uses, VueLibraryBase::VUE, $lib.'');
                }
            }
        }
        return $this;
    }
    /**
     * register components
     * @param string $name 
     * @param mixed|JSExpression|String|Array|object $name_or_component_option 
     * @param mixed|JSExpression|String|Array|object $data 
     * @return void 
     */
    public function component($name_or_component_option, $data=null){
        if (is_null($data)){
            $this->m_components[] = $name_or_component_option;
        }else{
            if (is_numeric($name_or_component_option) || is_string($name_or_component_option)){
                $this->m_components[$name_or_component_option] = $data;
            }else{
                throw new IGKException('key not valid');
            }
        }
        return $this;
    }
    /**
     * set data
     * @param mixed $data 
     * @return $this 
     * @throws IGKException 
     */
    public function data($data){ 
        $this->setData([
            sprintf("data(){ return %s; }", is_string($data) ? $data:  JSExpression::Stringify((object)$data))
        ]);
        return $this;
    }
    /**
     * get registrated components
     * @return null|array 
     */
    public function getComponents(): ?array{
        return $this->m_components;
    }  
}