<?php


// @author: C.A.D. BONDJE DOUE
// @filename: VueLibraryBase.php
// @date: 20220726 12:35:05
// @desc: 
namespace igk\js\Vue3\Libraries;

abstract class VueLibraryBase implements IRefLibrary{
    const VUE = 'vue';
    /**
     * define name of this library
     * @var mixed
     */
    protected $m_name;

    public function getName(){
        return $this->m_name;
    }
    protected function __construct(?string $name=null)
    {        
        $this->m_name = $name;
    }
 
    /**
     * 
     * @param mixed $option 
     * @return array 
     */
    public abstract function useLibrary($option=null):array;

    /**
     * 
     * @param mixed $option 
     * @return null|string 
     */
    public abstract function render($option=null):?string;
}
