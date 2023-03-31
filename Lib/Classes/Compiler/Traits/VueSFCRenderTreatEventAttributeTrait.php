<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderTreatEventAttributeTrait.php
// @date: 20230331 18:54:39
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\VueConstants;
use IGK\System\Regex\Replacement;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderTreatEventAttributeTrait{
    use VueSFCRenderTreatGetExpressionValueTrait;
    public static function TreatEventAttribute($options, $key, $v, $context):?string{
        $rp = new Replacement;
        $modifiers = [];
        $rp->addCallable("/\.(?P<name>[^\. ]+)/", function($n)use(& $modifiers){
            $modifiers[] = $n['name'];
            return '';
        });
        $rp->addCallable("/^(v-on)?:/", function($n)use($modifiers){   
            return '';
        });
        $key = $rp->replace($key);
        $eventName = 'on'.StringUtility::CamelClassName($key);
        $format = '%s';
        $modifiers = array_filter($modifiers);
        $modifiers = array_map('trim', $modifiers);
        if ($modifiers){
            VueSFCUtility::AddLib($options, VueConstants::VUE_METHOD_WITH_MODIFIERS);
            $format =VueConstants::VUE_METHOD_WITH_MODIFIERS. '(()=>{%s},['."'".implode("','",$modifiers)."'])";
        } 
        return ( self::_GetKey($eventName) . ":" . sprintf($format, self::_GetBindingExpressionValue($v, $context)));
    }
    
}