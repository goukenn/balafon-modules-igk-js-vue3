<?php
// @author: C.A.D. BONDJE DOUE
// @file: CreateProjectCommand.php
// @date: 20230419 08:09:36
namespace igk\js\Vue3\System\Console\Commands\Vite;

use IGK\Helper\IO;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use igk\js\Vue3\Vite\Configuration\ProjectViteSettingConfiguration;
use igk\js\Vue3\Vite\ViteProjectInfo;
use igk\js\Vue3\VueConstants;
use igk\System\Console\Commands\Utility;
use IGK\System\Console\Logger;
use IGK\System\IO\File\PHPScriptBuilder;
use IGK\System\IO\Path;
use IGK\System\Regex\Replacement;
use IGK\System\Shell\OsShell;
use IGKConstants;

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
        $c_data_dir = $ctrl->getDataDir();
        $path = Path::Combine($c_data_dir, $path_name);

        Logger::print("Create Application");
        IO::CreateDir($pdir = dirname($path));
        $output = basename($path);

        // initprojec - configuration 
        $module = igk_current_module();
        $src = file_get_contents($module->getDataDir()."/vite/vite.config.js");
        $rp = new Replacement;
        $rp->add("/<% project.outdir %>/",IO::GetRelativePath($path."/", $ctrl->getAssetsDir()."/".$output."/dist" ));
        $rp->add("/'\s*<% project.plugins %>\s*'/",'vue()');
        $rp->add("/'\s*<% project.imported.plugins %>\s*'/",'');
        $src = $rp->replace($src);

        
        $o = `cd $pdir && $npm create vue@3 $output 2>&2 1>&2 && cd $path && $yarn install 2>&2 1>&2 && echo 'done'`;
        if ($o && (trim($o)=='done')){
            // replace configuration file .
            igk_io_w2file($path.'/vite.config.js', $src);
            $file = Path::Combine($ctrl->getDeclaredDir()."/", IGKConstants::PROJECT_CONF_FILE);  
            if (!file_exists($file)){
                igk_die(sprintf('missing configuration file [%s] ', $ctrl->getName()));
            }
            $cnf = ProjectViteSettingConfiguration::Load($file);
            if (!$cnf->viteProjects){
                $cnf->viteProjects = (object)[];
            }
            $v_viteProject = new ViteProjectInfo();
            $v_viteProject->date = date('Ymd H:i:s');
            $v_viteProject->author = $this->getAuthor($command);

            $cnf->viteProjects->{$path_name} = $v_viteProject;
            igk_io_w2file($file, json_encode($cnf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            // init some default - scaffold
            $this->initialzeDirectory($path, $ctrl, $command);
        } 
        Logger::info('output : '. $path);
        Logger::success("Done");

    }

    public function initialzeDirectory(string $dir, $ctrl, $command){
        $data[$dir."/src/rsscomponents/Home.phtml"]= function($f){
            $builder = new PHPScriptBuilder;

            igk_io_w2file($f, $builder->render());
        };
        $data[$dir."/src/rsscomponents/Home".VueConstants::VUE_JS_SETUP_EXT]= function($f){
     
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir()."/scaffold/vue3-setup.js");
            igk_io_w2file($f, $src);
        };

        Utility::MakeBindFiles($command, $data, false);
    }

}