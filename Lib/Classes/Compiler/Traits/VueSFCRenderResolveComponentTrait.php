<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderResolveComponentTrait.php
// @date: 20230331 04:50:33
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\Components\IVueComponent;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\Dom\HtmlItemBase;

///<summary></summary>
/**
* to register component pass tag to options list
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderResolveComponentTrait{
    public function checkIsResolvableComponent(HtmlItemBase $t, string $tagname, bool & $v_slot=false):bool{
        return $this->isResolvableComponent($tagname) || (($t instanceof IVueComponent) && ($t->isComponent())) || self::AutoFallbackDetectedComponent($t,$tagname, $v_slot);
    }
    public static function AutoFallbackDetectedComponent(HtmlItemBase $t, string $tagname, bool &$v_slot):bool{
        if (!($t['v-pre'])){
            if ((strpos($tagname, ":") === false) && preg_match("/[A-Z\-]/", $tagname)){
                $v_slot = true;
                return true;
            }
        }
        return false;
    }
    /**
     * in order to user element as component 
     * @param mixed $tagname 
     * @return bool 
     */
    public function isResolvableComponent(string $tagname){ 
        $tag = StringUtility::CamelClassName($tagname);
        return key_exists($tag, $this->m_options->components);
    }
    public function resolveComponent($tagname, & $attrs, & $v_slot){        
        return VueSFCUtility::ResolveComponent($tagname, $attrs, $v_slot, $this->m_options);
    }
}