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
    /**
     * interpolate values
     * @param string $v 
     * @param string $start 
     * @param string $end 
     * @return string 
     */
    public static function InterpolateValue(string $v, string $start='{{', string $end='}}'){
        $start = '{{';
            $end = '}}';
            $ln = strlen($v);
            $tp = 0;
            $pos = 0;
            while(($pos<$ln) && ($pos = strpos($v, $start))!==false){
                //
                $tp = $pos;
                $pos += strlen($start);
                $vv = '';
                while($pos<$ln){
                    $ch = StringUtility::ReadBrank($v, $pos);                  
                    $vv .= $ch;
                    if (strrpos($vv, $end) !== false){
                        $v = igk_str_rm($v, $tp, $pos - $tp + (strlen($end)-1));
                        $vv = trim(igk_str_rm_last($vv, $end));
                        $v = igk_str_insert('${'.$vv.'}', $v, $tp);
                    
                        break;
                    }
                    $pos++;

                }
            }
        return $v;

    }
    public static function CheckBindAttribute($attrib, $key){
        foreach(["v-bind:",":"] as $k){
            $s=$k.$key;
            if (key_exists($s, $attrib)){
                return igk_createobj(['key'=>$s, "value"=>igk_getv($attrib, $s)]);
            }
        }
        return null;
    }
    /**
     * add chain library to options
     * @param VueSFCRenderNodeVisitorOptions $options 
     * @param string $name 
     * @param string $lib 
     * @return void 
     */
    public static function AddLib(VueSFCRenderNodeVisitorOptions $options, string $name, string $lib = VueConstants::JS_VUE_LIB){       
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
    public static function ResolveComponent($tagname,  & $attrs, & $v_slot, $options, $meth=VueConstants::VUE_METHOD_RESOLVE_COMPONENT){
        $c = $options;
        $globl = strtolower($c->global_prefix.'c'); 
        $rname = StringUtility::CamelClassName($tagname);
        $tag = strtolower($c->component_prefix.$rname);
        if (!isset($c->defineGlobal[$globl])){
            $c->defineGlobal[$globl]= 'const '.$globl.'=(q,n)=>(n in q)?((f)=>typeof(f)==\'function\'?f():(()=>f)())(q[n]):'.
            $meth.'(n);';
        }
        if (!isset($c->defineArgs[$tag])){   
            self::AddLib($c, $meth); 
            $c->defineArgs[$tag] = sprintf('const %s=%s(this,\'%s\');', $tag, $globl, $rname);
        }
        if (key_exists($k = 'v-slot', $attrs )){
            $v_slot = igk_getv($attrs, $k);
            unset($attrs[$k]);
        }
        if (!$v_slot && isset($options->components[$rname])){
            $v_slot = true;
        }
        return $tag;
    }
}