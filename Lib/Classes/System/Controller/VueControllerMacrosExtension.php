<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueControllerMacrosExtension.php
// @date: 20230415 13:51:35
// @desc: description 

namespace igk\js\Vue3\System\Controller;

use IGK\Controllers\BaseController;
use IGK\System\IO\Path;

abstract class VueControllerMacrosExtension{
    public static function getVueAppDir(BaseController $ctrl){
        $assets = $ctrl->getAssetsDir(); 
        return Path::Combine($assets, 'VueApp');
    }
}