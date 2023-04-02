<?php


// @author: C.A.D. BONDJE DOUE
// @filename: VueRouter.php
// @date: 20220726 12:35:05
// @desc: bue router library
namespace igk\js\Vue3\Libraries;

use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use igk\js\common\JSAttribExpression;
use igk\js\common\JSExpression;
use igk\js\Vue3\JS\VueLazyImportExpression;
use igk\js\Vue3\JS\VueLazyLoadExpression;
use igk\js\Vue3\VueConstants;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlRenderer;
use IGK\System\IO\StringBuilder;
use IGKException;
use IGKHtmlDoc;
use ReflectionException;
use stdClass;

/**
 * vue router library helper
 * @package igk\js\Vue3\Libraries
 */
class VueRouter extends VueLibraryBase{    
    const CDN = "https://unpkg.com/vue-router@4.0.15/dist/vue-router.global.js";
    const OPS_CDN = "https://unpkg.com/vue-router@4.0.15/dist/vue-router.global.prod.js";

    // type of rendering history
    const vueHashHistory = "createWebHashHistory";
    const vueMemoryHistory = "createMemoryHistory";
    const vueWebHistory = "createWebHistory";

    /**
     * init document and return a instance of new router definition
     * @param IGKHtmlDoc $doc 
     * @return static 
     * @throws IGKException 
     */
    public static function InitDoc(IGKHtmlDoc $doc, ?BaseController $ctrl=null, string $route_name=null){
        $uri = igk_configs()->get(VueConstants::CNF_VUE_ROUTER_CDN) ?? igk_environment()->isDev()? self::CDN :  self::OPS_CDN;
        $doc->addTempScript($uri)->activate('defer');
        $ref = new static;
        if ($ctrl){
            $route_name = $route_name ?? 'vue-router.pinc';
            ViewHelper::Inc($ctrl->configFile($route_name),[
                'router'=>$ref,
                'ctrl'=>$ctrl
            ]);
        }
        return $ref;
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
    private $m_routes;

    public function clearRoutes(){
        $this->m_routes = [];
    }
    /**
     * 
     * @param string $path 
     * @param mixed|array|HtmlNode|VueLazyImportExpression|VueRouteOptions $data component to use \
     * if array : array['template', 'useProps']
     * @return static 
     * @throws IGKException 
     */
    public function addRoute(string $path, $data){         
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
    public function addRouteDefinition(IRouterParams $route){        
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
    public function addRouteWithChildren(string $path, $component, ?array $children=[], ?string $name=null){
        $this->m_routes[$path] = (object)compact("component", "children" , "name");
        return $this;
    }
    public function __construct($type= self::vueWebHistory)
    {
        parent::__construct();
        $this->type =$type ?? self::vueWebHistory;
    }
    /**
     * get history method
     * @return string 
     */
    public function getHistoryMethod():string{
        $t = "createMemoryHistory";
        if ($this->type == self::vueHashHistory){
            $t = "createWebHashHistory";
        }else if ($this->type == self::vueWebHistory){
            $t = "createWebHistory";
        }
        return $t;
    }
    /**
     * init rendering
     * @return null|string 
     */
    public function render($option=null):?string{
        $sb = new StringBuilder();
        $t = $this->getHistoryMethod();
        $v_extra = '';
        $_id = $this->id ?? "route";
        $sb->appendLine("/** vue-router **/");
        $sb->appendLine("const { createRouter, {$t}{$v_extra} } = VueRouter;");
        $ref_cp = 0;
        $ref_ca = 0;
        $rs = [];
        $_voptions = (object)["objectNotation"=>1];
        $vue_import = [];
        $v_routes = $this->m_routes;
        if ($v_routes)
        foreach($v_routes as $path=>$data){            
        
           // $data = JSExpression::Stringify([$data], (object)["objectNotation"=>1]);
           $tdata = ["path"=>$path, "component"=>null];
           // igk_wln_e(__FILE__.":".__LINE__,  $data);
           if (is_string($data)){
                $data = JSExpression::Create($data);
                $tdata["component"] =  $data;
           } else if ($data instanceof VueLazyLoadExpression){
                if (!$ref_cp){
                    // $sb->appendLine("const { loadVueComponent } = igk.js.vue3; ");
                    $vue_import[] = "loadVueComponent";
                    $ref_cp = 1;
                }
                $tdata["component"] =JSExpression::Create("()=> loadVueComponent('".$data->module."')");
           } else if ($data instanceof VueLazyImportExpression){
                if (!$ref_ca){
                    $ref_ca = 1;
                    $vue_import[] = "importVueComponent";
                }
                if ($name = igk_getv($data->options, "name")){
                    $tdata["name"]= $name;
                }
                $tdata["component"]= JSExpression::create("()=>importVueComponent(".$data->inlineData().")");
           }
           else{
                // init computent
                $c = null;
                if ($data instanceof HtmlNode){
                    $c = $data;
                    $data = [];
                } else {
                    if (!(($c = igk_getv($data, "template")) instanceof HtmlNode)){
                        $c = null;
                    }
                }
                if ($c){
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
        if ($vue_import){
            $sb->appendLine("const {".implode(",", $vue_import)."} = igk.js.vue3;");
        }
        $rs = implode(",", $rs); 
        $args = null;
        if ($t == self::vueWebHistory){
            $args = "'{$this->baseUri}'";
        }
        
        $sb->append("const {$_id} = createRouter({history:{$t}({$args}), routes: [".
        $rs .
            // "{'path':'/', component: {template:'<div>home page</div>'}},".
            // "{'path':'/about', component: {template:'<div>about page</div>'}}".
        "]}");
        // global definition method   
        // $sb->append(").beforeEach(function(to, from){ console.debug('date loading'); return true; }");     
        
        $sb->appendLine(");");
        foreach(["beforeEach", "afterEach", "beforeResolve"] as $k){
            if ($this->$k){
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
    private function _bindExtraProperty(& $tdata, $data){
        if (!is_array($data)){
            return;
        }
        if (isset($data["useProps"])){
            $tdata["props"] = (bool)igk_bool_val($data["useProps"]);
       }
    }
    public function useLibrary($option=null):array{
        $_id = $this->id ?? "route";
        return [$_id, null];
    }
    /**
     * create a router definition interface
     * @return IRouterParams 
     */
    public function createRouteDefinition():IRouterParams{
        $d = new RouterParams();
        return $d;
    }
    /**
     * load route definition
     * @param array $routeDef 
     * @return array 
     */
    public function load(array $routeDef){
        foreach($routeDef as $route=>$def){
            $this->addRoute($route, $def);
        };
        return $this;
    }


    static function evalJSString(string $s){
        $s = preg_replace("#</script\s*>#i", "<\\/script>", $s);
        return $s;
    }
}
