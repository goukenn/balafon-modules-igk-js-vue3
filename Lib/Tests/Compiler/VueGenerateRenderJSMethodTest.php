<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueGenerateRenderJSMethodTest.php
// @date: 20230331 01:03:37
namespace igk\js\Vue3\Compiler;

use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3\Compiler
*/
class VueGenerateRenderJSMethodTest extends ModuleBaseTestCase{
    function test_render(){
        $n = igk_create_node();
        $n->load("hello");
        $this->assertEquals(
            'render(){ const {h}=Vue; return h(\'div\',"hello");}',
            VueSFCCompiler::ConvertToVueRenderMethod($n)
        );
    }
}