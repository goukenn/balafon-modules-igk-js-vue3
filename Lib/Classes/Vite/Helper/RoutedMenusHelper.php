<?php

// @author: C.A.D. BONDJE DOUE
// @filename: RoutedMenusHelper.php
// @date: 20230510 15:11:47
// @desc: 

namespace igk\js\Vue3\Vite\Helper;

use IGK\Controllers\BaseController;
use igk\js\Vue3\Vite\RoutedMenuDefinition;
use igk\js\Vue3\Vite\RoutedMenusBase;
use IGK\System\Regex\Replacement;
use ReflectionMethod;

class RoutedMenusHelper
{
    private $m_menus = [];
    private $m_async_menus = [];
    var $ctrl;
    var $helper;
    public function __construct(BaseController $ctrl, $helper)
    {
        $this->ctrl = $ctrl;
        $this->helper = $helper;
    }
    /**
     * get builded menu
     * @return array
     */
    public function getMenus()
    {
        return $this->m_menus;
    }
    /**
     * 
     * @param string $class_name 
     * @return void 
     */
    public function bindRoutedMenu(string $class_name)
    {
        RoutedMenusBase::BindClass($class_name, $this->ctrl, $this->helper, function ($routed_method, $is_async) {
            if ($is_async) {
                $this->m_async_menus[$routed_method->name] = $routed_method;
            } else {
                $this->m_menus[$routed_method->name] = $routed_method;
            }
        });
    }
}
