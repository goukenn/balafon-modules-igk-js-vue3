<?php

// @author: C.A.D. BONDJE DOUE
// @filename: CompileVueCommand.php
// @date: 20230101 03:19:38
// @desc: 

namespace igk\js\Vue3\System\Console\Commands;

use IGK\Helper\FileBuilderHelper;
use IGK\Helper\IO;
use igk\js\common\JSExpression; 
use IGK\System\Console\Logger;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\System\Shell\OsShell;
use igk\webpack\WebpackGeneratorInfo;
use igk\webpack\WebpackHelper;
use igk\webpack\WebpackManifestInfo;
use igk\webpack\WebpackManifestRule;
use IGKException;
use ReflectionException;

/**
 * 
 * @package igk\js\Vue3\System\Console\Commands
 */
class CompileVueCommand extends VueCommandBase
{ 
    var $command = "--vue3:compile-vue";

    var $distName = 'dist';

    var $desc = 'compile .vue to balafon entries. use npm as default package manager';
    var $options = ["--manager:[name]"=>"default package manager. value can be npm or yarn"];
    public function showUsage(){
        parent::showCommandUsage('file [options]');
    }
    /**
     * 
     * @param mixed $command 
     * @param string|null $file 
     * @return int|void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     * @throws EnvironmentArrayException 
     */
    public function exec($command, string $file = null)
    {

        if (is_null($file) || !is_file($file)) {
            return -1;
        }
        // + | --------------------------------------------------------------------
        // + | check for webpack         
        // + |
        $webpack = igk_require_module(\igk\webpack::class, null, 0, 0);

        if (!$webpack) {
            Logger::danger(\igk\webpack::class, "webpack module required");
            return -2;
        }
        $manager = igk_getv($command->options, "--manager", "npm");
        if (!in_array($manager , ["npm", "yarn"])){
            $manager = 'npm';
        }
        $tab = (array)WebpackHelper::CheckNpm();
        $cfile = "";

        $data["webpack.config.js"] = function ($f) use (&$cfile) {
            $g = new WebpackManifestInfo();
            $g->mode = "production";
            $g->entry = $cfile;

            $rule = new WebpackManifestRule();
            $rule->test = JSExpression::CreateRegex('/\.vue$/i');
            $rule->use = ['vue-loader'];
            $g->addRule($rule);
            // $gen = new WebpackGeneratorInfo;
            // $gen->filename = "assets/img/[name][ext]";
            // $g->addRule(WebpackManifestRule::createAssetRule("/.(png|jpeg|jpg|svg)/i", $gen));
            // $g->clean = true;
            $dist = ltrim($this->distName, '/');
            $g->output = [
                "publicPath"=>"", // for manifest to not prefix with 'auto'
                "path" => JSExpression::Create("path.resolve(__dirname, '$dist')"),
                "filename" => 'assets/js/[name].js',
                'clean'=>true
            ];
            $g->plugins = [
                "new VueLoaderPlugin()",
                "new WebpackManifestPlugin()",
            ];
            // + | --------------------------------------------------------------------
            // + | write header
            // + |            
            $s  = "";
            $s .= "const path = require('path');";
            $s .= "const { VueLoaderPlugin } = require('vue-loader');";
            $s .= "const { WebpackManifestPlugin } = require('webpack-manifest-plugin');";
            // + | --------------------------------------------------------------------
            // + | generate exta file
            // + |            
            $s .= "module.exports = " . JSExpression::Stringify($g, (object)[
                "objectNotation" => 1,
                "ignoreNull" => 1
            ]);
            
            igk_io_w2file($f, $s);
        };
        $file = realpath($file);
        $pwd = IO::CreateTempDir("webpack");
        $cnf_file = $pwd . "/webpack.config.js";
        chdir($pwd);
        IO::CreateDir('src');
        IO::CreateDir('node_modules');
        copy($file, $cfile = "src/" . basename($file));
        $cfile = "./" . $cfile;
        FileBuilderHelper::Build($data, true, $this);
        if ($manager == 'npm'){
            Logger::info("install npm");        
            $npm_i = `cd $pwd && npm install --save-dev webpack webpack-cli webpack-manifest-plugin vue@next vue-loader vue-template-compiler  --loglevel=verbose`; // 2>&1`;
            Logger::print($npm_i);
        }else if ($manager=='yarn'){
            Logger::info("install yarn");        
            $npm_i = `cd $pwd && yarn add vue@next && yarn add -D webpack webpack-cli webpack-manifest-plugin vue-loader vue-template-compiler 1>&2 2>&2`; // 2>&1`;
            Logger::print($npm_i);
        }
        Logger::info("run webpack ...");
        $result = `webpack --config $cnf_file`;

        echo "result : \n" . $result;
        Logger::print($pwd);
    }
}