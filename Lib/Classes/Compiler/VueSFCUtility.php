<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCUtility.php
// @date: 20230331 04:48:37
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\VueConstants;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
abstract class VueSFCUtility{
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
}