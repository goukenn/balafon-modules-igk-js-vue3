<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueProjectHelperCommand.php
// @date: 20230414 18:37:26
namespace igk\js\Vue3\System\Console\Commands;

use IGK\System\Console\Commands\MakeUtility;
use igk\System\Console\Commands\Utility;
use IGK\System\Console\Logger;
use IGK\System\IO\File\PHPScriptBuilder;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Console\Commands
*/
class VueProjectHelperCommand extends VueCommandBase{
    var $command = '--vue3:project';
    var $desc = 'vue command helper';
    public function exec($command, string $cmd=null) { 
        if (empty($cmd)){
            Logger::danger('missing command');
            $this->showUsage();
            return;
        }
        if (!method_exists($this, $cmd = 'cmd_'.$cmd)){
            Logger::danger('command not found');
            $this->showUsage();
            return;
        }
        $args = array_merge([$command], array_slice(func_get_args(), 2));
        return $this->$cmd(...$args);
    }
    /**
     * init project command
     */
    public function cmd_init($command, $controller){
        $ctrl = self::GetController($controller);
        $bind = [];
        $dir = $ctrl->getDeclaredDir();
        $force = property_exists($command->options, '--force');
        $bind[$dir.'/Configs/vue-router.pinc.php'] = function($file){
            $builder = new PHPScriptBuilder;
            $builder->type('function');
            $builder->defs('return [];');
            igk_io_w2file($file, $builder->render());
        }; 
        Utility::MakeBindFiles($command, $bind, $force);
    }
    public function cmd_make_component($command, $controller,$path){
        $mod = igk_current_module();
        $ctrl = self::GetController($controller);
        $bind = [];
        $dir = $ctrl->getDeclaredDir();
        $force = property_exists($command->options, '--force');
        if (igk_io_path_ext($path)!='js'){
            $path.='.js';
        }
        $bind[Path::Combine($dir.'/Data/', $path)] = function($file)use($mod){ 
            $author = IGK_AUTHOR;
            $sb = new StringBuilder;
            $sb->appendLine("// @component: ".igk_io_basenamewithoutext($file));
            $sb->appendLine("// @author: ".$author);
            $sb->appendLine("// @date: ".date('Ymd H:i:s'));
            $sb->appendLine("// @desc: ".date('Ymd H:i:s'));
            $sb->appendLine(file_get_contents($mod->getDataDir().'/scaffold/component.js'));

            igk_io_w2file($file,$sb.'');
        }; 
        Utility::MakeBindFiles($command, $bind, $force);
    }

}