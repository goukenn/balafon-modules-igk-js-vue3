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
use igk\js\Vue3\Helpers\JSUtility;
use igk\js\Vue3\Traits\ResolveLibraryTrait;
use igk\js\Vue3\Vite\Compiler\ViteSFCCompiler;
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
    use ResolveLibraryTrait;
    
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
        igk_environment()->NoLogEval = true;
        $app_dir = igk_getv($command->options, '--app-dir', igk_server()->INIT_CWD) ?? dirname($file);        
        $compiler = new ViteSFCCompiler;
        $compiler->app_dir = $app_dir; 
        $compiler->ctrl = $ctrl;               
        $src = $compiler->compile($f);       
        echo $src;
        igk_exit();
    }
}

