<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodTest.php
// @date: 20230331 01:03:37
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\Components\VueComponentNode;
use IGK\System\Html\Dom\HtmlTextNode;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
class VueGenerateRenderJSMethodTest extends ModuleBaseTestCase{
    function test_render(){
        $n = igk_create_node();
        $n->Content = "hello";
        $this->assertEquals(
            'render(){const{h}=Vue;return h(\'div\',\'hello\')}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function test_render_text(){
        $n = new HtmlTextNode("hello"); 
        $this->assertEquals(
            "render(){const{h,Text}=Vue;return h(Text,'hello')}",
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function test_render_with_attr(){
        $n = igk_create_node();
        $n->Content = "hello";
        $n['x']='1';
        $n['y']='null';
        $n['z']=null;
        $this->assertEquals(
            'render(){const{h}=Vue;return h(\'div\',{x:1,y:\'null\',innerHTML:\'hello\'})}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function test_render_with_buildin_component(){
        $n = igk_create_node();
        $n->addTransition();
        $this->assertEquals(
            'render(){const{h,Transition}=Vue;return h(\'div\',[h(Transition)])}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function test_render_with_component(){
        $n = igk_create_node();
        $n->add('router-view');
        $this->assertEquals(
            'render(){const{h,resolveComponent}=Vue;const $__c=(q,n)=>(n in q)?'.
            '((f)=>typeof(f)==\'function\'?f():(()=>f)())(q[n]):resolveComponent(n);const _vue_routerview=$__c(this,\'RouterView\');return h(\'div\',[h(_vue_routerview)])}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function test_render_with_dyn_component(){
        $n = igk_create_node();
        $g = new VueComponentNode;
        $g->vBind('is', 'Foo');
        $n->add($g); 
        $this->assertEquals(
            'render(){const{h,resolveDynamicComponent}=Vue;return h(\'div\',[h(resolveDynamicComponent(Foo))])}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }

    function test_render_with_v_slot(){
        $n = igk_create_node();
      
        $n->load("<router-view v-slot=\"{Component}\"><transition><component :is='Component'></component></transition></router-view>");
        $this->assertEquals(
            "render(){const{h,resolveComponent,resolveDynamicComponent,Transition}=Vue;const \$__c=(q,n)=>(n in q)?((f)=>typeof(f)=='function'?f():(()=>f)())(q[n]):resolveComponent(n);const _vue_routerview=\$__c(this,'RouterView');return h('div',[h(_vue_routerview,{},({Component})=>[h(Transition,()=>[h(resolveDynamicComponent(Component))])])])}",            
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
    function _test_render(){
        $n = igk_create_node();
        $n->load("hello");
        $this->assertEquals(
            'render(){ const {h}=Vue; return h(\'div\',"hello");}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
}