<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderTreatDirectiveAttributeTrait.php
// @date: 20230331 18:54:39
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\VueConstants;
use IGK\System\Regex\Replacement;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderTreatDirectiveAttributeTrait{ 
    use VueSFCRenderTreatGetExpressionValueTrait;
    public static function TreatDirectiveAttribute(&$directive , $options, $key, $v, $ch, $context):?string{
        $rp = new Replacement;
        $modifiers = [];
        $rp->addCallable("/\.(?P<name>[^\. ]+)/", function($n)use(& $modifiers){
            $modifiers[] = $n['name'];
            return '';
        });
        $rp->addCallable("/^v-(?P<name>[^\. ]+):/", function($n)use(& $name){   
            $name = $n['name'];
            return '';
        });
        VueSFCUtility::AddLib($options, VueConstants::VUE_METHOD_WITH_DIRECTIVES);
        $key = $rp->replace($key); 
        $modifiers = array_filter($modifiers);
        $modifiers = array_map('trim', $modifiers);
        if ($modifiers){
            $modifiers = array_fill_keys($modifiers, true);            
        } 
        $v = self::_GetBindingExpressionValue($v, $context);
        array_push($directive, [$name, $v, $key, JSExpression::Stringify((object)$modifiers)]);
        return null;
    }
    
}