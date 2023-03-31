<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodTest.php
// @date: 20230331 01:03:37
namespace igk\js\Vue3\Compiler;

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
        $n = new HtmlTextNode("hello");        ;
        $this->assertEquals(
            'render(){const{h,Text}=Vue;return h(Text,hello)}',
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
            'render(){const{defineComponent,h}=Vue;return h(\'div\',[h(_vue_router_view)])}',
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