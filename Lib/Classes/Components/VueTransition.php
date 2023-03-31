<?php
// @author: C.A.D. BONDJE DOUE
// @filename: VueTransition.php
// @date: 20220727 07:47:46
// @desc: vue transition 

namespace igk\js\Vue3\Components;

use IGKException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use ReflectionException;
use IGK\System\Exceptions\EnvironmentArrayException;

/**
 * represent vue transition
 * @package igk\js\Vue3\Components
 * @extra ttributes \
 *     ?string name:   \
 *     ?string mode: in-out|out-in|default
 *     ?string type: animation|transition
 *     ?int|{enter:int , leave:int} :duration: total duration 
 *     ?bool :css disable css transition auto detection in case of using js animation library
 *     
 */
class VueTransition extends VueComponent
{
    protected $tagname = "transition";

    /**
     * add transition component
     * @return mixed|VueTransitionComponent
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     * @throws EnvironmentArrayException 
     */
    public function addComponent()
    {
        ($this->getChildCount() == 0) || igk_die("transition already have child");
        $g = new VueTransitionComponent();
        return $this->add($g);
    }
    protected function _add($n, $force = false):bool
    {
        ($this->getChildCount() == 0) || igk_die("transition already have child");
        return parent::_add($n, $force);
    }
    /**
     * on before enter js method
     * @param mixed $value 
     * @return $this 
     */
    public function vOnBeforeEnter($value)
    {
        $this->vOn("before-enter", $value);
        return $this;
    }
    public function vOnEnter($value)
    {
        $this->vOn("enter", $value);
        return $this;
    }
    public function vOnAfterEnter($value)
    {
        $this->vOn("after-enter", $value);
        return $this;
    }
    public function vOnEnterCancelled($value)
    {
        $this->vOn("enter-cancelled", $value);
        return $this;
    }
    public function vOnBeforeLeave($value)
    {
        $this->vOn("before-leave", $value);
        return $this;
    }
    public function vOnLeave($value)
    {
        $this->vOn("leave", $value);
        return $this;
    }
    public function vOnAfterLeave($value)
    {
        $this->vOn("after-leave", $value);
        return $this;
    }
    public function vOnLeaveCancelled($value)
    {
        $this->vOn("leave-cancelled", $value);
        return $this;
    }
    /**
     * must appear during vue transition
     * @return $this 
     * @throws IGKException 
     */
    public function appear()
    {
        $this->activate(__FUNCTION__);
        return $this;
    }
    // + | CLASS
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setenterFromClass($value)
    {
        $this->setAttribute("enter-from-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setenterActiveClass($value)
    {
        $this->setAttribute("enter-active-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setenterToClass($value)
    {
        $this->setAttribute("enter-to-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setappearFromClass($value)
    {
        $this->setAttribute("appear-from-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setappearActiveClass($value)
    {
        $this->setAttribute("appear-active-class", $value);
        return $this;
    }
    public function setappearToClass($value)
    {
        $this->setAttribute("appear-to-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setleaveFromClass($value)
    {
        $this->setAttribute("leave-from-class", $value);
        return $this;
    }
    public function setleaveActiveClass($value)
    {
        $this->setAttribute("leave-active-class", $value);
        return $this;
    }
    /**
     * class prop to customizing transition classes
     * @param mixed $value 
     * @return $this 
     */
    public function setleaveToClass($value)
    {
        $this->setAttribute("leave-to-class", $value);
        return $this;
    }
}
