<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderGetKeyValueTrait.php
// @date: 20230331 02:58:45
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\common\JSExpression;
use igk\js\common\JSExpressionUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\IHtmlGetValue;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderGetKeyValueTrait{
    protected static function _GetKey(string $k){        
        if (JSExpressionUtility::IsValidKey($k)){
            return $k;
        }
        return igk_str_surround($k,"'");
    }
    /**
     * 
     * @param mixed $v 
     * @return string 
     */
    protected static function _GetValue($v, $options=null, $preserve=false):?string{
        if (is_null($v)){
            return null;
        }
        if ($v instanceof IHtmlGetValue)
            $v = $v->getValue($options);
        else if ($v instanceof HtmlItemBase){
            igk_die("not allowed");
        }
        if (is_numeric($v)){
            return $v;
        }
        if (!$preserve && preg_match("/{/",$v)){
            // is mustache replace with ${}express
            $v = VueSFCUtility::InterpolateValue($v, '{{', '}}');          
            return igk_str_surround(trim($v, '`'),"`");
        }
        return igk_str_surround($v,"'");
    }
}