<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderBuildInComponentTrait.php
// @date: 20230331 04:18:49
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\VueConstants;
use IGKException;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Compiler\Traits
 */
trait VueSFCRenderBuildInComponentTrait
{
    /**
     * check if build in component
     * @param string $tagname 
     * @return bool 
     */
    protected function isBuildInComponent(string $tagname)
    {
        return in_array(strtolower($tagname), explode('|', strtolower(VueConstants::VUE_BUILDIN_COMPONENT)));
    }
    /**
     * resolve tagname
     * @param mixed $tagname 
     * @return string 
     */
    protected function resolveBuildInComponent(string $tagname, &$attrs, &$v_slot = false, $has_childs = false): string
    {
        $c = $this->m_options;
        if (method_exists($this, $fc = '_resolveBuildIn' . StringUtility::CamelClassName($tagname))) {
            return $this->$fc($tagname, $attrs, $v_slot, $has_childs);
        }
        if ($has_childs && !in_array($tagname, explode('|', "teleport")))
            $v_slot = 1;
        self::AddLib($c, $tag = VueSFCUtility::GetBuildInName($tagname));
        return $tag;
    }
    protected function _resolveBuildInComponent(string $tagname, &$attrs, &$v_slot = false, $has_childs = false)
    {
        // + | handle special component
        $c = $this->m_options;
        if (is_object($g = VueSFCUtility::CheckBindAttribute($attrs, "is"))) {
            unset($attrs[$g->key]);
            self::AddLib($c, VueConstants::VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT);
            return sprintf("%s(%s)", VueConstants::VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT, $g->value);
        }
        igk_die("missing :is in component");
    }
    protected function _resolveBuildInSlot(string $tagname, &$attrs, &$v_slot = false, $has_childs = false)
    {
        // + | handle special component
        $c = $this->m_options;
        if (is_object($g = VueSFCUtility::CheckBindAttribute($attrs, "name"))) {
            unset($attrs[$g->key]);
            self::AddLib($c, VueConstants::VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT);
            return sprintf("%s(%s)", VueConstants::VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT, $g->value);
        }
        igk_die("missing :name in slot = " . $tagname);
    }

    // for <slot name='default></slot>
}
