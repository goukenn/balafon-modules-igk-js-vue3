<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderResolveComponentTrait.php
// @date: 20230331 04:50:33
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\Vue3\VueConstants;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderResolveComponentTrait{
    public function isResolvableComponent($tagname){
        $tag = StringUtility::CamelClassName($tagname);
        return key_exists($tag, $this->m_options->components);
    }
    public function resolveComponent($tagname){
        $c = $this->m_options;
        $globl = strtolower($c->global_prefix.'c');
        $meth = VueConstants::VUE_METHOD_RESOLVE_COMPONENT;
        $rname = StringUtility::CamelClassName($tagname);
        $tag = strtolower($c->component_prefix.$rname);
        if (!isset($c->defineGlobal[$globl])){
            $c->defineGlobal[$globl]= 'const '.$globl.'=(q,n)=>(n in q)?((f)=>typeof(f)=="function"?f():(()=>f)())(q[n]):'.
            $meth.'(n);';
        }
        if (!isset($c->defineArgs[$tag])){   
            self::AddLib($c, $meth); 
            $c->defineArgs[$tag] = sprintf('const %s=%s(this,\'%s\');', $tag, $globl, $rname);
        }
        return $tag;
    }
}