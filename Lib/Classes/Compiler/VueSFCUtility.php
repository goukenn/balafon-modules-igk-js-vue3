<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCUtility.php
// @date: 20230331 04:48:37
namespace igk\js\Vue3\Compiler;

use IGK\Helper\StringUtility;
use igk\js\Vue3\VueConstants;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
abstract class VueSFCUtility{
    protected static function AddLib(VueSFCRenderNodeVisitorOptions $options, string $name, string $lib = VueConstants::JS_VUE_LIB){       
        if (!isset($options->libraries[$lib])){
            $options->libraries[$lib] = [];
        }
        $options->libraries[$lib][$name] = 1;
    }
/**
     * get build in tag name 
     * @param string $tagname 
     * @return mixed 
     * @throws IGKException 
     */
    public static function GetBuildInName(string $tagname){
        static $buildin = null;
        if (is_null($buildin)){
            $buildin = array_combine(explode('|',strtolower(VueConstants::VUE_BUILDIN_COMPONENT)),
            explode('|',VueConstants::VUE_BUILDIN_COMPONENT ));
        }
        return igk_getv($buildin, strtolower($tagname));
    }
    public static function ResolveComponent($tagname, $options, $meth=VueConstants::VUE_METHOD_RESOLVE_COMPONENT){
        $c = $options;
        $globl = strtolower($c->global_prefix.'c'); 
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