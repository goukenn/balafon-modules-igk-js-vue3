<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderTreatGetExpressionValueTrait.php
// @date: 20230331 18:54:39
namespace igk\js\Vue3\Compiler\Traits;

use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\IHtmlGetValue;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderTreatGetExpressionValueTrait{
    protected static function _GetBindingExpressionValue($v, $context=null){
        if (is_null($v)){
            return 'null';
        }
        if ($v instanceof IHtmlGetValue)
            $v = $v->getValue($context);
        else if ($v instanceof HtmlItemBase){
            igk_die("not allowed");
        }
        if (is_numeric($v)){
            return $v;
        }
        return $v;
    }
    
}