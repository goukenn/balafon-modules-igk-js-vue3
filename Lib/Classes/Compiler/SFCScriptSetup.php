<?php
// @author: C.A.D. BONDJE DOUE
// @file: SFCScriptSetup.php
// @date: 20230301 18:24:39
namespace igk\js\Vue3\Compiler;

use \igk\js\common\IO\JSScriptReader;
use IGKException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use ReflectionException;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Compiler
 */
class SFCScriptSetup
{
    /**
     * detect variable options response 
     * @param string *7d9743b3 
     * @param igk\js\Vue3\Compiler\src *fb68b147 
     * @return ?array 
     */
    public static function DetectVarResponse(string $src): ?array
    {
        $jsreader = new JSScriptReader;
        $jsreader->src = $src;
        $mode = 0;
        $def = [];
        $declare_mode = false;
        $end = false;
        while (!$end && $jsreader->read()) {
            // igk_debug_wln("type = " . $jsreader->type, "value = " . $jsreader->value);
            if ($jsreader->depth == 0) {
                switch ($jsreader->type) {
                    case $jsreader::TOKEN_RESERVED_WORD:
                        if (($mode == 0) && in_array($jsreader->value, ['var', 'let', 'const', 'return', 'function'])) {
                            if ($jsreader->value == "return") {
                                $end = true;
                                $def = null;
                                break;
                            }
                            $mode = 1;
                            $declare_mode = true;
                        }
                        break;
                    case $jsreader::TOKEN_WORD:
                        if ($mode == 1) {
                            $def[$jsreader->value] = $jsreader->value;
                        }
                        break;
                    case $jsreader::TOKEN_OPERATOR:

                        if (($jsreader->value == ",") &&  $declare_mode) { // declaration initialiation 
                            $mode = 1;
                        } else if (($jsreader->value == ';') || ($jsreader->value == "\n")) {
                            $declare_mode = false;
                            $mode = 0;
                        } else {
                            if ($mode == 1) {
                                $mode = 0;
                            }
                        }
                        break;
                }
            }
        }
        return $def;
    }
    /**
     * 
     * @param string $src 
     * @return string 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public static function TransformToThisContext(string $src): string
    {
        $s = "";
        $jsreader = new JSScriptReader;
        $jsreader->src = $src;
        $mode = 0;
        $def = [];
        $declare_mode = false;
        $end = false;
        $follow = false;
        while (!$end && $jsreader->read()) {
            $cv = $jsreader->value;
            if ($jsreader->depth == 0) {
                switch ($jsreader->type) {

                    case $jsreader::TOKEN_RESERVED_WORD:
                        if (!$follow){
                            if ($cv == "this") {
                                $follow = true;
                            }
                        }
                        break;
                    case $jsreader::TOKEN_WORD:
                        // if not follow this
                        if (!$follow) {
                            $cv = 'this.' . $cv;
                            $follow = true;
                        }
                        break;
                    default:
                        if ($follow) {
                            if ($jsreader->type == $jsreader::TOKEN_OPERATOR ){
                                $follow = $cv == '.';
                            } else {
                               // if ($jsreader->type == $jsreader::TOKEN_BRACKET){
                                    $follow==false;
                                //}
                            }
                        }
                        break;
                }
            }
            $s .= $cv;
        }
        return $s;
    }
}
