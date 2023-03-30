<?php

// @author: C.A.D. BONDJE DOUE
// @filename: CompileVueCommand.php
// @date: 20230101 03:19:38
// @desc: 

namespace igk\js\Vue3\System\Console\Commands;

use IGK\Helper\FileBuilderHelper;
use IGK\Helper\IO;
use igk\js\common\JSExpression;
use igk\js\Vue3\System\Console\VueCommandBase;
use IGK\System\Console\Logger;
use IGK\System\Shell\OsShell;
use igk\webpack\WebpackGeneratorInfo;
use igk\webpack\WebpackHelper;
use igk\webpack\WebpackManifestInfo;
use igk\webpack\WebpackManifestRule;

class CompileVueCommand extends VueCommandBase
{
    var $category = "vue";
    var $command = "--vue3:compile-vue";

    var $distName = 'dist';

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
        $pwd = "/private/var/folders/sp/f7bfk6cx359b61kd9cfp414h0000gn/T/webpacka9IRCP"; // IO::CreateTempDir("webpack");
        $cnf_file = $pwd . "/webpack.config.js";
        chdir($pwd);
        IO::CreateDir('src');
        IO::CreateDir('node_modules');
        copy($file, $cfile = "src/" . basename($file));
        $cfile = "./" . $cfile;
        FileBuilderHelper::Build($data, true, $this);
        Logger::info("install npm");
        // $npm_i = `cd $pwd && npm install `; // @vue/compiler-sfc
        $npm_i = `cd $pwd && npm install --save-dev webpack webpack-cli webpack-manifest-plugin vue@next vue-loader vue-template-compiler  --loglevel=verbose`; // 2>&1`;
        Logger::print($npm_i);
        Logger::info("run webpack ...");
        $result = `webpack --config $cnf_file`;

        echo "result : \n" . $result;
        Logger::print($pwd);
    }
}