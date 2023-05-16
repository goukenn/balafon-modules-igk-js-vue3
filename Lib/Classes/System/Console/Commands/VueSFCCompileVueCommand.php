<?php

// @author: C.A.D. BONDJE DOUE
// @filename: CompileVueCommand.php
// @date: 20230101 03:19:38
// @desc: 

namespace igk\js\Vue3\System\Console\Commands;

use IGK\Helper\Activator;
use IGK\Helper\FileBuilderHelper;
use IGK\Helper\IO;
use IGK\Helper\JSon;
use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\svg\SvgDocument;
use IGK\System\Console\Logger;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\System\Html\Css\Builder\ControllerLitteralBuilder;
use IGK\System\Html\Css\CssUtils;
use IGK\System\Html\Dom\Html5Document;
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
class VueSFCCompileVueCommand extends VueCommandBase
{

    var $command = "--vue3:sfc-compile-file";

    var $desc = 'compile vue file and return a js module';

    var $usage = 'file [options]';

    var $options = [];
    /**
     * 
     * @param mixed $command 
     * @param string|null $file 
     * @return void 
     * @throws IGKException 
     */
    public function exec($command, string  $file=null) {
        is_null($file) && igk_die("reequire .vue file");
        if ($g = VueSFCCompiler::Compile($file)){            
            echo $g->to_js('module');
        }
        else {
            Logger::danger("failed to compile");
        }
     }
}