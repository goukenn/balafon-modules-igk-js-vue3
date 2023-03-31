<?php

// @author: C.A.D. BONDJE DOUE
// @filename: CopyDistToCommand.php
// @date: 20230214 13:17:10
// @desc: copy dist to project directory 


namespace igk\js\Vue3\System\Console\Commands;
 
use IGK\Helper\IO;  
use IGK\System\Console\Logger;
use IGK\System\IO\Path;
use IGK\System\Regex\Replacement; 
use function igk_resources_gets as __;

/**
 * copy vue distibution project asset 
 * @package igk\js\Vue3\System\Console\Commands
 */
class CopyDistToCommand extends VueCommandBase
{
    var $command = "--vue3:copy-dist";
    var $desc = "copy distibution to controller assets";
    var $distName = 'dist';

    public function exec($command, ?string $controller = null, ?string $dir = null, $appName="vue-app")
    { 
        if (empty($controller)){
            Logger::danger(__("controller is required"));
            return -1;
        }

        if (empty($dir) || !is_dir($dir)){
            Logger::danger("distibution directory is missing");
            return -2;
        } 
        $ctrl = self::GetController($controller, true);
        $asset = $ctrl->getAssetsDir();
        $output = Path::Combine($asset, $appName);


        if (property_exists($command->options, "--clean")){
            Logger::info("clean directory");
            IO::RmDir($output);
        }

        $ln = strlen($dir);
        $JS_REPLACE = new Replacement;
        $JS_REPLACE->add("/(\"|')__IGK_BASE_URI__(\/)?\\1/", "configs.publicPath");

        $JS_Manifest = new Replacement;
        $JS_Manifest->add("/(\"|')__IGK_BASE_URI__(\/)?(\\1)?/", "\\1/");
        foreach(IO::GetFiles($dir, "/.*/", true) as $f){
            $path = substr($f, $ln);
            $ext = igk_io_path_ext($f);
            $c = Path::Combine($output, $path);
            switch($ext){
                case 'html':
                    break;
                case "js":
                    $rs = file_get_contents($f);
                    // treat file = 
                    $rs = $JS_REPLACE->replace($rs);
                    igk_io_w2file($c, $rs);
                    break;
                case "json":
                    if (basename($f)=="manifest.json"){
                        $rs = file_get_contents($f); 
                        $rs = $JS_Manifest->replace($rs);
                        igk_io_w2file($c, $rs);
                        break;
                    }
                    copy($f, $c); 
                    break;
                default:                  
                    if (!IO::CreateDir(dirname($c))){
                        Logger::danger("failed to create directory : for ".$c);
                    }
                    copy($f, $c); 
                break;
            }
        } 
        Logger::success("output : ".$output);
    }
}