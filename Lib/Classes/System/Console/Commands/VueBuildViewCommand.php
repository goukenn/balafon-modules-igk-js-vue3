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
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Compiler\VueSFCCompilerOptions;
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

    ];
    public function exec($command, ?string $controller=null , ?string $file=null) {
        $ctrl = self::GetController($controller);

        $t = igk_create_notagnode();
        $builder = new HtmlNodeBuilder($t);
        $f = Path::SearchFile($file,['.phtml'], [$ctrl->getDeclaredDir()]);
        if (is_null($f)){
            Logger::danger("missing file :".$file);
            return -1;
        }
        ViewHelper::Inc($f, compact('builder', 't','ctrl'));
        $src = '';
        $options = null; // new VueSFCCompilerOptions;
        $html = $t->render();
        $render = VueSFCCompiler::ConvertToVueRenderMethod($t, $options);
        if (file_exists($js = igk_io_remove_ext($f).VueConstants::VUE_JS_SETUP_EXT)){
            $sb = new StringBuilder();
            if ($render){
                $sb->appendLine("import * as Vue from 'vue';");
                $render.=",";
            }
            if ($options){
                foreach($options->libraries as $k=>$v){
                    // render library in use
                    // $sb->appendLine(sprintf('const {%s} = %s;', implode(",", array_keys($v)), $k));
                    $sb->appendLine(sprintf('import {%s} from \'%s\';', implode(",", array_keys($v)), self::ResolvLibToDev($k)));
                }
            }
            // append i18n if required
            $sb->appendLine("import * as VueI18n from 'vue-i18n';");
            $sb->appendLine(sprintf("export default { %s...%s}", $render, trim(file_get_contents($js), '; ')));
            // $sb->appendLine("export default { render(){ return h('div', 'check'+slots)} }");
            $src = $sb.'';
        }

        echo $src;
        exit;
    }

    static function ResolvLibToDev($d){
        return igk_getv([
            'vue'=>'vue',
            'vue-router'=>'vue-router',
        ],strtolower($d)) ?? igk_die("not resolved : ".$d);
    }

}