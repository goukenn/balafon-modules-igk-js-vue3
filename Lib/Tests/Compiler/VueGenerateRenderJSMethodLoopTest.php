<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodLoopTest.php
// @date: 20230331 12:25:01
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\Components\VueComponent;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Compiler
 */
class VueGenerateRenderJSMethodLoopTest extends ModuleBaseTestCase
{
    public function test_render()
    {
        $d = new VueComponent('div');
        $d->vFor("i in [1,3,5]")->setContent("hello");
        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){((i)=>this.push(h('div',{innerHTML:'hello'})))(l[key])}return this}).apply([],[[1,3,5]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_destruct()
    {
        $d = new VueComponent('div');
        $d->vFor("{i} in [{i:10,value:'one'},{i:20,value:'two'}]")->setContent("hello {{ i }} {{ key }}");
        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){(({i})=>this.push(h('div',{innerHTML:`hello \${i} \${key}`})))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_destruct_bind()
    {
        $d = new VueComponent('div');
        $d->vFor("{i} in [{i:10,value:'one'},{i:20,value:'two'}]")
        ->vBind("key", "i")
        ->setContent("hello {{ i }} {{ key }}");
        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){(({i})=>this.push(h('div',{key:i,innerHTML:`hello \${i} \${key}`})))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }

    public function test_render_destruct_bind_2()
    {
        $o = new VueSFCRenderNodeVisitorOptions;
        $o->components['SampleInfo'] = 1;
        $o->test = true;
        $d = new VueComponent('div');
        $d->vFor("{i} in [{i:10,value:'one'},{i:20,value:'two'}]")
        ->vBind("key", "i")
        ->div()->add("SampleInfo")
        ->vBind("data", "litteral")
        ->setContent("hello {{ i }} {{ key }} ");
        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){(({i})=>this.push(h('div',{key:i,innerHTML:`hello \${i} \${key}`})))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d, $o)
        );
    }
}
