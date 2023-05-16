<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteApiHelper.php
// @date: 20230504 19:33:41
namespace igk\js\Vue3\Vite;

use IGK\Controllers\BaseController;
use IGK\Helper\Activator;
use IGK\Helper\ViewHelper; 
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Regex\Replacement;
use IGKException;
use ReflectionException;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Vite
*/
class ViteApiHelper{
    var $source;
    private $m_idcounter = 0;
    const MENU_NAME = 'vue-vite-api.pinc';

    /**
     * load menu with 
     * @param BaseController $ctrl 
     * @param string|null $api_name 
     * @return mixed 
     * @throws IGKException 
     */
    public static function Load(BaseController $ctrl, string $api_name=null){
        $m = $api_name ?? self::MENU_NAME;
        $file = $ctrl->configFile($m);
        $tab = ViewHelper::Inc($file, [
            'ctrl'=>$ctrl,
            'user'=>$ctrl->getUser(),
            'helper'=>new static
        ]);   
        return $tab;
    }  
}