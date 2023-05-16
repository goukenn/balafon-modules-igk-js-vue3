<?php


// @author: C.A.D. BONDJE DOUE
// @filename: VueRouter.php
// @date: 20220726 12:35:05
// @desc: bue router library
namespace igk\js\Vue3\Libraries;

use IGK\Controllers\BaseController;
use IGK\Helper\ActionHelper;
use IGK\Helper\ViewHelper;
use igk\js\common\JSAttribExpression;
use igk\js\common\JSExpression;
use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Compiler\VueSFCRenderNodeVisitorOptions;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Components\VueNoTagNode;
use igk\js\Vue3\JS\VueLazyImportExpression;
use igk\js\Vue3\JS\VueLazyLoadExpression;
use igk\js\Vue3\System\Controller\VueControllerMacrosExtension;
use igk\js\Vue3\Vite\RoutedMenuDefinition;
use igk\js\Vue3\Vite\RoutedMenusBase;
use igk\js\Vue3\Vite\ViteMenuHelper;
use igk\js\Vue3\VueConstants;
use igk\js\Vue3\VueHelper;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlItemBase;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\System\Html\HtmlRenderer;
use IGK\System\IO\Path;
use IGK\System\IO\StringBuilder;
use IGK\System\Regex\Replacement;
use IGKException;
use IGKHtmlDoc;
use IGKResourceUriResolver;
use ReflectionException;
use ReflectionMethod;
use stdClass; 

/**
 * vue router library helper
 * @package igk\js\Vue3\Libraries
 */
class VueRouter extends VueLibraryBase
{
    const CDN = "https://unpkg.com/vue-router@4.0.15/dist/vue-router.global.js";
    const OPS_CDN = "https://unpkg.com/vue-router@4.0.15/dist/vue-router.global.prod.js";
    const DEFAULT_ROUTE_FILE = 'vue-router.pinc';
    // type of rendering history
    const vueHashHistory = "createWebHashHistory";
    const vueMemoryHistory = "createMemoryHistory";
    const vueWebHistory = "createWebHistory";
    const MenuRouteOptions = 'vue.routeOptions';



    var $options;

    /**
     * retrieve store routes
     * @return mixed 
     */
    public function getRoutes()
    {
        return $this->m_routes;
    }
    /**
     * init document and return a instance of new router definition    
     * @param IGKHtmlDoc $doc 
     * @param null|BaseController $ctrl 
     * @param string|null $route_name name of configuration file to use
     * @return static 
     * @throws IGKException 
     */
    public static function InitDoc(IGKHtmlDoc $doc, ?BaseController $ctrl = null, string $route_name = null)
    {
        $uri = igk_configs()->get(VueConstants::CNF_VUE_ROUTER_CDN) ?? igk_environment()->isDev() ? self::CDN :  self::OPS_CDN;
        $doc->addTempScript($uri)->activate('defer');
        $ref = new static;
        if ($ctrl) {
            self::InitRoute($ctrl, $route_name, null, $ref);
            // $ctrl::RegisterExtension(VueControllerMacrosExtension::class);
            // $route_name = $route_name ?? self::DEFAULT_ROUTE_FILE;
            // $definition = ViewHelper::Inc($ctrl->configFile($route_name), [
            //     'router' => $ref,
            //     'ctrl' => $ctrl,
            //     'builder'=>new HtmlNodeBuilder(igk_create_notagnode()),
            // ]);
            // if (is_array($definition)) {
            //     $ref->_initDefinition($definition);
            // }
        }
        return $ref;
    }
    public static function InitRoute(?BaseController $ctrl, string $route_name = null, ?string $refUri = null, ?VueRouter &$ref = null)
    {
        $ref = $ref ?? new static;
        $ctrl::RegisterExtension(VueControllerMacrosExtension::class);
        $route_name = $route_name ?? self::DEFAULT_ROUTE_FILE;
        $definition = ViewHelper::Inc($ctrl->configFile($route_name), [
            'router' => $ref,
            'ctrl' => $ctrl,
            'refUri' => $refUri,
            'builder' => new HtmlNodeBuilder(igk_create_notagnode()), // component builder 
        ]);
        if (is_array($definition)) {
            $ref->_initDefinition($definition);
        }
        return $ref;
    }
    /**
     * load ad init definiont 
     * @param mixed $def 
     * @return void 
     * @throws IGKException 
     */
    private function _initDefinition($def)
    {
        foreach ($def as $k => $v) {
            $this->addRoute($k, $v);
        }
    }
    /**
     * history type
     * @var string
     */
    var $type;
    /**
     * id in script
     * @var mixed
     */
    var $id;

    /**
     * base uri in case of webHistory
     * @var mixed
     */
    var $baseUri;

    /**
     * before each expression
     * @var mixed
     */
    var $beforeEach;

    /**
     * 
     * @var mixed js method (to, from, failure)
     */
    var $afterEach;

    var $beforeResolve;

    /**
     * registrated name
     * @var string
     */
    protected $m_name = "vueRouter";
    /**
     * list of routes
     * @var mixed
     */
    private $m_routes = [];

    public function clearRoutes()
    {
        $this->m_routes = [];
    }
    /**
     * 
     * @param string $path 
     * @param mixed|string|array|HtmlNode|VueLazyImportExpression|VueRouteOptions $data component to use \
     * if array : array['template', 'useProps']
     * @return static 
     * @throws IGKException 
     */
    public function addRoute(string $path, $data, $template = false)
    {
        if ($template) {
            if (is_string($data)) {
                $data = ['template' => $data];
            } else if ($data instanceof HtmlItemBase) {
                $data = ['template' => $data];
            }
        } else {
            if (is_string($data)) {
                $options = new VueSFCRenderNodeVisitorOptions;
                $node = new VueNoTagNode();
                $node->load($data);
                $defs = VueSFCCompiler::ConvertToVueRenderMethod($node, $options);
                $data = [$defs];
                $this->m_libraries = array_merge_recursive($this->m_libraries, $options->libraries);
            } else if ($data instanceof HtmlItemBase) {
                $options = new VueSFCRenderNodeVisitorOptions;
                $defs = VueSFCCompiler::ConvertToVueRenderMethod($data, $options);
                $data = [$defs];
                $this->m_libraries = array_merge_recursive($this->m_libraries, $options->libraries);
            }
        }
        $this->m_routes[$path] =  $data;
        return $this;
    }
    /**
     * used to directly include path options 
     * @param string $path 
     * @param string $component_name 
     * @param mixed $options 
     * @return static 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function addRouteWithDefinedComponent(string $path, string $component_name, $options)
    {
        return $this->addRoute($path, JSExpression::Litteral($component_name), $options);
    }
    /**
     * add route definition
     * @param IRouterParams $route 
     * @return $this 
     */
    public function addRouteDefinition(IRouterParams $route)
    {
        $this->m_routes[$route->path] = $route;
        return $this;
    }
    /**
     * add component with children
     * @param string $path 
     * @param mixed|array|IVueComponentDefinition $component 
     * @param mixed $children 
     * @return $this 
     */
    public function addRouteWithChildren(string $path, $component, ?array $children = [], ?string $name = null)
    {
        $this->m_routes[$path] = (object)compact("component", "children", "name");
        return $this;
    }
    public function __construct($type = self::vueWebHistory)
    {
        parent::__construct();
        $this->type = $type ?? self::vueWebHistory;
    }
    /**
     * get history method
     * @return string 
     */
    public function getHistoryMethod(): string
    {
        $t = "createMemoryHistory";
        if ($this->type == self::vueHashHistory) {
            $t = "createWebHashHistory";
        } else if ($this->type == self::vueWebHistory) {
            $t = "createWebHistory";
        }
        return $t;
    }
    /**
     * init rendering
     * @return null|string 
     */
    public function render($option = null): ?string
    {
        $sb = new StringBuilder();
        $t = $this->getHistoryMethod();
        $v_extra = '';
        $_id = $this->getVarName();
        $sb->appendLine("/** vue-router **/");
        $lib_n = '';
        // inject constant helper in application context
        if ($this->m_libraries) {
            $vue_lib = igk_getv($this->m_libraries, 'Vue');
            $lib_n = implode(",", array_keys($vue_lib));
            $sb->appendLine("const Vue = {" . $lib_n . "};");
            $lib_n .= ',';
        } else {
            $sb->appendLine("const {Text, h} = Vue;");
        }
        // $sb->appendLine("const { createRouter, {$t}{$v_extra} } = VueRouter;");
        // + | function argument to use
        $v_targs = "{ createRouter,{$lib_n}{$t}{$v_extra} }";
        // $sb->appendLine("const { createRouter, {$t}{$v_extra} } = igk.js.vue3.vite.lib;");
        $ref_cp = 0;
        $ref_ca = 0;
        $rs = [];
        $_voptions = (object)["objectNotation" => 1];
        $vue_import = [];
        $v_routes = $this->m_routes;
        if ($v_routes)
            foreach ($v_routes as $path => $data) {

                // $data = JSExpression::Stringify([$data], (object)["objectNotation"=>1]);
                $tdata = ["path" => $path, "component" => null];
                // igk_wln_e(__FILE__.":".__LINE__,  $data);
                if (is_string($data)) {
                    $data = JSExpression::Create($data);
                    $tdata["component"] =  $data;
                } else if ($data instanceof VueLazyLoadExpression) {
                    if (!$ref_cp) {
                        // $sb->appendLine("const { loadVueComponent } = igk.js.vue3; ");
                        $vue_import[] = "loadVueComponent";
                        $ref_cp = 1;
                    }
                    $tdata["component"] = JSExpression::Create("()=> loadVueComponent('" . $data->module . "')");
                } else if ($data instanceof VueLazyImportExpression) {
                    if (!$ref_ca) {
                        $ref_ca = 1;
                        $vue_import[] = "importVueComponent";
                    }
                    if ($name = igk_getv($data->options, "name")) {
                        $tdata["name"] = $name;
                    }
                    $tdata["component"] = JSExpression::create("()=>importVueComponent(" . $data->inlineData() . ")");
                } else {
                    // init computent
                    $c = null;
                    if ($data instanceof HtmlNode) {
                        $c = $data;
                        $data = [];
                    } else {
                        if (!(($c = igk_getv($data, "template")) instanceof HtmlNode)) {
                            $c = null;
                        }
                    }
                    if ($c) {
                        // ignore script and style tag...
                        $options = HtmlRenderer::CreateRenderOptions();
                        $options->skipTags = ["style", "script"];
                        $v_tcx = HtmlRenderer::Render($c, $options);
                        $data["template"] = self::evalJSString($v_tcx);
                    } else if (is_array($data) && isset($data["component"])) {
                        $tdata["component"] = $data["component"];
                    } else {
                        $tdata["component"] = (object)$data;
                    }
                }

                $this->_bindExtraProperty($tdata, $data);
                $rs[] = JSExpression::Stringify((object)$tdata, $_voptions);
            }
        if ($vue_import) {
            $sb->appendLine("const {" . implode(",", $vue_import) . "} = igk.js.vue3;");
        }
        $rs = implode(",", $rs);
        $args = null;
        if ($t == self::vueWebHistory) {
            $args = "'{$this->baseUri}'";
            // $args = "'/testapi/vite'";
        }

        $sb->append("const _r = createRouter({history:{$t}({$args}), " .
            "strict: true," .
            "routes: [" .
            $rs .
            // "{'path':'/', component: {template:'<div>home page</div>'}},".
            // "{'path':'/about', component: {template:'<div>about page</div>'}}".
            "]});");
        // global definition method   
        // $sb->append("beforeEach(function(to, from){ console.debug('date loading'); return true; }");    
        //$sb->appendLine(");");
        foreach (["beforeEach", "afterEach", "beforeResolve"] as $k) {
            if ($this->$k) {
                $src = JSExpression::Stringify($this->$k);
                $sb->append("_r.beforeEach({$src});");
            }
        }
        $sb->append("/** end: vue-router **/");
        $ns = 'VueRouter';
        if ($this->options && ($entryNamespace = $this->options->entryNamespace)) {
            $ns = $entryNamespace . '.lib || ' . $ns;
        } else if ($lib_n) {
            $ns = "{{$lib_n}...{$ns}}";
        }
        $sb->append('return _r;');
        $sb->set(sprintf('const %s = (function(%s){%s})(' . $ns . ');', $_id, $v_targs, $sb . ''));
        return $sb;
    }
    public function render_bck($option = null): ?string
    {
        $sb = new StringBuilder();
        $t = $this->getHistoryMethod();
        $v_extra = '';
        $_id = $this->getVarName();
        $sb->appendLine("/** vue-router **/");
        $sb->appendLine("console.log('init router', igk.js.vue3.vite.lib); ");
        // $sb->appendLine("const { createRouter, {$t}{$v_extra} } = VueRouter;");
        $sb->appendLine("const { createRouter, {$t}{$v_extra} } = igk.js.vue3.vite.lib;");
        $ref_cp = 0;
        $ref_ca = 0;
        $rs = [];
        $_voptions = (object)["objectNotation" => 1];
        $vue_import = [];
        $v_routes = $this->m_routes;
        if ($v_routes)
            foreach ($v_routes as $path => $data) {

                // $data = JSExpression::Stringify([$data], (object)["objectNotation"=>1]);
                $tdata = ["path" => $path, "component" => null];
                // igk_wln_e(__FILE__.":".__LINE__,  $data);
                if (is_string($data)) {
                    $data = JSExpression::Create($data);
                    $tdata["component"] =  $data;
                } else if ($data instanceof VueLazyLoadExpression) {
                    if (!$ref_cp) {
                        // $sb->appendLine("const { loadVueComponent } = igk.js.vue3; ");
                        $vue_import[] = "loadVueComponent";
                        $ref_cp = 1;
                    }
                    $tdata["component"] = JSExpression::Create("()=> loadVueComponent('" . $data->module . "')");
                } else if ($data instanceof VueLazyImportExpression) {
                    if (!$ref_ca) {
                        $ref_ca = 1;
                        $vue_import[] = "importVueComponent";
                    }
                    if ($name = igk_getv($data->options, "name")) {
                        $tdata["name"] = $name;
                    }
                    $tdata["component"] = JSExpression::create("()=>importVueComponent(" . $data->inlineData() . ")");
                } else {
                    // init computent
                    $c = null;
                    if ($data instanceof HtmlNode) {
                        $c = $data;
                        $data = [];
                    } else {
                        if (!(($c = igk_getv($data, "template")) instanceof HtmlNode)) {
                            $c = null;
                        }
                    }
                    if ($c) {
                        // ignore script and style tag...
                        $options = HtmlRenderer::CreateRenderOptions();
                        $options->skipTags = ["style", "script"];
                        $v_tcx = HtmlRenderer::Render($c, $options);
                        $data["template"] = self::evalJSString($v_tcx);
                    }
                    $tdata["component"] = $data;
                }

                $this->_bindExtraProperty($tdata, $data);
                $rs[] = JSExpression::Stringify((object)$tdata, $_voptions);
            }
        if ($vue_import) {
            $sb->appendLine("const {" . implode(",", $vue_import) . "} = igk.js.vue3;");
        }
        $rs = implode(",", $rs);
        $args = null;
        if ($t == self::vueWebHistory) {
            $args = "'{$this->baseUri}'";
            // $args = "'/testapi/vite'";
        }

        $sb->append("const {$_id} = createRouter({history:{$t}({$args}), routes: [" .
            $rs .
            // "{'path':'/', component: {template:'<div>home page</div>'}},".
            // "{'path':'/about', component: {template:'<div>about page</div>'}}".
            "]}");
        // global definition method   
        // $sb->append(").beforeEach(function(to, from){ console.debug('date loading'); return true; }");     

        $sb->appendLine(");");
        foreach (["beforeEach", "afterEach", "beforeResolve"] as $k) {
            if ($this->$k) {
                $src = JSExpression::Stringify($this->$k);
                $sb->append("{$_id}.beforeEach({$src});");
            }
        }
        $sb->appendLine("/** end: vue-router **/");
        // $sb->append("{$_id}.beforeEach((to, from)=>{ console.debug('must be set after id'); return false; });");
        return $sb;
    }
    /**
     * get extra bind options from array
     * @param mixed $tdata data to update
     * @param mixed $data array to get
     * @return void 
     */
    private function _bindExtraProperty(&$tdata, $data)
    {
        if (!is_array($data)) {
            return;
        }
        if (isset($data["useProps"])) {
            $tdata["props"] = (bool)igk_bool_val($data["useProps"]);
        }
    }
    public function useLibrary($option = null): array
    {
        return [$this->getVarName(), null];
    }
    /**
     * create a router definition interface
     * @return IRouterParams 
     */
    public function createRouteDefinition(): IRouterParams
    {
        $d = new RouterParams();
        return $d;
    }
    /**
     * load route definition
     * @param array $routeDef 
     * @return array 
     */
    public function load(array $routeDef)
    {
        foreach ($routeDef as $route => $def) {
            $this->addRoute($route, $def);
        };
        return $this;
    }


    static function evalJSString(string $s)
    {
        $s = preg_replace("#</script\s*>#i", "<\\/script>", $s);
        return $s;
    }
    public function getVarName()
    {
        return $this->id ?? 'router';
    }

    /**
     * import component definition with lazy import technique
     * @param mixed $name 
     * @param mixed $ctrl 
     * @return void 
     */
    public function lazyImport($name, $ctrl = null)
    {
        $ctrl = $ctrl ?? ViewHelper::CurrentCtrl();
        $file = Path::Combine($ctrl->getVueAppDir(), $name);
        $uri = IGKResourceUriResolver::getInstance()->resolveOnly($file);
        return VueHelper::LazyLoad($uri);
    }
    public function view($name)
    {
        $ctrl = $ctrl ?? ViewHelper::CurrentCtrl();
        $file = Path::Combine($ctrl->getVueAppDir(), $name);
        // $uri = IGKResourceUriResolver::getInstance()->resolveOnly($file);
        $t = new VueNoTagNode();
        ViewHelper::Inc($file . '.phtml', [
            'ctrl' => $ctrl,
            't' => $t,
            'builder' => new HtmlNodeBuilder($t)
        ]);
        return $t; // VueHelper::LazyLoad($uri);
    }
    /**
     * lazy load request. 
     * @param string $request 
     * @return VueLazyLoadExpression 
     */
    public function lazyImportRequest(string $request)
    {
        empty($request = ltrim($request, "/")) && igk_die("empty request not allowed");
        return VueHelper::LazyLoad(ltrim($request, "/"));
    }

    /**
     * 
     * @param string $class_name 
     * @param string $path 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function injectClassDefinition(string $class_name, string $path = "/", ?string $refUri = null)
    {
        $routes = VueRouterUtility::BuildRouteFrom($class_name, $path, $refUri);
        if ($routes) {
            foreach ($routes as $r) {
                $route = $this->addRoute(
                    $r->path,
                    [
                        'component' => JSExpression::Litteral($r->component)
                    ]
                );
                $route->name = $r->name;
            }
        }
    }

    public function injectClassesDefinition($ctrl, array $class_name, string $path = "/", ?string $refUri = null)
    {
        foreach ($class_name as $cl) {
            if ($cl = $ctrl->resolveClass($cl)) {
                $this->injectClassDefinition($cl, $path, $refUri);
            }
        }
    }
    /**
     * inject action class as route path
     * @param BaseController $ctrl 
     * @param string $action_path 
     * @param null|string $refUri 
     * @return bool 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function injectClassActionDefinition(BaseController $ctrl, string $action_path, ?string $refUri = null)
    {
        $p = \Actions::class . '\\' . $action_path;
        $cl = $ctrl->resolveClass($p);
        if ($cl) {
            $this->injectClassDefinition($cl, ActionHelper::GetActionUri($ctrl, $p), $refUri);
            return true;
        }
        return false;
    }
    /**
     * inject menus as route path
     * @param BaseController $ctrl 
     * @param array $classes 
     * @return void 
     */
    public function injectRoutedMenuRoutes(BaseController $ctrl, array $classes)
    {
        $helper = new ViteMenuHelper;
        while (count($classes) > 0) {
            $class_name = array_shift($classes);
            RoutedMenusBase::BindClass($class_name, $ctrl, $helper, function($routed_method){
                if ($routed_method->route)
                    $this->addRoute($routed_method->route, $routed_method->component);     
            }); 
        }
    }
    /**
     * import component
     * @param string $path 
     * @param string $name 
     * @return null|object litteral expression  
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function component(string $path, string $name)
    {
        $this->m_libraries[$path] = $name;
        return JSExpression::Litteral($name);
    }

    public function importComponent(string $path, string $name){        
        $this->m_libraries[$path] = $name;
    }
}
