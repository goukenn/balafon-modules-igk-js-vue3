<?php

namespace igk\js\Vue3\Html;

use igk\js\Vue3\Components\VueComponentNode;
use igk\js\Vue3\Components\VueCustomComponentNode;
use igk\js\Vue3\Components\VueRouterLink;
use igk\js\Vue3\Components\VueRouterView;
use igk\js\Vue3\Components\VueSlot;
use igk\js\Vue3\Components\VueTemplate;
use igk\js\Vue3\Components\VueTransition;
use igk\js\Vue3\Components\VueTransitionGroup;
use igk\js\Vue3\System\WinUI\Menus\RouterMenuBuilder;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\System\Html\Dom\HtmlNode;
use IGKException;

/**
 * vue macros extension helper - bind vue attribute mecanism / node 
 * @package igk\js\Vue3\Html
 */
abstract class MacrosExtensions{
    private function __construct(){        
    }
    //--------------------------------------------------------------
    // + | DIRECTIVES
    //--------------------------------------------------------------
    /**
     * 
     * @param HtmlNode $node 
     * @param string|array $conditions vue condition expression
     * @return HtmlNode 
     */
    public static function vFor(HtmlNode $node, $conditions){
        if (is_array($conditions)){
            $conditions = "item in ".json_encode($conditions);
        }
        return $node->setAttribute("v-for", $conditions);
    }
    /**
     * activate directive v-pre
     * @param HtmlNode $node 
     * @return HtmlNode 
     * @throws IGKException 
     */
    public static function vPre(HtmlNode $node){        
        return $node->activate("v-pre");
    }
    /**
     * activate with directive v-ignore
     * @param HtmlNode $node 
     * @return HtmlNode 
     * @throws IGKException 
     */
    public static function vIgnore(HtmlNode $node){        
        return $node->activate("v-ignore");
    }
    /**
     * 
     * @param HtmlNode $node 
     * @param string $conditions 
     * @return HtmlNode 
     */
    public static function vIf(HtmlNode $node, string $conditions){
        return $node->setAttribute("v-if", $conditions);
    }
    /**
     * 
     * @param HtmlNode $node 
     * @param string $conditions 
     * @return HtmlNode 
     */
    public static function vElse(HtmlNode $node, string $conditions){
        return $node->setAttribute("v-else", $conditions);
    }
    /**
     * 
     * @param HtmlNode $node 
     * @param string $conditions 
     * @return HtmlNode 
     */
    public static function vElseIf(HtmlNode $node, string $conditions){
        return $node->setAttribute("v-else-if", $conditions);
    }
    /**
     * 
     * @param HtmlNode $node 
     * @param string $conditions 
     * @return HtmlNode 
     */
    public static function vShow(HtmlNode $node, string $conditions){
        return $node->setAttribute("v-show", $conditions);
    }
    /**
     * force render html expression
     * @param HtmlNode $node 
     * @param string $expression 
     * @return HtmlNode 
     */
    public static function vHtml(HtmlNode $node, string $expression){
        return $node->setAttribute('v-html', $expression); 
    }
    public static function vOnce(HtmlNode $node){
        return $node->activate('v-once'); 
    }
    public static function vBind(HtmlNode $node, $attribute, $value){
        return $node->setAttribute("v-bind:".$attribute, $value);
    }
    public static function vAdd(HtmlNode $node, $type){
        if (class_exists($cl = \igk\js\Vue3\Components::class."\Vue".$type)){
            $i = new $cl();
            $node->add($i);
            return $i;
        }
    }
   
    /**
     * add event handler bind attribute 
     * @param HtmlNode $node 
     * @param string $eventType (name|[property])(.modifier)?
     * @param mixed $value expression to evaluate
     * @return HtmlNode 
     */
    public static function vOn(HtmlNode $node, string $eventType, $value){
        return $node->setAttribute("v-on:".$eventType, $value);
    }
    /**
     * bind v-model attribute
     * @param HtmlNode $node 
     * @param mixed $value value or attribute :attribute_name
     * @return mixed 
     * if (attribute_name) must provide an extra property for value
     */
    public static function vModel(HtmlNode $node, $value){
        if (func_num_args()==3){
            if ($value[0]==":"){
                return $node->setAttribute("v-model".$value, func_get_arg(2));
            }
            throw new IGKException(__("definition not valid"));
        }
        return $node->setAttribute("v-model", $value);
    }
    public static function vDisabled(HtmlNode $node, $condition){
        return self::vBind($node, "disabled", $condition);
    }
    /**
     * attach v-clock directive
     * @param HtmlNode $node 
     * @return HtmlNode 
     * @throws IGKException 
     */
    public static function vCloak(HtmlNode $node, bool $activate=true){
        $activate ? $node->activate("v-cloak") : $node->deactivate("v-cloak");
        return $node;
    }
    public static function vClass(HtmlNode $node, $condition){
        return self::vBind($node, "class", $condition);
    }
    public static function vStyle(HtmlNode $node, $condition){
        return self::vBind($node, "style", $condition);
    }
    public static function vKey(HtmlNode $node, $id){
        return self::vBind($node, "key", $id);
    }
    /**
     * set reference attribute on node to access dom on mounted
     * @param HtmlNode $node 
     * @param string $id 
     * @return mixed 
     */
    public static function vRef(HtmlNode $node, string $id){
        $node->setAttribute("ref", $id);
        return $node;
    }
    /**
     * set is value on node
     * @param HtmlNode $node 
     * @param string $value expression as vue:component-name
     * @return HtmlNode 
     */
    public static function vIs(HtmlNode $node, string $value){
        $node->setAttribute("is", $value);
        return $node;
    }
    /**
     * attach :ref binding
     * @param HtmlNode $node 
     * @param string $id 
     * @return HtmlNode 
     */
    public static function vRefBinding(HtmlNode $node, string $id){
        return self::vBind($node, "ref", $id);
    }
    public static function vTransition(HtmlNode $node){
        $n = new VueTransition();
        $node->add($n);
        return $n;
    }
    /**
     * create a transition group node
     * @param HtmlNode $node 
     * @return VueTransitionGroup 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vTransitionGroup(HtmlNode $node){
        $n = new VueTransitionGroup();
        $node->add($n);
        
        return $n;
    }
    /**
     * 
     * @param HtmlNode $node 
     * @param mixed $name 
     * @param mixed $value 
     * @return HtmlNode 
     */
    public static function vSetDirective(HtmlNode $node, $name, $value){
        $node->setAttribute("v-".$name, $value);
        return $node;
    }
    ///<summary>create a vcomponent node</summary>
    /**
     * add vue component
     * @param HtmlNode $node 
     * @param string $name create a vue component name
     * @param string $expectedTag expected tag
     * @return VueComponentNode 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vComponent(HtmlNode $node){
        $n = new VueComponentNode();
        $node->add($n);
        return $n;
    }
    /**
     * create a custom component node
     * @param HtmlNode $node 
     * @param string $name 
     * @return VueCustomComponentNode 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vComponentNode(HtmlNode $node, string $name){
        $n = new VueCustomComponentNode($name);
        $node->add($n);
        return $n;
    }
    /**
     * add a router link 
     * @param HtmlNode $node 
     * @param null|string|\IGK\System\Html\ViewRef mixed $to string target 
     * @return VueRouterLink 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vRouterLink(HtmlNode $node, $to=null){
        $n = new VueRouterLink();
        $to && $n->setAttribute("to", $to); 
        $node->add($n);
        return $n;
    }
    /**
     * v-bind router link
     * @param HtmlNode $node 
     * @param mixed $to 
     * @return VueRouterLink 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vBindRouterLink(HtmlNode $node, $to){
        $n = new VueRouterLink();
        $n->setAttribute(":to", $to); 
        $node->add($n);
        return $n;
    }
    ///<summary> bind router link helper</summary>
    /**
     *  bind router link helper
     * @param HtmlNode $node 
     * @param mixed $to_expression 
     * @return RouterLink 
     * @throws IGKException 
     */
    public static function vRouterBindLink(HtmlNode $node, $to_expression){
        return self::vBindRouterLink($node, $to_expression);
    }
    /**
     * add a router view to node
     * @param HtmlNode $node 
     * @return igk\js\Vue3\Html\VueRouterView 
     * @throws IGKException 
     */
    public static function vRouterView(HtmlNode $node){           
        $n = new VueRouterView(); 
        $node->add($n);
        return $n;
    }
    /**
     * append slot node
     */
    public static function vSlot(HtmlNode $node){
        $n = new VueSlot();
        $node->add($n);
        return $n;
    }
    /**
     * create a vue template
     * @param HtmlNode $node 
     * @return VueTemplate 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    public static function vTemplate(HtmlNode $node){
        $n = new VueTemplate();
        $node->add($n);
        return $n;
    }
    public static function vLink(HtmlNode $node, $link){
        $n = new HtmlNode("a");
        $n->vBind("href", $link);
        $node->add($n);
        return $n;
    }
    /**
     * help build vue router link
     * @param HtmlNode $node 
     * @param array $menus 
     * @return HtmlNode 
     * @throws IGKException 
     */
    public static function vMenus(HtmlNode $node, array $menus = []){
        $ul = $node->ul();
        $ul["class"] = "igk-menu igk-vue-menu";
        igk_html_build_menu($ul, $menus, new RouterMenuBuilder);
        return $ul;
    }
    
    public static function __callStatic($name, $arg){
        igk_dev_wln_e(__FILE__.":".__LINE__, 'call static not allowed');
    }
}