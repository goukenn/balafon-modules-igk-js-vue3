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
    var $desc = 'make sfc component';
    var $usage = 'filename [options]';
    public function exec($command , ?string $name=null) { 
        empty($name) && igk_die("name required"); 
        $f = $name;
        if (igk_io_path_ext($f) != 'vue'){
            $f.=".vue";
        }
        $component = new VueSFCFile;
        $component->template()->div()->Content = 'Hello component,'.basename($name);

        $component->script();

        $component->style();

        igk_io_w2file($f, $component->render((object)['Indent'=>true]));
        Logger::success("output: ", $f);
    }

}