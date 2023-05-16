<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodWithConditionTest.php
// @date: 20230331 12:25:01
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\Components\VueComponent;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Compiler
 */
class VueGenerateRenderJSMethodWithConditionTest extends ModuleBaseTestCase
{
    public function test_render()
    {
        $d = new VueComponent('div');
        $d->vIf("x > 50.5 ")->setContent("hello");
        $this->assertEquals(
            "render(){const{h}=Vue;return [this.x>50.5?h('div','hello'):null]}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_else()
    {
        $d = new VueComponent('div');
        // because of the sub tag - need to end conditional only on the same depht level
        $d->load("<div v-if='x > 50.5'>hello </div><div v-else>else <b>what</b></div>");

        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.x>50.5?h('div','hello '):h('div',{innerHTML:'else '},[h('b','what')])])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_else_2()
    {
        $d = new VueComponent('div');
        $d->load("<div v-if='x > 50.5'>hello </div><div v-else>else what</div><span>Jumping</span>");
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.x>50.5?h('div','hello '):h('div','else what'),h('span','Jumping')])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
}
