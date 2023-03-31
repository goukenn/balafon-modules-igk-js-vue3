<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderBuildInComponentTrait.php
// @date: 20230331 04:18:49
namespace igk\js\Vue3\Compiler\Traits;

use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\VueConstants;
use IGKException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderBuildInComponentTrait{
    /**
     * check if build in component
     * @param string $tagname 
     * @return bool 
     */
    protected function isBuildInComponent(string $tagname){
        return in_array(strtolower($tagname), explode('|', strtolower(VueConstants::VUE_BUILDIN_COMPONENT)));
    }
    /**
     * resolve tagname
     * @param mixed $tagname 
     * @return string 
     */
    protected function resolveBuildInComponent(string $tagname, & $v_slot=false):string{
        $c = $this->m_options;
        self::AddLib($c, $tag= VueSFCUtility::GetBuildInName($tagname));        
        return $tag;
    }
    
}