<?php
// @author: C.A.D. BONDJE DOUE
// @filename: JSUtility.php
// @date: 20230421 14:57:02
// @desc: 

namespace igk\js\Vue3\Helpers;

use igk\js\common\JSTokenReader;
use IGK\System\IO\StringBuilder;

abstract class JSUtility{
    
    /**
     * treat expression and return for SFC
     * @param string $expression 
     * @return string 
     */
    public static function TreatExpression(string $expression, array $vars = [])
    { 
        $exp = '';
        $filtering = false;
        $tags = [$expression];
        $base = '';
        $rp = '';
        /**
         * @var ?StringBuilder
         */
        $rkey = null;
        $depth = 0;

        while (count($tags) > 0) {
            $ref = 0;
            $expression  = array_shift($tags);
            if (is_array($expression)) {
                $rp = $expression[0];
                $rkey = $expression[2];
                $expression = $expression[1];
            }
            $tag = JSTokenReader::GetAllToken($expression);
            foreach ($tag as $e) {
                list($token, $value) = $e;
                if ($depth===0){
                     
                if ($value == 'this') {
                    $ref = 1;
                }
                if (!$filtering && ($token == JSTokenReader::TOKEN_LITTERAL_EXPRESSION)) {
                    // litteral expression to 
                    if ($c = preg_match_all("/\\\$\{(?P<value>[^\}]+)\}/", $value, $tab)) {
                        $vv = $value;
                        $sb = new StringBuilder($vv);
                        while ($c > 0) {
                            $c--;
                            $tags[] = [array_shift($tab[0]), array_shift($tab['value']), $sb];
                        }
                    }
                }
                if ($token == JSTokenReader::TOKEN_WORD) {
                    if (!$ref) {
                        if (!in_array($value, $vars)){
                            $exp .= 'this.';
                        }
                        $ref = 1;
                    }
                }
                if (($token == JSTokenReader::TOKEN_BRACKET_END) || ($token == JSTokenReader::TOKEN_BRACKET_START)) {
                    $ref = 0;
                }
            } 
            switch($value){
                case '{':
                case '(':
                case '[':
                    $depth++;
                    break;
                case '}':
                case ']':
                case ')':
                    $depth--;
                    break;
            }

                $exp .= $value;
            }
            if (count($tags) > 0) {
                if (!$filtering) {
                    $base = $exp;
                    $exp = '';
                } else {
                    $tsrc = $rkey . '';
                    $pp = str_replace($rp, "\${" . $exp . "}", $tsrc);
                    $base = str_replace($tsrc, $pp, $base);
                    $rkey->replace($tsrc, $pp);
                    $exp = '';
                }
                $filtering = true;
            } else {
                if ($filtering) {
                    $tsrc = $rkey . '';
                    $pp = str_replace($rp, "\${" . $exp . "}", $tsrc);
                    $base = str_replace($tsrc, $pp, $base);
                    $exp = $base;
                }
            }
        }
        return $exp;
    }

        /**
     * treat source and get loaded import
     * @param string $src 
     * @return null|array 
     */
    static function GetImport(string & $src):?array{
        $imports = [];
        $l = JSTokenReader::GetAllToken($src);
        $v = '';
        $i = false; // check of import 
        $end = false;
        $append =false;
        //wait until litteral to close 
        $litteral = false;
        $skip = false;
        $tv = '';
        while(!$end && (count($l)>0)){
            $q = array_shift($l);
            $tv = $q[1];
            switch($q[0]){
                case JSTokenReader::TOKEN_LITTERAL_STRING:
                    if ($i){
                        $litteral = true;
                    }
                    break;
                case JSTokenReader::TOKEN_RESERVERD_WORD:                
                    if (!$i){
                        if ($tv=='import'){
                            $i = true;                        
                        }
                    } 
                    break;
                default:
                    if ($i && $litteral && in_array($q[1], [";","\n"])){
                        $append = true;
                        $litteral = false;
                    }
                break;
            }
            if (!$i && ($q[1]=='(')){
                $end = true;
                continue;
            }

            if ($i){                
                if (empty(trim($tv))){
                    if (!$skip){
                        $tv =' ';
                        $skip = true;
                    }
                    else{
                        $tv ='';
                    }
                } else {
                    $skip = false;
                }
            }
            $v.= $tv;
            if ($append){
                $imports[] = $v; 
                $v = '';
                $append = false;
                $i = false;
                $litteral = false;
            }
        } 
        if ($i && !empty($v)){
            $imports[] = trim($v); 
            $v = '';
        }
        // combine rest of token 
        $src =  $tv.implode("" , array_map(function($a){ return $a[1]; }, $l));
        return $imports;
    } 

}