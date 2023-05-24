<?php

namespace igk\js\Vue3;

use IGKException;

/**
 * Vue Script helper
 * @package igk\js\Vue3
 */
class VueScript
{
    /**
     * get file content treat it as build function
     * @param string $file 
     * @return string|void 
     * @throws IGKException 
     */
    public static function Include(string $file)
    {
        if ($src = file_get_contents($file)) {
            return sprintf('...(function(){ return %s; })()', self::_treatForReturn($src));
        }
    }
    private static function _checkStartComment($tab, $start)
    {
        $single = $tab[0] == "//";
        if (!$start && $single) {
            igk_die("please use a multiline comment as first src expression");
        }
    }
    private static function _treatForReturn(string $src, $remove_comment = false)
    {
        if ($g = trim($src)) {
            // if start with comment remove line feed until first instruction found
            $offset = 0;
            $tab = [];
            $pos = null;
            $start = 0;
            $regex = "#/(/|\*)#";
            $detect = false;
            while (!$detect && preg_match($regex, $g, $tab, 0, $offset) && (false !== ($pos = strpos($g, "\n", $offset)))) {
                if (!$start && ($g[0] != "/")) {
                    break;
                }
                self::_checkStartComment($tab, $start);

                $t = substr($g, $pos);
                $g = $remove_comment ? '' : substr($g, 0, $pos);
                $len = strlen($t);
                $src = "";
                $pos = 0;
                while ($pos < $len) {
                    $ch = $t[$pos];
                    switch ($ch) {
                        case "\r":
                        case "\n":
                        case " ":
                            // ignore line fied
                            break;
                        default:
                            if (preg_match($regex, $t, $tab, 0, $pos)) {
                                $fpos = strpos($t, $tab[0],$pos ); 
                                if ($fpos === $pos){ //
                                    self::_checkStartComment($tab, $start);
                                }else{
                                    // after comment 
                                    $start = false;
                                    $detect=true;
                                    break;
                                }
                                $cpos = $pos;
                                if (false !== ($pos = strpos($t, "\n", $pos))) {
                                    $g .= $remove_comment ? '' : substr($t, $cpos, $pos - $cpos);
                                    $t = substr($t, $pos);
                                    $len = strlen($t);
                                    $pos = -1;
                                    break;
                                }
                            } else {
                                $detect = true;
                            }
                            break;
                    }
                    if ($detect) {
                        $g .= substr($t, $pos);
                        break;
                    }
                    $pos++;
                }
            };
            return $g;
        }
        if (!empty($src)){
            $src = rtrim($src,";");
        }
        return $src;
    }
}