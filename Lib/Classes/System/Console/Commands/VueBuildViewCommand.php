<?php

// @author: C.A.D. BONDJE DOUE
// @filename: VueBuildViewCommand.php
// @date: 20230101 03:19:38
// @desc: 

namespace igk\js\Vue3\System\Console\Commands;

use IGK\Helper\Activator;
use IGK\Helper\FileBuilderHelper;
use IGK\Helper\IO;
use IGK\Helper\JSon;
use IGK\Helper\ViewHelper;
use igk\js\common\JSExpression;
use igk\js\common\JSTokenReader;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Compiler\VueSFCCompilerOptions;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Components\VueComponentHost;
use igk\js\Vue3\Components\VueComponentNode;
use igk\js\Vue3\Components\VueNoTagNode;
use igk\js\Vue3\Vite\ViteAppManagement;
use igk\js\Vue3\VueConstants;
use igk\svg\SvgDocument;
use IGK\System\Console\Logger;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\System\Html\Css\Builder\ControllerLitteralBuilder;
use IGK\System\Html\Css\CssUtils;
use IGK\System\Html\Dom\Html5Document;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;
use IGK\System\Npm\JsonPackage;
use IGK\System\Regex\Replacement;
use IGK\System\Shell\OsShell;
use igk\webpack\WebpackGeneratorInfo;
use igk\webpack\WebpackHelper;
use igk\webpack\WebpackManifestInfo;
use igk\webpack\WebpackManifestRule;
use IGKException;
use IGKGD;
use ReflectionException;

/**
 * 
 * @package igk\js\Vue3\System\Console\Commands
 */
class VueBuildViewCommand extends VueCommandBase
{
    var $command = "--vue3:build-view";
    var $desc = 'Build view.phtml and view.vue3-setup.js to Js for import in vue apps';
    var $options = [
        '--app-dir:dir'=>'set application directory'
    ];
    public function exec($command, ?string $controller=null , ?string $file=null) {
        $ctrl = self::GetController($controller);
        $f = Path::SearchFile($file,['.phtml'], [$ctrl->getDeclaredDir()]);
        if (is_null($f)){
            Logger::danger("missing file :".$file);
            return -1;
        }
        $t = new VueComponent('div'); 
        igk_ctrl_bind_css($ctrl, $t, igk_io_basenamewithoutext($f));
        
        $builder = new HtmlNodeBuilder($t);
        $app_dir = igk_getv($command->options, '--app-dir', igk_server()->INIT_CWD) ?? dirname($file);
        
        $vite = new ViteAppManagement($ctrl, $app_dir);
        $library = new ViteLibraryManagment($vite);
        ViewHelper::Inc($f, compact('builder', 't','ctrl', 'vite', 'library'));
        $src = '';
        $options = null; 
        $render = VueSFCCompiler::ConvertToVueRenderMethod($t, $options);
        if (file_exists($js = igk_io_remove_ext($f).VueConstants::VUE_JS_SETUP_EXT)){
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
            if ($import = self::GetImport($src)){
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
        echo $src;
        igk_exit();
    }
    static function GetImport(& $src):?array{
        $imports = [];
        $l = JSTokenReader::GetAllToken($src);
        $v = '';
        $i = false; // check of import 
        $end = false;
        $append =false;
        //wait until litteral to close 
        $litteral = false;
        $skip = false;
        $tv = '';
        while(!$end && (count($l)>0)){
            $q = array_shift($l);
            $tv = $q[1];
            switch($q[0]){
                case JSTokenReader::TOKEN_LITTERAL_STRING:
                    if ($i){
                        $litteral = true;
                    }
                    break;
                case JSTokenReader::TOKEN_RESERVERD_WORD:                
                    if (!$i){
                        if ($tv=='import'){
                            $i = true;                        
                        }
                    } 
                    break;
                default:
                    if ($i && $litteral && in_array($q[1], [";","\n"])){
                        $append = true;
                        $litteral = false;
                    }
                break;
            }
            if (!$i && ($q[1]=='(')){
                $end = true;
                continue;
            }

            if ($i){                
                if (empty(trim($tv))){
                    if (!$skip){
                        $tv =' ';
                        $skip = true;
                    }
                    else{
                        $tv ='';
                    }
                } else {
                    $skip = false;
                }
            }
            $v.= $tv;
            if ($append){
                $imports[] = $v; 
                $v = '';
                $append = false;
                $i = false;
                $litteral = false;
            }
        } 
        if ($i && !empty($v)){
            $imports[] = trim($v); 
            $v = '';
        }
        // combine rest of token 
        $src =  $tv.implode("" , array_map(function($a){ return $a[1]; }, $l));
        return $imports;
    }

    static function ResolvLibToDev($d){
        return igk_getv([
            'vue'=>'vue',
            'vue-router'=>'vue-router',
        ],strtolower($d)) ?? igk_die("not resolved : ".$d);
    }

}


class ViteLibraryManagment{
    private $m_imports = [];
    /**
     * import library to use in vue
     * @param string $path 
     * @param null|string $name 
     * @return void 
     */
    public function import(string $path, ?string $name = null){
        $this->m_imports[$path] = $name ?? igk_io_basenamewithoutext($path);
    }
    /**
     * get imports library
     * @return array 
     */
    public function getLibs(){
        return $this->m_imports;
    }
}