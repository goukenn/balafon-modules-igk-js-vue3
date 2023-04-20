<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteAppUtilityCommand.php
// @date: 20230420 11:52:34
namespace igk\js\Vue3\System\Console\Commands\Vite;

use IGK\Helper\Activator;
use IGK\Helper\StringUtility;
use igk\js\Vue3\Vite\Configuration\ProjectViteSettingConfiguration;
use IGK\System\Console\Logger;
use IGK\System\IO\Path;
use IGKConstants;
use PhpParser\JsonDecoder;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\System\Console\Commands\Vite
*/
class ViteAppUtilityCommand extends ViteCommandBase{

    var $command = "--vue3:vite-app-utility";

    var $usage = "[controller] cmd [options]";

    var $desc = "vite application utility.";

    var $options = []; 

    const CMD_PREFIX = '_cmd_';

    public function exec($command, ?string $controller_or_command = null, ?string $cmd=null) { 
        $num = func_num_args();
        if ($num >= 3){
            $controller = $controller_or_command ?? igk_configs()->default_controller;
            //normal 
        } else if ($num==2) {
            $cmd = $controller_or_command;
            $controller = igk_configs()->default_controller;
        } 
        $ctrl = self::GetController($controller);

        $file = Path::Combine($ctrl->getDeclaredDir()."/", IGKConstants::PROJECT_CONF_FILE);  
        if (!file_exists($file)){
            igk_die(sprintf('missing configuration file [%s] ', $ctrl->getName()));
        }
        $cnf = ProjectViteSettingConfiguration::Load($file);

        $g = $this->listCommand();
        if (method_exists($this, $fc = self::CMD_PREFIX.StringUtility::CamelClassName($cmd))){
            return $this->$fc($command, $ctrl, $cnf);
        } else if($cl = igk_getv($g, $cmd)){
            return (new $cl())->exec($command, $ctrl, $cmd, $cnf);
        }else {
            $this->help();
            return -1;
        }        
    }
    public function help(){
        parent::help();

        Logger::print("for a list of available command --help-command");
    }
    /**
     * list commands 
     * @return ?array
     */
    public function listCommand(){
        $command =igk_environment()->get('vite-app-utility-command') ?? [];
        return $command;
    } 

    protected function _cmd_List($command, $ctrl, ProjectViteSettingConfiguration $cnf){
        Logger::print("List of vite-project");

        foreach($cnf->viteProjects as $k=>$v){
            Logger::info($k);
        }

    }



}