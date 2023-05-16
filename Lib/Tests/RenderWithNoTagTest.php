<?php


// @author: C.A.D. BONDJE DOUE
// @filename: RenderWithNoTagTest.php
// @date: 20230505 14:35:25
// @desc: 
namespace igk\js\Vue3\Tests;

use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueNoTagNode;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\Tests\Controllers\ModuleBaseTestCase;

class RenderWithNoTagTest extends ModuleBaseTestCase
{
    private function _runBuilder($b)
    {

        $t = new VueNoTagNode;
        $builder = new HtmlNodeBuilder($t);
        $builder($b);
        $src = VueSFCCompiler::ConvertToVueRenderMethod($t);
        return $src;
    }
    public function test_render_with_child(){
        $g = $this->_runBuilder([
            'div' => [
                'div.header'=>'header',
                'div.footer'=>'footer',
            ]
        ]);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[h('div',{class:'header',innerHTML:'header'}),h('div',{class:'footer',innerHTML:'footer'})])}", $g);
    }
    public function test_render_with_child_2(){
        $g = $this->_runBuilder([
            'div' => [
                'div.header'=>'header',
                'div.footer'=>'footer',
            ],
            'div.global'=>'litteral'
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[h('div',{class:'header',innerHTML:'header'}),h('div',{class:'footer',innerHTML:'footer'})]),h('div',{class:'global',innerHTML:'litteral'})}",
             $g);
    }
    public function test_render_with_conditional()
    {
        $g = $this->_runBuilder([
            'div' => 'Home',
            'div[v-if:response]' => 'Hello',
            'div#result' => 'OK',
        ]);
        $this->assertEquals("render(){const{h}=Vue;return [h('div','Home'),this.response?h('div','Hello'):null,h('div',{id:'result',innerHTML:'OK'})]}", $g);
    }
    public function test_render_with_conditional_if_else()
    {
        $g = $this->_runBuilder([
            'div' => 'Home',
            'div[v-if:response]' => 'Hello',
            'div#result[@v-else]' => 'OK',
        ]);
        $this->assertEquals("render(){const{h}=Vue;return [h('div','Home'),this.response?h('div','Hello'):h('div',{id:'result',innerHTML:'OK'})]}", $g);
    }
    public function test_render_with_conditional_if_else_2()
    {
        $g = $this->_runBuilder([
            'div' => 'Home',
            'div[v-if:response]' => 'Hello',
            'div#result[@v-else]' => 'OK',
            'div#end' => 'end'
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return [h('div','Home'),this.response?h('div','Hello'):h('div',{id:'result',innerHTML:'OK'}),h('div',{id:'end',innerHTML:'end'})]}",
            $g
        );
    }

    public function test_render_single_with_no_tag()
    {
        $g = $this->_runBuilder([
            'div' => 'Home'
        ]);
        $this->assertEquals("render(){const{h}=Vue;return h('div','Home')}", $g);
    }
    public function test_render_with_no_tag()
    {
        $g = $this->_runBuilder([
            'div' => 'Home',
            ['@_t:div' => 'Info']
        ]);
        $this->assertEquals("render(){const{h}=Vue;return [h('div','Home'),h('div','Info')]}", $g);
    }
    public function test_render_with_no_tag_2()
    {
        $g = $this->_runBuilder([
            "div" => [
                "tracking details"
            ],
            "form" => [
                // "::fields"=>[
                //     [
                //      "d"=>['type'=>'password']
                //     ]
                // ]
            ]
        ]);
        $this->assertEquals(
            "render(){const{h,Text}=Vue;return h('div',[h(Text,'tracking details')]),h('form',{method:'POST',action:'.',class:'igk-form'},[h('div',{class:'content'})])}",
            $g
        );
    }
    public function test_render_complex_form()
    {
        $g = $this->_runBuilder([
            "form" => [
                "::fields" => [
                    [
                        "d" => ['type' => 'password']
                    ]
                ]
            ]
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('form',{method:'POST',action:'.',class:'igk-form'},[h('div',{class:'content'},[h('div',{class:'igk-form-group password'},[h('label',{for:'d',class:'igk-form-label',innerHTML:'D'}),h('input',{class:'igk-form-control password',id:'d',name:'d',placeholder:'d',type:'password'})])])])}",
            $g
        );
    }
    public function test_render_complex_panel()
    {
        $g = $this->_runBuilder([
            "div.i[v-if:rep]" => "AB",
            // "panelbox#response"=>[
            //     "vIf(response)"=>[
            //         'div'=>"result"
            //     ]
            // ], 
            "Teleport" => [
                "_" => ["to" => "body"],
                "div" => "OK"
            ],
            "div" => "A"
        ]);
        $this->assertEquals(
            "render(){const{h,Teleport}=Vue;return [this.rep?h('div',{class:'i',innerHTML:'AB'}):null,h(Teleport,{to:'body'},[h('div','OK')]),h('div','A')]}",
            $g
        );
    }
    public function test_render_top_visit()
    {
        $g = $this->_runBuilder([
            "div.i[v-if:resp]" => "AB",
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return [this.resp?h('div',{class:'i',innerHTML:'AB'}):null]}",
            $g
        );
    }
    public function test_render_binding()
    {
        $g = $this->_runBuilder([
            "input" => [
                "_" => ["v-model" => "value"]
            ]
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('input',{type:'text',class:'cltext',value:this.value,onInput:(e)=>this.value= e.target.Value})}",
            $g
        );
    }

    public function test_render_error_list()
    {
        $g = $this->_runBuilder([           
            "d > div" => [
                // "b" => '{{ this.name }}',
                // "c" => "tracking details",
                "panelbox#response" => [
                    "_" => [
                        "v-if" => "response"
                    ]
                ]
            ] 
        ]);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('d',[h('div',[this.response?h('div',{class:'igk-panel-box',id:'response'}):null])])}",
            $g
        );
    }
}
