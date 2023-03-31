<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderResolveDynamicComponentTrait.php
// @date: 20230331 04:50:33
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\VueConstants;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderResolveDynamicComponentTrait{
    public function isResolvableComponent($tagname){
        $tag = StringUtility::CamelClassName($tagname);
        return key_exists($tag, $this->m_options->components);
    }
    public function resolveComponent($tagname){
        return VueSFCUtility::ResolveComponent($tagname, $this->m_options, VueConstants::VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT);       
    }
}