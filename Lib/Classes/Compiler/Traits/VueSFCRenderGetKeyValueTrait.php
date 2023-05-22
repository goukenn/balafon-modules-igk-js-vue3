<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderGetKeyValueTrait.php
// @date: 20230331 02:58:45
namespace igk\js\Vue3\Compiler\Traits;

use IGK\Helper\StringUtility;
use igk\js\common\JSExpression;
use igk\js\common\JSExpressionUtility;
use igk\js\Vue3\Compiler\VueSFCUtility;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\IHtmlGetValue;
use IGKException;
use ReflectionException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderGetKeyValueTrait{
    protected $interpolateStart = '{{';
    protected $interpolateEnd = '}}';
    
    protected static function _GetKey(string $k){        
        if (JSExpressionUtility::IsValidKey($k)){
            return $k;
        }
        return igk_str_surround($k,"'");
    }
    /**
     * parset to littral value
     * @param mixed $v 
     * @param mixed $options 
     * @param bool $preserve 
     * @return null|string 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected static function _GetValue($v, $options=null, $preserve=false, $start='{{', $end='}}'):?string{
        if (is_null($v)){
            return null;
        }
        if ($v instanceof IHtmlGetValue)
            $v = $v->getValue(null);
        else if ($v instanceof HtmlItemBase){
            igk_die("not allowed");
        }
        if (is_numeric($v)){
            return $v;
        }
        $v = $v.'';
        if (!$preserve && preg_match("/".$start."/",$v)){
            // is mustache replace with ${}express
            $args = $options && $options->contextVars  ? $options->contextVars[0] : [];
            $v = VueSFCUtility::InterpolateValue($v, $start,  $end, $preserve, $args);          
            return igk_str_surround(trim($v, '`'),"`");
        }
        if (strpos($v,"'")!==false){          
            $v = igk_str_escape($v, "'");
        }

        return igk_str_surround($v,"'");
    }
}
 