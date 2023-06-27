<?php


// @author: C.A.D. BONDJE DOUE
// @filename: RenderFunctionTest.php
// @date: 20230519 20:51:20
// @desc: 

namespace igk\js\Vue3\Tests;

use igk\js\Vue3\Compiler\VueSFCCompiler;
use igk\js\Vue3\Components\VueComponent;
use igk\js\Vue3\Components\VueNoTagNode;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\Tests\Controllers\ModuleBaseTestCase;

class RenderFunctionTest extends ModuleBaseTestCase
{
    public function test_running_comment_render()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div> item </div>
<!-- first comment -->
<span>OK</span>
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{Comment,h}=Vue;return h('div',[h('div',' item '),h(Comment,' first comment '),h('span','OK')])}", $s);
    }

    public function test_render_after_sub(){
        $d = new VueComponent("div");
        $d->load(<<<'HTML'
<div  v-if="item">
    <h4 v-if="!a"> S </h4>
    <div>
        <p to="/proposal"> Proposer une voiture </p>
    </div>
</div>
HTML);
$s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div',[!this.a?h('h4',' S '):null,h('div',[h('p',{to:'/proposal',innerHTML:' Proposer une voiture '})])]):null])}",
        $s);

    }

    public function test_running_if_condition()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='item'> item </div> 
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div',' item '):null])}", $s);
    }

    public function test_running_if_else_condition()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='item'>item</div> 
<div v-else>else</div></div> 
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div','item'):h('div','else')])}", $s);
    }

    public function test_running_if_else_condition_2()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='item'>item </div> 
<div v-else>else</div></div> 
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div','item '):h('div','else')])}", $s);
    }
    public function test_running_if_else_condition_3()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='item'>item </div> 
<div v-else>else </div>
<div>trois</div>
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals("render(){const{h}=Vue;return h('div',[this.item?h('div','item '):h('div','else '),h('div','trois')])}", $s);
    }
    public function test_running_if_else_sub_condition_block()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='item'><span v-if="kill">sub two</span></div> 
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.item?h('div',[this.kill?h('span','sub two'):null]):null])}",
            $s
        );
    }

    public function test_running_multichild_block()
    {
        $d = new VueComponent("div");
        // <main v-if='code'><div v-if='item'>abc<p>quotes</p></div></main>
        $d->load(
            <<<'HTML'
<div v-if="a || b">
    <h4 v-if="a">one <span>m</span></h4>
    <h4 v-if="b">two <span>x</span></h4> 
</div>
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h,Text}=Vue;return h('div',[this.a || this.b?h('div',[this.a?h('h4',[h(Text,'one '),h('span','m')]):null,this.b?h('h4',[h(Text,'two '),h('span','x')]):null]):null])}",
            $s
        );
    }
    // public function test_running_start_global_detph()
    // {
    //     $d = new VueComponent("div");
    //     $d->load('<span>m</span>');
    //     $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
    //     $this->assertEquals(
    //         "render(){const{h}=Vue;return h('div',[this.item?h('div',[this.kill?h('span','two'):null]):null])}",
    //         $s
    //     );
    // }


    public function test_running_if_else_condition_block_inner()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div v-if='car.fueldId'><font color="#921534">Laca</font></div> 
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.car.fueldId?h('div',[h('font',{color:'#921534',innerHTML:'Laca'})]):null])}",
            $s
        );
    }
    public function test_running_if_else_condition_block_inner_dl()
    {
        $d = new VueComponent("div");
        $d['class'] = 'topdiv';
        $d->load(<<<'HTML'
<h4 v-if="a">A</h4>
<h4 v-if="b">B</h4>
<h4 v-else>C</h4> 
HTML);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',{class:'topdiv'},[this.a?h('h4','A'):null,this.b?h('h4','B'):h('h4','C')])}",
            $s
        );
    }
    public function test_running_if_else_condition_block_inner_xdl()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
<div class='topdiv' >
    <h4 v-if="a">A</h4>
    <span>info</span>
    <h4 v-if="b">B</h4>
    <h4 v-else>C</h4>
</div>
HTML
        );
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h,Text}=Vue;return h('div',[h('div',{class:'topdiv'},[this.a?h('h4','A'):null,h('span','info'),h(Text,' '),this.b?h('h4','B'):h('h4','C')])])}",
            $s
        );
    }
    public function test_leave_interpolation()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
  <h2> '{{$t('Show room')}} </h2>
HTML
     ,[]);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[h('h2',` \${this.\$t('Show room')} `)])}",
            $s
        );
    }
    public function test_cn_condition()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
  <h2 v-if="car.pictures && (car.pictures.length>0)"> data </h2>
HTML
     ,[]);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.car.pictures && (car.pictures.length>0)?h('h2',' data '):null])}",
            $s
        );
    }
    public function test_cn_after_condition()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
  <div v-if="x">
</div>
<div v-if="a || b || c">   
<div class="event_news_text">
    <h4 v-if="a">A: </h4>
    <h4 v-if="b">B: </h4>
    <h4 v-if="c">C:</h4>
</div>
</div>
HTML
     ,[]);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.x?h('div'):null,this.a || this.b || this.c?h('div',[h('div',{class:'event_news_text'},[this.a?h('h4','A: '):null,this.b?h('h4','B: '):null,this.c?h('h4','C:'):null])]):null])}",
            $s
        );
    }


    public function test_skip_script_after_condition()
    {
        $d = new VueComponent("div");
        $d->load(
            <<<'HTML'
  <div v-if="x">
</div>
    <script></script>
    <div>after </div>
</div>
</div>
HTML
     ,[]);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[this.x?h('div'):null,h('div','after ')])}",
            $s
        );
    }

//     public function test_convert_template()
//     {
//         $d = new VueComponent("div");
//         $d->load(
//             <<<'HTML'
//   <div v-if="x">
// </div>
//     <script></script>
//     <template v-if="ok"> <div>after </div> </template>
//     <div>base. </div>
// </div>
// </div>
// HTML
//      ,[]);
//         $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
//         $this->assertEquals(
//             "render(){const{h}=Vue;return h('div',[this.x?h('div'):null,this.ok?h('div','after '):null,h('div','base. ')])}",
//             $s,
//             "transform template to inline rendering failed."
//         );
//     }

//     public function test_convert_template_with_else_block()
//     {
//         $d = new VueComponent("div");
//         $d->load(
//             <<<'HTML'
//   <div v-if="x">
// </div>
//     <script></script>
//     <template v-if="ok"> <div>after </div> </template>
//     <template v-else>else...</template>
// </div>
// </div>
// HTML
//      ,[]);
//         $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
//         $this->assertEquals(
//             "render(){const{h}=Vue;return h('div',[this.x?h('div'):null,this.ok?h('div','after '):null,h('div','base. ')])}",
//             $s,
//             "transform template to inline rendering failed."
//         );
//     }

    public function test_pre_attribute_fix()
    {
        $d = new VueComponent("div"); 
        $d->load(
            <<<'HTML'
  <div v-if="x" v-pre/> 
HTML
     ,[]);
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d);
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',[h('div',{'v-if':'x',innerHTML:''})])}",
            $s,
            "reserve transformation on div"
        );
    }
}
