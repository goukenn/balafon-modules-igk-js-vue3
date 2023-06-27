<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodLoopTest.php
// @date: 20230331 12:25:01
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\Components\VueComponent;
use IGK\System\Html\HtmlNodeBuilder;
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
            "render(){const{h}=Vue;return (function(l,key){for(key in l){((i)=>this.push(h('div','hello')))(l[key])}return this}).apply([],[[1,3,5]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_destruct()
    {
        $d = new VueComponent('div');
        $d->vFor("{i} in [{i:10,value:'one'},{i:20,value:'two'}]")->setContent("hello {{ i }} {{ key }}");
        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){(({i})=>this.push(h('div',`hello \${i} \${key}`)))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_destruct_bind_loop()
    {
        $d = new VueComponent('div');
        $d->vFor("{i} in [{i:10,value:'one'},{i:20,value:'two'}]")
            ->vBind("key", "i")
            ->setContent("hello {{ i }} {{ key }}");
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);

        $this->assertEquals(
            "render(){const{h}=Vue;return (function(l,key){for(key in l){(({i})=>this.push(h('div',{key:i,innerHTML:`hello \${i} \${key}`})))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            $s
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
            "render(){const{h,resolveComponent}=Vue;const \$__c=(q,n)=>(n in q)?((f)=>typeof(f)=='function'?f():(()=>f)())(q[n]):resolveComponent(n);const _vue_sampleinfo=\$__c(this,'SampleInfo');return (function(l,key){for(key in l){(({i})=>this.push(h('div',{key:i},[h('div',[h(_vue_sampleinfo,{data:litteral},()=>`hello \${i} \${key} `)])])))(l[key])} return this}).apply([],[[{i:10,value:'one'},{i:20,value:'two'}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d, $o)
        );
    }

    public function test_render_with_builder()
    {
        $o = new VueSFCRenderNodeVisitorOptions;
        $d = igk_create_notagnode();
        $builder = new HtmlNodeBuilder($d);
        $app_main = $builder->setup("vue_component(div.mycomponent)", [
            // "div.igk-winui-vue-clone.menu[igk-data:'#menu']" => [
            //     "menu"
            // ],
            "vTeleport('#offcanvas')" => [
                "div" => "Menu teleport",
                "div.igk-winui-vue-clone.menu[igk-data:'#menu']" => []
            ]
        ]);
        $this->assertEquals(
            "render(){return h('div',{class:'mycomponent'},[h(Teleport,{to:'#offcanvas'},[h('div','Menu teleport'),h('div',{class:'igk-winui-vue-clone menu','igk-data':'#menu'})])])}",
            VueSFCCompiler::ConvertToVueRenderMethod($app_main, $o)
        );
    }
    public function test_render_with_builder_router_link()
    {
        $o = new VueSFCRenderNodeVisitorOptions;
        $o->test = true;
        $d = igk_create_notagnode();
        $builder = new HtmlNodeBuilder($d);
        $app_main = $builder->setup("vue_component(div.mycomponent)", [
            "nav" =>  [
                ["@_t:li" => ["vue_router_link" => ['@' => ['/'], "gohome"]]],
                ["@_t:li" => ["vue_router_link" => ['@' => ['/about'], "gotoabout"]]],
            ],
            "main"=>[
                "vRouterView"=>[]
            ]
        ]);
        $this->assertEquals(
            "render(){const{h,resolveComponent,Text}=Vue;const \$__c=(q,n)=>(n in q)?((f)=>typeof(f)=='function'?f():(()=>f)())(q[n]):resolveComponent(n);const _vue_routerlink=\$__c(this,'RouterLink');const _vue_routerview=\$__c(this,'RouterView');return h('div',{class:'mycomponent'},[h('nav',[h('li',[h(_vue_routerlink,{class:'v-router-link',to:'/'},()=>[h(Text,'gohome')])]),h('li',[h(_vue_routerlink,{class:'v-router-link',to:'/about'},()=>[h(Text,'gotoabout')])])]),h('main',[h(_vue_routerview)])])}",
            VueSFCCompiler::ConvertToVueRenderMethod($app_main, $o)
        );
    }
    public function test_render_with_builder_preserve_router_link()
    {
        $o = new VueSFCRenderNodeVisitorOptions;
        $o->test = true;
        $d = igk_create_notagnode();
        $builder = new HtmlNodeBuilder($d);
        $app_main = $builder->setup("vue_component(div.mycomponent)", [
            "nav[@v-pre]" => [
                ["@_t:li" => ["vue_router_link" => ['@' => ['/'], "gohome {{ this.x }} "]]],
                ["@_t:li" => ["vue_router_link" => ['@' => ['/about'], "gotoabout"]]]
            ],
            "main"=>[
                "vRouterView"=>[]
            ]
        ]);
        $this->assertEquals(
            "render(){const{h,resolveComponent}=Vue;const \$__c=(q,n)=>(n in q)?((f)=>typeof(f)=='function'?f():(()=>f)())(q[n]):resolveComponent(n);const _vue_routerview=\$__c(this,'RouterView');return h('div',{class:'mycomponent'},[h('nav',{innerHTML:'<li><router-link class=\"v-router-link\" to=\"/\">gohome {{ this.x }} </router-link></li><li><router-link class=\"v-router-link\" to=\"/about\">gotoabout</router-link></li>'}),h('main',[h(_vue_routerview)])])}",
            VueSFCCompiler::ConvertToVueRenderMethod($app_main, $o)
        );
    }
}
