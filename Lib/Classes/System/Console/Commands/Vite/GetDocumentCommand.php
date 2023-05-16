<?php
// @author: C.A.D. BONDJE DOUE
// @file: GetDocumentCommands.php
// @date: 20230424 09:12:47
namespace igk\js\Vue3\System\Console\Commands\Vite;

use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCUtility;
use igk\js\Vue3\Libraries\i18n\Vuei18n;
use igk\js\Vue3\Libraries\VueRouter;
use igk\js\Vue3\System\Console\Commands\VueCommandBase;
use igk\js\Vue3\Vite\ViteApiHelper;
use igk\js\Vue3\Vite\ViteMenuHelper;
use igk\js\Vue3\Vite\ViteMenuInfo;
use IGK\System\Console\Logger;
use IGK\System\Regex\Replacement;

///<summary></summary>
/**
 * helper for vite project virtual element 
 * @package igk\js\Vue3\System\Console\Commands\Vite
 */
class GetDocumentCommand extends VueCommandBase
{
    var $command = '--vue3:vite-get-document';
    const DOCS = [
        'balafon-corejs-vite-helper' => 'corehelper.js',
        'balafon-corejs' => 'balafon-corejs',
        'balafon-i18n' => '_render_i18n',
        'balafon-routes' => '_render_routes',
        'balafon-menus' => '_render_menus',
        'balafon-api' => '_render_api',
    ];
    var $options = [];

    var $desc = 'get core documents - that will help do build vite apps';

    public function showUsage()
    {
        $this->showCommandUsage(" document_or_file [options]");
    }
    public function exec($command, ?string $docname_or_file = null)
    {
        empty($docname_or_file) && igk_die('docname_or_file required');

        if (in_array($docname_or_file, array_keys(self::DOCS))) {
            $this->_render_doc($docname_or_file, $command);
        } else {
            Logger::danger('missing route name.');
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
        $s = Vuei18n::BuildLocaleDefinition($ctrl, false, $lang);
        $js =
            /** JS */
            implode('', [
                'import { reactive } from \'vue\';',
                'const d = ' . $s . ';',
                // create a reactive object on messages t
                'd.messages = reactive(d.messages);',
                'function locale(lang, l){ if (l){',
                'for (let i in l){',                   
                'if (i in d.messages){',
                'd.messages[i] = { ...d.messages[i], ...l[i]};',
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
                'export { locale }',
            ]);
        echo $js;
    }
    protected function _render_routes($id, $command)
    {
        $ctrl = self::GetController($command->command[2]);
        $ctrl->register_autoload();
        $router_name = igk_getv($command->command, 3, null);
        $ref_uri = igk_getv($command->command, 4, null);
        $ref = VueRouter::InitRoute($ctrl, $router_name, $ref_uri);
        $s = $ref->render();
        $inject = VueSFCUtility::RenderLibraryAsConstantDeclaration($ref->getLibraries(), $globalImport);
        $js =
            /** JS */
            implode('', array_filter([
                'import * as Vue from \'vue\';',
                'import * as VueRouter from \'vue-router\';',
                $globalImport, 
                'function routes(){ ' . $inject . $s . ' return router;}',
                'export { routes }',
            ]));
        echo $js;
    }
    protected function _render_menus($id, $command)
    {
        $ctrl = self::GetController($command->command[2]);
        $ctrl->register_autoload();
        $menu_name = igk_getv($command->command, 3, null);

        $data = ViteMenuHelper::LoadMenu($ctrl, $menu_name);
        if (is_array($data)) {
            $g = ViteMenuHelper::Build($data);
            usort($g, function (ViteMenuInfo $a, ViteMenuInfo $b) {
                $x = $a->index ?? 0;
                $y = $b->index ?? 0;
                return strcmp($x, $y);
            });
            $s = JSExpression::Stringify((object)$g, (object)[
                'ignoreNull' => true,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $s = '{}';
        }
        $js =
            /** JS */
            implode('', array_filter([
                'import * as Vue from \'vue\';',
                'import IonIcon from \'@/components/IonIcon.vue\';',
                'const { h } = Vue;',
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
                'function api(){ return ' . $s . ';}',
                'export { api }',
            ]));
        echo $js;
    }
}
