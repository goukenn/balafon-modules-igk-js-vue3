<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueLibraryVar.php
// @date: 20230303 08:26:44
namespace igk\js\Vue3\Libraries;

use igk\js\Vue3\VueConstants;
use IGK\System\Html\HtmlRendererOptions;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Libraries
*/
class VueLibraryVar extends VueLibrary{
    var $varName;

    var $declarationListener;

    public function setDeclarationListener(?callable $listener){
        $this->declarationListener = $listener;
    }
    /**
     * get js variable name
     * @return string 
     */
    public function getVarName(){
        return $this->varName;
    }
    public function __construct(string $var_name, string $name, ?string $module="Vue")
    {
        $this->varName = $var_name;
        parent::__construct($name, $module);
    }
    static function & _pGetVueLibraryFromOptions(& $options){
        $key = VueConstants::LIB_OPTIONS; 
        $r = null;
        if ($options instanceof HtmlRendererOptions){
            $r = & $options->getRef($key);
            if (!$r){
                $r = [];
                $options->setRef($key, $r);
            }
        } else{

            if (!property_exists($options, $key)){
                $options->{$key} = []; 
            }
            $r = & $options->{$key}; 
        }
        return $r;
    }
    public function useLibrary($options=null):array{
 
        return [$this->varName,null];
    }
    public function render($option = null): ?string
    {
        $lib = & self::_pGetVueLibraryFromOptions($option);
        $g = $this->m_module;
        if (!isset($lib[$g])){
            $lib[$g] = new VueLibraryReference();
        }
        $lib[$g]->ref($this->getName());
        if ($fc = $this->declarationListener){
            return $fc($this->varName, $this->m_name, $option);
        } 
        return null;
    }
}
