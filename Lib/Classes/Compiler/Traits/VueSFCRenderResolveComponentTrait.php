<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderResolveComponentTrait.php
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
trait VueSFCRenderResolveComponentTrait{
    public function isResolvableComponent($tagname){
        $tag = StringUtility::CamelClassName($tagname);
        return key_exists($tag, $this->m_options->components);
    }
    public function resolveComponent($tagname, & $attrs, & $v_slot){        
        return VueSFCUtility::ResolveComponent($tagname, $attrs, $v_slot, $this->m_options);
    }
}