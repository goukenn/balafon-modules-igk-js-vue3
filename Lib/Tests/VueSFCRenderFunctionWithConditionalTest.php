<?php



namespace igk\js\Vue3\Tests;

use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueComponent;
use IGK\Tests\Controllers\ModuleBaseTestCase;

class VueSFCRenderFunctionWithConditionalTest extends ModuleBaseTestCase
{
    public function test_conditional_simple()
    {
        igk_require_module(\igk\js\Vue3::class);
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div v-if="item"> item </div>
HTML
);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div',' item '):null])}", $s);
    }
    public function test_conditional_with_child_simple()
    {
        igk_require_module(\igk\js\Vue3::class);
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div v-if="item"> item <span>child</span></div>
HTML
);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h,Text}=Vue;return h('div',[this.item?h('div',[h(Text,' item '),h('span','child')]):null])}", $s);
    }

    public function test_conditional_with_child2_simple()
    {
        igk_require_module(\igk\js\Vue3::class);
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div v-if="item1"> item1 <span>child1</span></div>
<div v-if="item2"> item2 <span>child2</span></div>
HTML
);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h,Text}=Vue;return h('div',[this.item1?h('div',[h(Text,' item1 '),h('span','child1')]):null,this.item2?h('div',[h(Text,' item2 '),h('span','child2')]):null])}", $s);
    }


    public function test_conditional_with_if_else_after_simple()
    {
        igk_require_module(\igk\js\Vue3::class);
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div v-if="item1"> item1 <span>child1</span></div>
<div v-else> else<span>child2</span></div>
<div> OK</div>
HTML
);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h,Text}=Vue;return h('div',[this.item1?h('div',[h(Text,' item1 '),h('span','child1')]):h('div',[h(Text,' else'),h('span','child2')]),h(Text,' '),h('div',' OK')])}", $s);
    }

}