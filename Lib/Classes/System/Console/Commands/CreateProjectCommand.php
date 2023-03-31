<?php
// @author: C.A.D. BONDJE DOUE
// @filename: CreateProjectCommand.php
// @date: 20230330 19:03:51
// @desc: create a vue project and store it to project dev

namespace igk\js\Vue3\System\Console\Commands;

use IGK\Helper\IO;
use IGK\Helper\JSon; 
use IGK\System\Console\Logger;
use IGK\System\IO\Path;
use IGK\System\Shell\OsShell;

class CreateProjectCommand extends VueCommandBase
{

    var $command = "--vue3:create";
    var $desc = "create vue3 app for a controller project";
    var $category = "vue3";
    var $options = [
        "--name:[]"=>"define application name"
    ];
    public function showUsage(){
        Logger::print(sprintf("%s project [options]", $this->command));
    }
    public function exec($command, ?string $ctrl = null)
    {
        if (!OsShell::Where('vue')) {
            Logger::danger("no vue command found");
        }
        $name = igk_getv($command->options, "--name", "vue-app");
        $cdir = getcwd();
        Logger::print("create vue3 project");
        $outdir = tempnam(sys_get_temp_dir(), "vueapp-");
        $ctrl = $ctrl ? igk_getctrl($ctrl) : null;
        @unlink($outdir);
        IO::CreateDir($outdir);
        if (is_dir($outdir)) {
            chdir($outdir);
        } 
        $descriptorspec =  array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to               
        );
        $pipes = [];
        Logger::info("Prepare vue app in temp folder ".$outdir); 
        $proc = proc_open("export NODE_PATH="/$cdir."/package_node; vue create app 1>&2 2>&2", $descriptorspec, $pipes);
        if (is_resource($proc)) {
            // "read stdin ";
            fwrite($pipes[0], "\n"); //stream_get_contents(STDIN));
            fclose($pipes[0]);

            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($proc);
        }
        
        Logger::info("create environment files ... ");
        $data = implode("\n", [
            "VUE_APP_BASE_URL=".($ctrl ? $ctrl::uri() : null),
            "VUE_APP_APP_ENTRY_URL=".($ctrl ? $ctrl::uri() : null),
            "VUE_APP_ASSETS_URL=".($ctrl ? $ctrl::resolveAssetUri() : null),
        ]);
        igk_io_w2file("app/.env", $data);
        igk_io_w2file("app/.env.development.local", $data);
        igk_io_w2file("app/.env.local", $data);
        igk_io_w2file("app/.env.production", "");
        // @unlink("app/yarn.lock");

        if ($ctrl) {
            $package = $outdir . "/app";

            if ($data = \IGK\System\Npm\JsonPackage::Load($core_package = igk_io_packagesdir()."/package.json")){
                if ($data->mergeWith($package."/package.json")){
                    igk_io_w2file($core_package, JSon::Encode($data,(object)['ignore_empty'=>1],JSON_PRETTY_PRINT| JSON_UNESCAPED_SLASHES));
                }
            }
            IO::RmDir($package."/node_modules");
            $idir = Path::Combine($ctrl->getDataDir(), $name); 
            IO::CreateDir($idir);
            Logger::info("copy files ... ");
            IO::CopyFiles($package, $idir, true, true);            
            IO::RmDir(dirname($package)); 
            $package = $idir;
        }
        Logger::success("Done ".$package);
    }
}
