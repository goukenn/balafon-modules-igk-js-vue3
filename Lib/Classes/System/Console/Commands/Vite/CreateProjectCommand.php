<?php
// @author: C.A.D. BONDJE DOUE
// @file: CreateProjectCommand.php
// @date: 20230419 08:09:36
namespace igk\js\Vue3\System\Console\Commands\Vite;

use IGK\Helper\IO;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use IGK\System\Console\Logger;
use IGK\System\IO\Path;
use IGK\System\Shell\OsShell;

///<summary></summary>
/**
* make a vite project - 2023
* @package igk\js\Vue3\System\Console\Commands\Vite
*/
class CreateProjectCommand extends VueCommandBase{
    var $command = '--vue3:make-vite-project';
    var $desc = 'make vite project';

    /**
     * 
     * @param mixed $command 
     * @param null|string $controller 
     * @param null|string $path_name request path name relative to Controller to Data folder
     * @return void 
     */
    public function exec($command, ?string $controller = null, ?string $path_name=null) { 
        $ctrl = self::GetController($controller);
        $npm = OsShell::Where("npm");
        $yarn = OsShell::Where("yarn");

        if (!$npm || !$yarn){
            Logger::danger('missing npm or yarn');
            return -1;
        }

        $path_name = $path_name ?? "VueApp";
        $path = Path::Combine($ctrl->getDataDir(), $path_name);

        Logger::print("Create Application");
        IO::CreateDir($pdir = dirname($path));
        $output = basename($path);
        $o = `cd $pdir && $npm create vue@3 $output 2>&2 1>&2 && cd $path && $yarn install 2>&2 1>&2`;

        Logger::info($o);
        Logger::info('output : '. $path);
        Logger::success("Done");

    }

}