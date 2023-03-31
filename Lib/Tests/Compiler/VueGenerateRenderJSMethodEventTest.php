<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodEventTest.php
// @date: 20230331 12:25:01
namespace igk\js\Vue3\Compiler;

use igk\js\Vue3\Components\VueComponent;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
 * 
 * @package igk\js\Vue3\Compiler
 */
class VueGenerateRenderJSMethodEventTest extends ModuleBaseTestCase
{
    public function test_render(){
        $d = new VueComponent('div');
        $d->vOn("click", "()=>console.log('ok')")->setContent('click me');
        $this->assertEquals(
            "render(){const{h}=Vue;return h('div',{onClick:()=>console.log('ok'),innerHTML:'click me'})}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
    public function test_render_with_modifier(){
        $d = new VueComponent('div');
        $d->vOn("click.prevent", "()=>console.log('ok')")->setContent('click me');
        $this->assertEquals(
            "render(){const{h,withModifiers}=Vue;return h('div',{onClick:withModifiers(()=>{()=>console.log('ok')},['prevent']),innerHTML:'click me'})}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }


    public function test_render_directive(){
        $d = new VueComponent('div');
        $d->vDirective("pin:top.animate", 200)->setContent('click me');
        $this->assertEquals(
            "render(){const{h,withDirectives}=Vue;return withDirectives(h('div',{innerHTML:'click me'}),[[pin,200,'top',{animate:true}]])}",
            VueSFCCompiler::ConvertToVueRenderMethod($d)
        );
    }
}