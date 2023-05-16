<?php
// @author: C.A.D. BONDJE DOUE
// @file: CreateProjectCommand.php
// @date: 20230419 08:09:36
namespace igk\js\Vue3\System\Console\Commands\Vite;

use IGK\Helper\IO;
use igk\js\common\JSExpression;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use igk\js\Vue3\Vite\Configuration\ProjectViteSettingConfiguration;
use igk\js\Vue3\Vite\ViteProjectInfo;
use igk\js\Vue3\VueConstants;
use IGK\System\Console\App;
use IGK\System\Console\BalafonApplication;
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
class CreateProjectCommand extends VueCommandBase
{
    var $command = '--vue3:make-vite-project';
    var $desc = 'make a vite project for a controller';

    var $options = [
        "--view:view_name" =>  "set the require view name - to build",
        '--force'=>'force view creation',
        '--entryNamespace:[ns]'=>'set entry namespace'
    ];

    private $m_force_view = false; 

    public function showUsage(){
        $this->showCommandUsage('controller path [options]');
    }
    /**
     * 
     * @param mixed $command 
     * @param null|string $controller 
     * @param null|string $path_name request path name relative to Controller to Data folder
     * @return void 
     */
    public function exec($command, ?string $controller = null, ?string $path_name = null)
    {
        $ctrl = self::GetController($controller);
        $npm = OsShell::Where("npm");
        $yarn = OsShell::Where("yarn");

        if (!$npm || !$yarn) {
            Logger::danger('missing npm or yarn');
            return -1;
        }

        $path_name = $path_name ?? "VueApp";
        $c_data_dir = $ctrl->getDataDir();
        if ((strlen($path_name) > 1) && $path_name[0] == "/") {
            $c_data_dir = dirname($path_name);
            $path_name = basename($path_name);
        }
        $path = Path::Combine($c_data_dir, $path_name);

        $view = igk_getv($command->options, '--view');
        $force = property_exists($command->options, '--force');
        if ($view){

            $file = $ctrl->getViewFile($view, false);

            if (file_exists($file) && !$force){
                Logger::warn('controller already contains a view '.$view.', do you want to continue ? '.
                App::Gets(App::GRAY, 'Y/n')."\r");
                $y = readline(); //App::Gets(App::GRAY, 'Y/n')); 
                if (!$y || (strtolower($y)!='y')){
                    return -1;
                }
                $this->m_force_view = true;
            }
        }
 

        Logger::print("Create Application : " . $path);
        IO::CreateDir($pdir = dirname($path));
        $output = basename($path);

        // initprojec - configuration 
        $imports = [];
        $imports[] = 'import { balafonViewHandler } from \'./plugins/balafonViewHandler.js\';';
        $imports[] = 'import vueI18n from \'@intlify/vite-plugin-vue-i18n\';';
        $plugins = ['vue()'];
        $plugins[] = 'vueI18n({})';
        $plugins[] = sprintf('balafonViewHandler(%s)', JSExpression::Stringify((object)[
            'controller' => $ctrl->getName(),
        ]));
        $module = igk_current_module();
        $src = file_get_contents($module->getDataDir() . "/vite/vite.config.js");
        $rp = new Replacement;
        $rp->add("/<% project.outdir %>/", IO::GetRelativePath($path . "/", $ctrl->getAssetsDir() . "/" . $output . "/dist"));
        $rp->add("/'\s*<% project.plugins %>\s*'/", implode(',', $plugins));
        $rp->add("/'\s*<% project.imported.plugins %>\s*'/", implode("\n", $imports));
        $src = $rp->replace($src);


        $o = `cd $pdir && $npm create vue@3 $output 2>&2 1>&2 && cd $path && $yarn install 2>&2 1>&2 && echo 'done'`;
        if ($o && (trim($o) == 'done')) {
            // replace configuration file .
            igk_io_w2file($path . '/vite.config.js', $src);
            $file = Path::Combine($ctrl->getDeclaredDir() . "/", IGKConstants::PROJECT_CONF_FILE);
            if (!file_exists($file)) {
                igk_die(sprintf('missing configuration file [%s] ', $ctrl->getName()));
            }
            $cnf = ProjectViteSettingConfiguration::Load($file);
            if (!$cnf->viteProjects) {
                $cnf->viteProjects = (object)[];
            }
            $v_viteProject = new ViteProjectInfo();
            $v_viteProject->date = date('Ymd H:i:s');
            $v_viteProject->author = $this->getAuthor($command);

            $cnf->viteProjects->{$path_name} = $v_viteProject;
            igk_io_w2file($file, json_encode($cnf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            // init some default - scaffold
            $this->initialzeDirectory($path, $ctrl, $command);
            chdir($path);
            //`yarn add -D `;
            Logger::info('add extra packages');
            `{$yarn} add vue-i18n@next vue-router@next vuex@next pinia @intlify/vite-plugin-vue-i18n 2>&2 1>&2 `;            
        }
        Logger::info('output : ' . $path);
        Logger::success("Done");
    }

    public function initialzeDirectory(string $dir, $ctrl, $command)
    {
        $module = igk_current_module();

        $view = igk_getv($command->options, '--view');
        if ($view){
            $ctrl->getViewFile($view, false);

            $data[$dir."/"] = function($f)use($module, $command){
                $builder = new PHPScriptBuilder;
                $builder->type("function")
                ->author($this->getAuthor($command));
                $src = file_get_contents($module->getDataDir().'/vite/default.view.phtml');
                // + | --------------------------------------------------------------------
                // + | treat default vue 
                // + |
                $rp = new Replacement; 
                $src = $rp->replace($src); 
                $builder->setDefs($src); 
                igk_io_w2file($f, $builder->render());
            };
        }
        $data[$dir . "/src/rsscomponents/Home.phtml"] = function ($f) {
            $builder = new PHPScriptBuilder;
            $src = igk_ob_get_func(function () {
?>
                $builder([
                'div > container > h1'=>'Vite + Balafon'
                ]);
<?php
            });
            $builder->type('function')->defs($src);
            igk_io_w2file($f, $builder->render());
        };
        $data[$dir . "/src/rsscomponents/Home" . VueConstants::VUE_JS_SETUP_EXT] = function ($f) {
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir() . "/scaffold/vue3-setup.js");
            igk_io_w2file($f, $src);
        };
        // + | --------------------------------------------------------------------
        // + | attach plugins
        // + |        
        $data[$dir . "/plugins/balafonViewHandler.js"] = function ($f) {
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir() . "/vite/plugins/balafonViewHandler.js");
            igk_io_w2file($f, $src);
        };

        $data[$dir . "/.env"] = function ($f)use($ctrl, $command) {
            $app = igk_app()->getApplication();
            $wdir = getcwd();
            $ns = igk_getv($command->options, '--entryNamespace', $ctrl->getName());
            if ($app instanceof BalafonApplication){
                $wdir = $app->getWorkingDir();
            }
            $ns = '';

            $src = '# environment file' . PHP_EOL;
            $src .= implode("\n", [
                'VITE_IGK_WORKING_DIR=' . $wdir,
                'VITE_IGK_ENTRY_NAMESPACE='. $ns
            ]);
            igk_io_w2file($f, $src);
        };


        $data[$dir . "/src/main.js"] = function ($f)use($ctrl, $command) {
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir() . "/vite/js/main.js");
            igk_io_w2file($f, $src);
        };
        $data[$dir . "/index.html"] = function ($f)use($ctrl, $command) {
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir() . "/vite/index.html");
            $rp  = new Replacement;
            $rp->add('/<% project.title %>/', $ctrl->getConfig('clAppName'));
            $src = $rp->replace($src);
            igk_io_w2file($f, $src); 
        };
        $data[$dir . "/src/components/HelloWorld.vue"] = function ($f)use($ctrl, $command) {
            $module = igk_current_module();
            $src = file_get_contents($module->getDataDir() . "/vite/components/HelloWorld.vue");
            $rp  = new Replacement;
            $rp->add('/<% project.title %>/', $ctrl->getConfig('clAppName'));
            $src = $rp->replace($src);
            igk_io_w2file($f, $src); 
        };
        Utility::MakeBindFiles($command, $data, true);
    }
}
