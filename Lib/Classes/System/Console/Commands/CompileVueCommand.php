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
class CompileVueCommand extends VueCommandBase
{
    var $command = "--vue3:compile-vue";

    var $distName = 'dist';

    var $desc = 'compile .vue as single start project with webpack. use (npm) as default package manager';
    var $options = [
        "--manager:[name]" => "set default package manager. value can be npm or yarn",
        "--title:[title]" => "set document title",
        "--entry-config:[name]" => "set entry configuration. default is igk.js.vue3.configs",
        "--output:[dir]"=>"set ouput directory",
        "--dependOn:[packagelist]"=>"set package list",
        "--devDependOn:[packagelist]"=>"set package list",
    ];
    public function showUsage()
    {
        parent::showCommandUsage('file [controller] [options]');
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
    public function exec($command, string $file = null, ?string $controller=null)
    {
        if (is_null($file) || !is_file($file)) {
            Logger::danger('required entry file');
            return -1;
        }
        $ctrl = $controller ? self::GetController($controller) : null;

        // + | --------------------------------------------------------------------
        // + | check for webpack         
        // + |
        $webpack = igk_require_module(\igk\webpack::class, null, 0, 0);

        if (!$webpack) {
            Logger::danger(\igk\webpack::class, "webpack module required");
            return -2;
        }
        $manager = igk_getv($command->options, "--manager", "npm");
        if (!in_array($manager, ["npm", "yarn"])) {
            $manager = 'npm';
        }
        $tab = (array)WebpackHelper::CheckNpm();
        $cfile = "";
        //
        $data["package.json"] = function ($f) {
            $js = new JsonPackage;
            $js->license = 'MIT';
            $js->scripts = [
                "serve" => "vue-cli-service serve",
                "build" => "vue-cli-service build",
            ];
            igk_io_w2file($f, JSon::Encode($js, (object)['ignore_empty' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        };

        if ($ctrl){
            $data["public/assets/css/main.css"] = function($f)use($command, $ctrl){
                $pwd = getcwd();
                $g = new ControllerLitteralBuilder;
                $g->controller = $ctrl;
                $dir = Path::Combine($pwd, "public/assets");
                $g->outputDir = dirname($dir);
                $g->outputFile = $f;            
                $g->build();               
      
            };
        }
        $data["public/index.html"] = function ($f) use ($command) {
            $title = igk_getv($command->options, "--title", "CompileVueCommand");
            $html5 = new Html5Document;
            $html5->setTitle($title);
            $html5->setMeta("charset", "utf-8");
            $header = $html5->getHead();
            $header->link()->setAttributes(["rel"=>"stylesheet", "href"=>"/assets/css/main.css"]);
            
            $html5->getBody()->div()->setAttributes(["id" => "app"]);
            igk_io_w2file($f, $html5->render());
        };
        #region // for vite configurion 2023
        $data["index.html"] = function ($f) use ($command, $file) {
            $title = igk_getv($command->options, "--title", "Vite App");
            $html5 = new Html5Document;
            $html5->setTitle($title);
            $html5->setMeta("charset", "utf-8");
            $header = $html5->getHead();
            $header->link()->setAttributes(["rel"=>"stylesheet", "href"=>"/assets/css/main.css"]);
            $body = $html5->getBody();
            $body->div()->setAttributes(["id" => "app"]);
            $body->script()->setAttributes([
                'type' => 'module',
                'src' => '/src/' . igk_io_basenamewithoutext($file) . '.js'
            ]);
            igk_io_w2file($f, $html5->render());
        };
        // 2023 - recommandation
        $data["vite.config.js"] = function ($file) {
            $sb = new StringBuilder;
            $sb->appendLine(file_get_contents(igk_current_module()->getDataDir() . "/vite/vite.config.js"));
            igk_io_w2file($file, $sb);
        };
        $data["favicon.ico"] = function ($f) use ($command) {
            self::_buildFavicon($f, $command);
        };
        #endregion

        $data["public/favicon.ico"] = function ($f) use ($command) {
            self::_buildFavicon($f, $command);
        };
        $main = igk_io_basenamewithoutext($file);
        $data["src/main.js"] = function ($f) use ($file, $command) {
            $entry = igk_getv($command->options, "--entry-config", "igk.js.vue3.configs");
            $target = igk_getv($command->options, "--target", "#app");
            $sb = new StringBuilder;
            $sb->appendLine('import { createApp } from \'vue\';');
            $sb->appendLine('import Main from \'./' . basename($file) . '\';');
            if ($entry) {
                $src = file_get_contents(igk_current_module()->getDataDir()."/scaffold/main.js");
                $rp = new Replacement;
                $rp->add("/<% igk.js.vue3.configs.target %>/", $target);
                $rp->add("/<% igk.js.vue3.configs.entry %>/", $entry);
                $sb->append($rp->replace($src));
            } else {
                $data = [
                    'createApp(Main)'
                ];
                // inject properties definitions from base controller
                $data[] = '.mount("' . $target . '");';          
                $sb->appendLine(implode("", $data));
            }
            igk_io_w2file($f, $sb);
        };
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
                "publicPath" => "", // for manifest to not prefix with 'auto'
                "path" => JSExpression::Create("path.resolve(__dirname, '$dist')"),
                "filename" => 'assets/js/[name].js',
                'clean' => true
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
        $pwd = igk_getv($command->options, "--output",  IO::CreateTempDir("webpack"));
        list($core, $dev_core) = self::_getCommandDev($command);      

        //+| build project 
        IO::CreateDir($pwd);

        $cnf_file = $pwd . "/webpack.config.js";
        chdir($pwd);
        IO::CreateDir('src');
        IO::CreateDir('node_modules');
        IO::CreateDir('public/assets/js');
        IO::CreateDir('public/assets/css');
        IO::CreateDir('public/assets/img');
        copy($file, $cfile = "src/" . basename($file));
        $cfile = "./" . $cfile;
        FileBuilderHelper::Build($data, true, $this);
       

        if ($manager == 'npm') {
            Logger::info("install with npm");
            $npm_i = `cd $pwd && npm install {$core} && npm install --save-dev {$dev_core} --loglevel=verbose 2>&2`; // 2>&1`;
            Logger::print($npm_i);
        } else if ($manager == 'yarn') {
            Logger::info("install with yarn");
            $npm_i = `cd $pwd && yarn add {$core} && yarn add -D {$dev_core} 1>&2 2>&2`; // 2>&1`;
            Logger::print($npm_i);
        }
        Logger::info("run webpack ...");
        $result = `webpack --config $cnf_file 1>&2 2>&2 && echo done;`;
        echo "result : \n" . $result;
        if (trim($result)=='done'){
            Logger::success("Done");
            Logger::print($pwd);
        }
    }

    private static function _buildFavicon(string $f)
    {
        if (class_exists(IGKGD::class)) {
            $gd = IGKGD::Create(48, 48);
            $gd->clearf(1.0);
            igk_io_w2file($f, $gd->RenderText());
        } else {
            $svg = new SvgDocument;
            $svg->setSize(48, 48);
            igk_io_w2file($f, $svg->render());
        }
    }
    static function _getCommandDev($command){
        $default_json_package = @json_decode(file_get_contents(igk_current_module()->getDataDir() . "/package.json"));
        $dependencies = [];
        array_filter((array)$default_json_package->dependencies, function ($a, $k) use (&$dependencies) {
            $dependencies[] = $k . "@" . $a;
        }, ARRAY_FILTER_USE_BOTH);

        $core =  [
            'core-js',
            'vue@^3.2.47',
            '@babel/core@^7.0.0',
            '@vue/cli-service',
            'vite @vitejs/plugin-vue',
            'vue-router@next vuex'
        ];
        $dev_core=['webpack webpack-cli webpack-manifest-plugin vue-loader vue-template-compiler'];

        if ($depends = igk_getv($command->options, "--dependOn")){
            if (!is_array($depends)){
                $depends = [$depends];
            }
            $core = array_merge($core, $depends);
        }
        if ($depends = igk_getv($command->options, "--devDependOn")){
            if (!is_array($depends)){
                $depends = [$depends];
            }
            $dev_core = array_merge($dev_core, $depends);
        }

        $core = implode(' ', $core);
        $dev_core = implode(' ', $dev_core);
        return [$core, $dev_core];
    }
}
