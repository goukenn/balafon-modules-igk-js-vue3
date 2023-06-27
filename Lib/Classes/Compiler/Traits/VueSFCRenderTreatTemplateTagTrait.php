<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderTreatTemplateTagTrait.php
// @date: 20230524 10:46:32
namespace igk\js\Vue3\Compiler\Traits;


///<summary></summary>
/**
* trait template trait
* @package igk\js\Vue3\Compiler\Traits
*/
trait VueSFCRenderTreatTemplateTagTrait{
    public function resolveSlotAttribute(& $attr){
        $keys = array_keys($attr);
        $props = '';
        $v_slot = false;
        $name = null;
        if ($keys){
            foreach($keys as $key){
                if (preg_match("/(v-slot(:?)|#)(?P<name>.+)?/", $key, $tab)){
                    $v = $attr[$key];
                    $v_slot = true;
                    if (!is_bool($v)){
                        $props = $v;
                    }
                    if (!empty($tab['name'])){
                        $name = $tab['name'];
                    }
                    unset($attr[$key]);
                }
            }
        }
        return compact('v_slot', 'name', 'props');
    }
}