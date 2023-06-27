<?php
// @author: C.A.D. BONDJE DOUE
// @file: VueSFCRenderVisitorChainList.php
// @date: 20230524 07:22:46
namespace igk\js\Vue3\Compiler;

use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
* 
* @package IGK
*/
class VueSFCRenderVisitorChainList{
    
    var $resolvedComponent = false;

    /**
     * slot name for resolved component list
     * @var mixed
     */
    var $slotName;
    /**
     * marker name 
     * @var mixed
     */
    private $m_name;
    /**
     * chain info 
     * @var mixed
     */
    private $m_childs;
    /**
     * 
     * @var mixed
     */
    private $m_parent;
    private $m_state;
    private $m_visitor;
    private $m_childCounter;
    /**
     * store buffer 
     * @var mixed
     */
    private $m_sb;

    /**
     * store slots
     * @var array
     */
    private $m_slots = [];

    /**
     * current separator
     * @var string
     */
    private $m_sep = '';

    /**
     * get the name of this chain
     * @return ?string 
     */
    public function getName(){
        return $this->m_name;
    }
    /**
     * incremetn child counter 
     * @return $this 
     */
    public function increment(){
        $this->m_childCounter++;
        return $this;
    }

    public function __construct($visitor){
        $this->m_visitor = $visitor;
    }
    public function __toString()
    {
        if (is_null($this->m_parent)){
            return 'sfc-root-chain';
        }
        return 'sfc-root-chain:'.$this->m_name;
    }
    public function __debugInfo()
    {
        return [];
    }
    public function getSlotProps(){
        return $this->m_slots;
    }
    public function setBuffer(StringBuilder $buffer){
        $this->m_sb = $buffer;
    }
    public function getBuffer(){
        return $this->m_sb;
    }
    /**
     * goto previous
     */
    function prev(){

    }
    public function hasChilds():bool{
        return $this->m_childCounter>0;
    }
    /**
     * get if this element is a component
     * @return bool 
     */
    public function isComponent():bool{
        igk_wln_e("is component");
        return $this->m_visitor->isResolvableComponent($this->m_name);
    }
    public function pushSlotComponent(string $name, string $content){
        $this->m_slots[$name] = $content;
    }
    public function pushDynamicSlotComponent($expression, $content){
        $this->m_slots[] = new VueSFCDynamicSlot($expression, $content);
    }
    /**
     * push marker name 
     * @param string $marker_name 
     * @param mixed $state 
     * @return static return the new chain instance
     */
    public function push(string $marker_name, & $state){
        $l = new static($this->m_visitor);
        $l->m_name = $marker_name;
        $l->m_state = $state;
        $l->m_parent = $this;
        return $l;
    }
    /**
     * get parent childs chain info 
     * @return ?static 
     */
    public function parent(){
        if ($this->m_parent){
            return $this->m_parent;
        }
        return null;
    }
    /**
     * get state 
     * @return mixed 
     */
    public function & getState(){
        return $this->m_state;
    }
    
}