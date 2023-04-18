<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueMakeSFCComponentCommand.php
// @date: 20230418 13:05:03
namespace igk\js\Vue3\System\Console\Commands;

use igk\js\Vue3\System\IO\VueSFCFile;
use IGK\System\Console\Logger;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Console\Commands
*/
class VueMakeSFCComponentCommand extends VueCommandBase{
    var $command = '--vue3:make-sfc-component';
    public function exec($command , ?string $name=null, ?string $controller=null) { 
        empty($name) && igk_die("name required");
        $ctrl = self::GetController($controller, false);
        $f = $name;
        if (igk_io_path_ext($f)!='vue'){
            $f.=".vue";
        }
        $component = new VueSFCFile;

        igk_io_w2file($f, $component->render());

        Logger::sucess("output: ", $f);
    }

}