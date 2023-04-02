<?php
// @author: C.A.D. BONDJE DOUE
// @file: ConvertVueProjectToInlineBalafonCommand.php
// @date: 20230308 03:09:00
namespace igk\js\Vue3\System\Console\Commands;

use IGK\Controllers\BaseController;
use IGK\Helper\IO; 
use igk\js\Vue3\System\Converter\VueInlineProjectConverter; 
use IGK\System\Console\Logger;
use IGK\System\IO\Path; 
use IGKException;

///<summary></summary>
/**
* use to convert existing vue3 project 
* @package igk\js\Vue3\System\Console\Commands
*/
class ConvertVueProjectToInlineBalafonCommand extends VueCommandBase{
    var $command = '--vue3:convert'; 
    var $options=[
        "--output:[dir]"=>"ouput directory",
        "--name:[name]"=>"project dir name"
    ];
    var $desc = "Convert vue project to inline runtime balafon-app";
    public function showUsage(){
        parent::showUsage();
        Logger::print(sprintf("%s path [controller] [options]", $this->command));
    }
    public function exec($command, string $path=null, $controller=null){
        $controller && ($controller = self::GetController($controller) ?? igk_die("project controller not found"));
        if (!$path || !is_dir($path)){
            igk_die("not a valid directory.");
        }
        // check that directory is a valid vue3 project
        $packagejson = Path::Combine($path, 'package.json');
        if (!is_file($packagejson)){
            igk_die("can't validate vue3 installation package. missing package.json");
        }
        $vueconfig = Path::Combine($path, 'vue.config.js');
        if (!is_file($vueconfig)){
            igk_die("can't validate vue3 installation package. missing vue.config.js");
        }
        $name = 'vue-app/'.(igk_getv($command->options, "--name", "app"));
        $outdir = igk_getv($command->options, '--output') ?? ($controller? $controller->getDataDir()."/".$name : null) ?? igk_die("missing output dir.");
        
        if ($this->Convert($path, $controller, $outdir)){
            Logger::info("input : ".$path);
            Logger::info("ouput : ".$outdir);
            Logger::success("conversion success.");
            return 0;
        }
        Logger::danger('something went wrong.');
        return -1;
    }
    /**
     * convert file expression
     * @param string $path 
     * @param null|BaseController $controller 
     * @param mixed $outdir 
     * @return bool 
     * @throws IGKException 
     */
    public function Convert(string $path, ?BaseController $controller, $outdir):bool{
        $files = IO::GetFiles($path."/src", "/\.(vue|js|css|scss|png|jp(e)g|json|md)$/",true);
        
        $converter = new VueInlineProjectConverter;
        $converter->outdir = $outdir;
        $converter->inputdir = $path;
        Logger::print("converting....");
        foreach($files as $file ){
            // $file = '/Volumes/Data/Downloads/cork-vue-v2.0.1/vue3/src/layouts/app-layout.vue';
            igk_is_debug() && Logger::info($file);
            $converter->Convert($file, $controller);
           // break;
        } 
        return true;
    }
}