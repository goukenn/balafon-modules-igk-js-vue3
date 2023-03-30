<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueLibraryReference.php
// @date: 20230303 08:27:09
namespace igk\js\Vue3\Libraries;


///<summary></summary>
/**
* help referer library
* @package igk\js\Vue3\Libraries
*/
class VueLibraryReference{
    var $ref = []; 

    public function ref($name){
        $this->ref[$name] = $name;
    }
    public function to_array(){
        return array_keys($this->ref);
    }
}