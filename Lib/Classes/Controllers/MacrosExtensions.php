<?php

// @author: C.A.D. BONDJE DOUE
// @filename: MacrosExtension.php
// @date: 20220816 19:03:24
// @desc: 

namespace igk\js\Vue3\Controllers;

use Closure;
use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use IGKException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use ReflectionException;

abstract class MacrosExtensions{
    private function __construct(){
    }
    /**
     * import layout 
     * @param BaseController $controller 
     * @param string $file 
     * @param null|array $param 
     * @return Closure 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
   public static function vueLayoutImport(BaseController $controller, string $file, ?array $param=null):Closure{
        return ViewHelper::Import($file, $param,  $controller);       
   }
}
