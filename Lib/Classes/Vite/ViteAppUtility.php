<?php

namespace igk\js\Vue3\Vite;

use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Helpers\JSUtility;
use igk\js\Vue3\System\Console\Commands\ViteLibraryManagment;
use igk\js\Vue3\Traits\ResolveLibraryTrait;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\System\IO\StringBuilder;

class ViteAppUtility{
    use ResolveLibraryTrait;

    public static function BuildView(BaseController $ctrl, string $file, ?string $app_dir=null){ 
        $t = new VueComponent('div'); 
        igk_ctrl_bind_css($ctrl, $t, igk_io_basenamewithoutext($file)); 
        $builder = new HtmlNodeBuilder($t);
        // $app_dir = igk_getv($command->options, '--app-dir', igk_server()->INIT_CWD) ?? dirname($file);
        $app_dir = $app_dir ?? dirname($file);
        $vite = new ViteAppManagement($ctrl, $app_dir);
        $library = new ViteLibraryManagment($vite);
        ViewHelper::Inc($file, compact('builder', 't','ctrl', 'vite', 'library'));
        $src = '';
        $options = null; 
        $render = VueSFCCompiler::ConvertToVueRenderMethod($t, $options);
        if (file_exists($js = igk_io_remove_ext($file).VueConstants::VUE_JS_SETUP_EXT)){
            $sb = new StringBuilder();
            if ($render){
                $sb->appendLine("import * as Vue from 'vue';"); 
            }
            if ($options){
                foreach($options->libraries as $k=>$v){
                    // render library in use
                    // $sb->appendLine(sprintf('const {%s} = %s;', implode(",", array_keys($v)), $k));
                    $sb->appendLine(sprintf('import {%s} from \'%s\';', implode(",", array_keys($v)), self::ResolvLibToDev($k)));
                }
            }
            if ($libs = $library->getLibs()){
                foreach($libs as $k=>$v){
                    $sb->appendLine(sprintf('import %s from \'%s\';',$v, $k));
                }
            }
            // append i18n if required
            $sb->appendLine("import * as VueI18n from 'vue-i18n';");
            $src = trim(file_get_contents($js), '; ');
            // trait global imports
            if ($import = JSUtility::GetImport($src)){
                $sb->appendLine(implode("\n", $import));
            }
            if (!empty($src = trim($src))){ 
                //check that is a calling function 
                $src = '...'.$src;
                if ($render)
                    $render.=",";
            }            
            $sb->appendLine(sprintf("export default { %s%s}", $render, $src));             
            $src = $sb.'';
        } else {
            $src = sprintf("export default { %s }", $render);
            $sb = new StringBuilder;
            $sb->appendLine("import * as Vue from 'vue';");
            $sb->appendLine($src);
            $src = $sb.'';
        }
        return $src;
    }
}