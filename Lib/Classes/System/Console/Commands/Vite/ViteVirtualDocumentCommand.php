<?php
// @author: C.A.D. BONDJE DOUE
// @file: ViteVirtualDocumentCommand.php
// @date: 20230424 09:12:47
namespace igk\js\Vue3\System\Console\Commands\Vite;

use IGK\Controllers\BaseController;
use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use igk\js\Vue3\Libraries\VueRouter;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use igk\js\Vue3\System\IO\VueSFCFile;
use igk\js\Vue3\Vite\Helper\Utility;
use igk\js\Vue3\Vite\ViteApiHelper;
use igk\js\Vue3\Vite\ViteAppUtility;
use igk\js\Vue3\Vite\ViteMenuHelper;
use igk\js\Vue3\Vite\ViteMenuInfo;
use IGK\System\Console\Logger;
use IGK\System\IO\Path;
use IGK\System\Regex\Replacement;
use function igk_resources_gets as __;


///<summary></summary>
/**
 * helper for vite project virtual element 
 * @package igk\js\Vue3\System\Console\Commands\Vite
 */
class ViteVirtualDocumentCommand extends VueCommandBase
{
    var $command = '--vue3:vite-get-document';
    const DOCS = [
        'balafon-corejs-vite-helper' => 'corehelper.js',
        'balafon-corejs' => 'balafon-corejs',
        'balafon-i18n' => '_render_i18n',
        'balafon-routes' => '_render_routes',
        'balafon-menus' => '_render_menus',
        'balafon-api' => '_render_api',
        'balafon-components'=>'_render_components',
        'balafon-view'=>'_load_controller_view',
        'balafon-router-components'=>'_render_routes_component',
    ];
    var $options = [
        '--vite_view_dir'=>'balafon-view: set view directory where to search'
    ];

    var $desc = 'get core documents - that will help do build vite apps';

    var $environemnt;

    public function showUsage()
    {
        $this->showCommandUsage(" document_or_file [options]");

        $tab = array_keys(self::DOCS);
        sort($tab);
        Logger::info("available keys : \n\t". implode(",\n\t", $tab));
    }
    public function exec($command, ?string $docname_or_file = null)
    {
        empty($docname_or_file) && igk_die('docname_or_file required');

        $this->environemnt = $this->environemnt ?? igk_getv($_SERVER, 'NODE_ENV', 'development'); 
        igk_environment()->NoLogEval = 1;
        if (in_array($docname_or_file, array_keys(self::DOCS))) {
            $this->_render_doc($docname_or_file, $command);
        } else {
            Logger::danger(sprintf(__('missing document or file .[%s]'), $docname_or_file));
            return -1;
        }
    }

    protected function _render_doc($id, $command = null)
    {
        if ($id == 'balafon-corejs') {
            $controller = null;
            $sb = igk_sys_balafon_js($controller, false);
            // treat core js - 
            $rp = new Replacement;
            $rp->add("/import\(/", "import(/* @vite-ignore */");
            $sb = $rp->replace($sb);


            echo implode("\n", [
                $sb,
                'export default { igk: window.igk, $igk: window.$igk }'
            ]);
            igk_exit();
        }

        $n = igk_getv(self::DOCS, $id) ?? igk_die('id not found');
        if (method_exists($this, $n)) {
            return call_user_func_array([$this, $n], [$id, $command]);
        }
        $cdoc = igk_current_module()->getDataDir() . '/vite/documents';
        readfile($cdoc . '/' . $n);
        igk_exit();
    }
    protected function _render_i18n($id, $command)
    {
        $ctrl = self::GetController($command->command[2]);
        $lang = igk_getv($command->command, 3, 'en');
        if ($this->environemnt == 'production'){
            $s = 'getNS(import.meta.env.VITE_IGK_APP_NAMESPACE+".configs.i18n") || {messages:{}, legacy:false, fallbackLocale:"en"}';
        } else {
            $s = Vuei18n::BuildLocaleDefinition($ctrl, false, $lang);
        }

        $js =
            /** JS */
            implode('', [
                "import { getNS } from 'virtual:balafon-corejs-vite-helper';",
                'import { reactive } from \'vue\';',
                'const d = ' . $s . ';',
                // create a reactive object on messages t
                'd.messages = reactive(d.messages);',
                'function locale(lang, l){ if (l){',
                'for (let i in l){',                   
                'if (i in d.messages){',
                    'd.messages[i] = { ...l[i], ...d.messages[i] };', // priority to server messages
                '} else {',
                'd.messages[i] = l[i];',
                '}',
                // merging fallback localed
                'if (d.fallbackLocale != i){ d.messages[d.fallbackLocale] = { ...Object.keys(l[i]).reduce((a,b)=>{',
                    'if (typeof(a)!="object"){let i = a;a = {};a[i] = i;}',
                    'a[b] = b;',
                    'return a;}),...d.messages[d.fallbackLocale]} }',
                '}',                
                '} if (lang) d.locale = lang; return d;}',
                // debug prod...
                // 'console.log("i18n", d);',
                'export { locale }',
            ]);
        echo $js;
    }
 
    protected function _render_menus($id, $command)
    {
        $s = '';
        $inject = '';
        if($this->environemnt == 'production'){
            // inject from string 
            readfile(igk_current_module()->getDataDir() . '/vite/documents/menus.js');
            igk_exit();
        } else {
            $ctrl = self::GetController($command->command[2]);
            $ctrl->register_autoload();
            $menu_name = igk_getv($command->command, 3, null);
            
            $data = ViteMenuHelper::LoadMenu($ctrl, $menu_name);
            if (is_array($data)) {
                $lib_keys = igk_getv($data, ViteMenuHelper::LIB_KEYS);
                unset($data[ViteMenuHelper::LIB_KEYS]);
                $g = ViteMenuHelper::Build($data);
                usort($g, function (ViteMenuInfo $a, ViteMenuInfo $b) {
                    $x = $a->index ?? 0;
                    $y = $b->index ?? 0;
                    return strcmp($x, $y);
                });
                $inject = implode("", array_map(function($a, $k){
                    return 'import '.$k.' from \''.$a.'\';';
                }, $lib_keys, array_keys($lib_keys)));

                $s = JSExpression::Stringify((object)$g, (object)[
                    'ignoreNull' => true,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $s = '{}';
            }
        }
        $js =
            /** JS */
            implode('', array_filter([
                'import * as Vue from \'vue\';', 
                'const { h } = Vue;',
                $inject,
                'function menus(key){ const _m = ' . $s . '; return key ? _m[key] : _m;}',
                'export { menus }',
            ]));
        echo $js;
    }
    protected function _render_api($id, $command)
    {
        $ctrl = self::GetController($command->command[2]);
        $ctrl->register_autoload();
        $api_name = igk_getv($command->command, 3, null);

        $data = ViteApiHelper::Load($ctrl, $api_name);
        if (is_array($data)) {
            $s = JSExpression::Stringify((object)$data, (object)[
                'ignoreNull' => true,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $s = '{}';
        }

        $js =
            /** JS */
            implode('', array_filter([                
                sprintf('const _api = %s;', 
                    rtrim(sprintf(file_get_contents(igk_current_module()->getDataDir() . '/vite/documents/api.menu.build.tjs'), $s), ';')
                ),                
                'function api(){ return _api;}',
                'export { api }',
            ]));
        echo $js;
    }

    protected function _render_components(){
        // + | production component route list 
        $js = '';
        if ($this->environemnt == 'production'){
            $js .= '// inject production component';
        }
         else { 
             $js .= "import Home from '@/components/Dashboard/Pages/DashboardHomeComponent.vue';";
             $js .= "import DashboardUser from '@/components/Dashboard/Pages/DashboardUserComponent.vue';";
             $js .= "export { Home, DashboardUser }"; 
        }
        echo $js;
        igk_exit();
    }


    private function _load_controller_view($id, $command){
        if ($ctrl = igk_getv($command->options, '--controller')){
            $ctrl = self::GetController($ctrl);
        }
        $ctrl || igk_die('missing target controller');
        $dir = igk_getv($command->options, '--vite_view_dir', $ctrl->getDeclaredDir()."/ViteViews" );
        $name = igk_getv($command->command, 2);
        $c = Path::Combine($dir, $name); 
        $tf = Path::SearchFile($c, ['.phtml', '.vue']);
        $js = '';
        if ($tf){

            $ext = strtolower(igk_io_path_ext($tf));
            switch($ext){
                case 'vue':
                    $g = new VueSFCFile(); 
                    $g->loadFile($tf); 
                    $js = $g->compile(); 
                    break;
                case 'phtml':
                    // load from -build view ...
                    $src = ViteAppUtility::BuildView($ctrl, $tf);
                    $js .= $src;
                    break;
            }
            echo json_encode([
                'code'=>$js, 
                'file'=>$tf, 
                'type'=>$ext
            ]);
            igk_exit();
            
        } 
        echo $js;
        igk_exit();
    }
    private function _get_app_dir($command){
        return igk_getv($command->options, 'app-dir', igk_getv($_SERVER, 'OLDPWD'));
    }

       /**
     * get rendering routes \ 
     * command balafon-routes controller [route-name] 
     */
    protected function _render_routes($id, $command)
    { 
        if ($this->environemnt == 'production'){ 
            echo 'export function routes(){}';
            igk_exit(0);
        }
        $js = ''; 
        $ctrl = self::GetController($command->command[2]);
        $ctrl->register_autoload();
        $router_name = igk_getv($command->command, 3, null);
        $ref_uri = igk_getv($command->command, 4, null);
        $ref = null; 
        $app_dir = $this->_get_app_dir($command);
        $ref = $this->_initRoute($ctrl, $router_name, $ref_uri, $app_dir );
        $ref->preload = Utility::ViteRouterPreloadScript(); 
        $s = $ref->render();
        $inject = VueSFCUtility::RenderLibraryAsConstantDeclaration($ref->getLibraries(), $globalImport);
        // global js empression script */
        $js = implode('', array_filter([
                'import * as Vue from \'vue\';',
                'import * as VueRouter from \'vue-router\';',
                $globalImport, 
                'function routes(){ ' . $inject . $s . ' return router;}',
                'export { routes }',
            ]));
        echo $js;
    }
    private function _render_routes_component($id, $command){

        $jsjs = '';
        $ctrl = self::GetController($command->command[2]);
        $ctrl->register_autoload();
        $route_name = igk_getv($command->command, 3, null);
   
        $ref_uri = null;
        $app_dir = $this->_get_app_dir($command);
        $ref = $this->_initRoute($ctrl, $route_name, $ref_uri, $app_dir);
        if ($ref){
            VueSFCUtility::RenderLibraryAsConstantDeclaration($ref->getLibraries(), $globalImport, $components);
            $use_components = $components;
            $jsjs .= $globalImport."\n";
            $jsjs .= "export {".implode(",", $use_components)."}";            
        }        
        echo $jsjs; 
        igk_exit();              
    }
    private function _initRoute(BaseController $ctrl, $route_name, $ref_uri, $app_dir){
        $ref = null;
        $options = (object)[
            'sourceDir'=>$app_dir,
            'funcDeclaration'=>true
        ];
        $ref = VueRouter::InitRoute($ctrl, $route_name, $ref_uri, $ref, $options);
        return $ref;
    }
}
