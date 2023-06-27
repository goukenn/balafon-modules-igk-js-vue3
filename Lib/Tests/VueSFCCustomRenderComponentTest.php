<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCCustomRenderComponentTest.php
// @date: 20230524 12:24:48
namespace igk\js\Vue3;

use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Compiler\VueSFCRenderNodeVisitorOptions;
use igk\js\Vue3\Components\VueComponent;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3
*/
class VueSFCCustomRenderComponentTest extends ModuleBaseTestCase{
    private function render_item($d){
        $options = new VueSFCRenderNodeVisitorOptions;
        $options->components = array_fill_keys(['CustomItem'], 1); 
        return VueSFCCompiler::ConvertToVueRenderMethod($d, $options);
    }
    public function test_render_item()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
 <CustomItem></CustomItem> 
HTML
     ,[]);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h(_vue_customitem)])}",
            $s
        );
    }

    public function test_render_item_with_class()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
 <CustomItem class="app"></CustomItem> 
HTML
     ,[]);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h(_vue_customitem,{class:'app'})])}",
            $s
        );
    }
    public function test_render_item_dynamic_slot()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
 <CustomItem class="app">
    <template v-slot:[target]="info">
        <div>render slot info</div>
    </template>
 </CustomItem> 
HTML
     ,[]);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h(_vue_customitem,{class:'app'},{...((n)=>{const p={};p[n]=()=>h('div','render slot info');return p})(`\${this.target}`)})])}",
            $s
        );
    }
    public function test_render_item_conditional_slot()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
 <CustomItem class="app">
    <template #info="slots" v-if="info">
        <div>render slot info</div>
    </template>
 </CustomItem> 
HTML
     ,[]);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h(_vue_customitem,{class:'app'},{info:(slots)=>this.info?(h('div','render slot info')):null})])}",
            $s
        );
    }
    public function test_render_item_consume_slot()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
 <CustomItem class="app">
    <template #info="slots" v-if="info">
        <div>render slot info '{{ slots.data }}</div>
    </template>
 </CustomItem> 
HTML
     ,[]);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h(_vue_customitem,{class:'app'},{info:(slots)=>this.info?(h('div',`render slot info \${slots.data}`)):null})])}",
            $s
        );
    }
    public function test_routerlink_test()
    {
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div>
    <router-link to="/proposal" class="igk-btn btn custom-btn nav-btn"> '{{ $t('Propose a car') }} </router-link>
</div>
HTML, []);
        $s = $this->render_item($d);
        $this->assertEquals(
            "render(){return h('div',[h('div',[h(_vue_routerlink,{to:'/proposal',class:'igk-btn btn custom-btn nav-btn'},()=>` \${this.\$t('Propose a car')} `)])])}",
            $s
        );
    }
}