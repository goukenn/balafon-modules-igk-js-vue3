<?php

namespace igk\js\Vue3\Vite\Compiler;

use IGK\Controllers\BaseController;
use IGK\Helper\Activator;
use IGK\Helper\ViewHelper;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Helpers\JSUtility; 
use igk\js\Vue3\Traits\ResolveLibraryTrait;
use igk\js\Vue3\Vite\Compiler\ViteLibraryManagment;
use igk\js\Vue3\Vite\ViteAppManagement;
use igk\js\Vue3\VueConstants;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\System\IO\StringBuilder;

/**
 * compile .vue and .phtml file
 * @package igk\js\Vue3\Vite\Compiler
 */
class ViteSFCCompiler{
    use ResolveLibraryTrait;
    
    var $autocache = false;
    var $app_dir;
    var $ctrl;
    var $funcDeclaration;
    private $m_library;


    public function compile(string $file, bool $minify=false){
        $f = $file;
        $ctrl = $this->ctrl;
        $app_dir = $this->app_dir;
        $t = new VueComponent('div'); 
        igk_ctrl_bind_css($ctrl, $t, igk_io_basenamewithoutext($f)); 
        $builder = new HtmlNodeBuilder($t);        
        $vite = new ViteAppManagement($ctrl, $app_dir);
        $library = new ViteLibraryManagment($vite);
        igk_environment()->vuelibaryManager = $library;
        $props = & $library->getProps();
        ob_start();
        $ts = ViewHelper::Inc($f, compact('builder', 't','ctrl', 'vite', 'library', 'props'));
        $g = ob_get_contents();
        ob_end_clean();
        if (is_string($g)){
            igk_debug(1);
            $t->load($g);
            igk_debug(0);
        }
        unset($g);

        igk_environment()->vuelibaryManager = null;
        $src = '';
        $options = null; 
        $this->m_library = $library;
        
        $render = VueSFCCompiler::ConvertToVueRenderMethod($t, $options);
        if (file_exists($js = igk_io_remove_ext($f).VueConstants::VUE_JS_SETUP_EXT)){
            $sb = new StringBuilder();
            $header = new StringBuilder();
            if ($render){
                $header->appendLine("import * as Vue from 'vue';"); 
            }
            if ($options){
                foreach($options->libraries as $k=>$v){
                    // render library in use
                    // $sb->appendLine(sprintf('const {%s} = %s;', implode(",", array_keys($v)), $k));
                    $header->appendLine(sprintf('import {%s} from \'%s\';', implode(",", array_keys($v)), self::ResolvLibToDev($k)));
                }
            }
            if ($libs = $library->getLibs()){
                foreach($libs as $k=>$v){
                    $header->appendLine(sprintf('import %s from \'%s\';',$v, $k));
                }
            }
            // append i18n if required
            $header->appendLine("import * as VueI18n from 'vue-i18n';");

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
            if ($this->funcDeclaration){
                $sb->appendLine(sprintf("(()=>({%s%s}))()", $render, $src));      
            }else{
                $sb->appendLine($header); 
                $sb->appendLine(sprintf("export default { %s%s}", $render, $src));             
            }
            $src = $sb.'';
        } else {
            // $components = $library->getComponents();
            // $lit = $components ? sprintf('{%s}', implode(",", $components)) : '';
            $comp = '';// $components ? sprintf("const %s = appComponents;", $lit) : '';
            $sb = new StringBuilder; 
                   
            if ($this->funcDeclaration){
                $sb->appendLine(sprintf("(()=>{ %s return {%s};})()",$comp, $render));      
            }else{                 
                $sb->appendLine("import * as Vue from 'vue';");
                $sb->appendLine(sprintf("export default { %s }", $render));
            }
            $src = $sb.'';
        }

        $src.= !$props->isEmpty() ? ", props:".$props->litteral() : "";
        if ($minify){
            $src = igk_js_minify($src);
        }
        return $src;
    }

    public static function BuildComponent(BaseController $ctrl, string $file, $options = null){
        $compiler = new static;
        $compiler->ctrl = $ctrl;
        if ($options){
            $compiler->app_dir = $options->sourceDir;
            Activator::BindProperties($compiler, $options);            
        }else {
            $compiler->app_dir = dirname($file); /// $options->sourceDir;
        }
        $src= $compiler->compile($file, igk_environment()->isOPS());
        return $src;
    }
}