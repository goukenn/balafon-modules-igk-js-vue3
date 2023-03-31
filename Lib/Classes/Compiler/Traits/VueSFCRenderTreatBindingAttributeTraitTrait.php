<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderTreatBindingAttributeTraitTrait.php
// @date: 20230331 18:54:56
namespace igk\js\Vue3\Compiler\Traits;

use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\IHtmlGetValue;
use IGK\System\Regex\Replacement;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderTreatBindingAttributeTraitTrait{
    use VueSFCRenderTreatGetExpressionValueTrait;
    public static function TreatBindingAttribute($key, $v, $context=null){
        $rp = new Replacement;
        $modifiers = [];
        $rp->addCallable("/\.(?P<name>[^\. ]+)/", function($n)use($modifiers){
            $modifiers[] = $n['name'];
            return '';
        });
        $rp->addCallable("/^(v-bind)?:/", function($n)use($modifiers){   
            return '';
        });
        $key = $rp->replace($key);
        return (self::_GetKey($key) . ":" . self::_GetBindingExpressionValue($v, $context));
    }
    
}