<?php

namespace igk\js\Vue3\System\Console\Commands\Vite;

use igk\js\Vue3\System\Console\Commands\Svg\ConvertSVGToVueCommand;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use IGK\System\Console\Logger;
use IGK\System\Regex\Replacement;

class ViteGenIconScriptCommand extends VueCommandBase
{
    var $command = "--vue3:vite-gen-svg-lib"; 
    var $category = 'vue3';
    var $desc = 'generate icons libraries';
    public function exec($command, ?string $libname = null, ?string $svg_source_dir=null, ?string $target_dir=null) { 
        (!$libname || !$svg_source_dir || !$target_dir ) && igk_die('missing required argument');

        $m = new ConvertSVGToVueCommand;
        $cmd = $command->app::CreateCommand($command->app);  
        $m->exec($cmd, $svg_source_dir, $target_dir."/icons");

        $g = dirname($target_dir);
        $src = file_get_contents(igk_current_module()->getDataDir()."/vite/js/gen_icons_script.pjs");
        $rp = new Replacement;
        $rp->add("/%libname%/", $libname); 
        $src = $rp->replace($src); 
        igk_io_w2file($target_dir."/index.js", $src); 
        Logger::success('done');
    }
    
}