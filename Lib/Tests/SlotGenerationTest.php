<?php
// @author: C.A.D. BONDJE DOUE
// @file: SlotGenerationTest.php
// @date: 20230420 10:24:49
namespace igk\js\Vue3\Tests;

use igk\js\Vue3\Compiler\VueSFCCompiler;
use IGK\System\Html\HtmlNodeBuilder;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\js\Vue3
*/
class SlotGenerationTest extends ModuleBaseTestCase{
    public function test_render_slot(){
        $d = igk_create_notagnode(); 
        $d->div()->slot(); 
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,useSlots}=Vue;const slots=useSlots();return h(\'div\',[slots.default?slots.default():null])}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }
    public function test_render_slot_name(){
        $d = igk_create_notagnode(); 
        $d->div()->slot()->setAttribute('name','marche'); 
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,useSlots}=Vue;const slots=useSlots();return h(\'div\',[slots.marche?slots.marche():null])}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }
    public function test_render_slot_name_with_attr(){
        $d = igk_create_notagnode(); 
        $d->div()->slot()->setAttribute('name','marche')->setAttribute(':msg', 'myssage'); 
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,useSlots}=Vue;const slots=useSlots();return h(\'div\',[slots.marche?slots.marche({msg:myssage}):null])}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }

    public function test_render_slot_next_to_item(){
        $d = igk_create_notagnode(); 
        $d->div()->Content = "first";
        $d->slot();

        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,useSlots}=Vue;const slots=useSlots();return [h(\'div\',\'first\'),slots.default?slots.default():null]}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }
    public function test_render_slot_next_sub_item_item(){
        $d = igk_create_notagnode(); 
        $d->div();
        $d->slot();  
        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = "render(){const{h,useSlots}=Vue;const slots=useSlots();return [h('div'),slots.default?slots.default():null]}";
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }
    public function test_render_slot_after_text(){
        $d = igk_create_notagnode(); 
        $builder  = new HtmlNodeBuilder($d);
        $builder([
            "div"=>[
                'try to handle slots ',        
                'slot'=>"presentation du slot" 
            ] 
        ], $d);        

        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,Text,useSlots}=Vue;const slots=useSlots();return h(\'div\',[h(Text,\'try to handle slots \'),slots.default?slots.default():h(Text,\'presentation du slot\')])}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }

    public function test_render_slot_content_data(){
        $d = igk_create_notagnode(); 
        $builder  = new HtmlNodeBuilder($d);
        $builder([
            "div"=>[
                'try to handle slots ',        
                'slot'=>"presentation du slot" 
            ] 
        ], $d);        

        $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
        $expected = 'render(){const{h,Text,useSlots}=Vue;const slots=useSlots();return h(\'div\',[h(Text,\'try to handle slots \'),slots.default?slots.default():h(Text,\'presentation du slot\')])}'; 
        $this->assertEquals($expected, 
        $s, 'not matching'); 
    }
    // public function test_render_slot_single_child(){
    //     $d = igk_create_notagnode(); 
    //     $d->div()->slot(); 
    //     $s = VueSFCCompiler::ConvertToVueRenderMethod($d); 
    //     $expected = 'render(){const{h,useSlots}=Vue;return [h(\'div\',slots.default())]}'; 
    //     $this->assertEquals($expected, 
    //     $s, 'not matching'); 
    // }
}   