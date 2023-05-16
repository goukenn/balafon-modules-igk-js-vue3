<?php
// @author: C.A.D. BONDJE DOUE
// @filename: RoutedMenusBase.php
// @date: 20230510 15:07:39
// @desc:

namespace igk\js\Vue3\Vite;

use IGK\Helper\StringUtility;
use IGK\System\Regex\Replacement;
use ReflectionMethod;

/**
 * routed menu base class 
 * @package igk\js\Vue3\Vite
 */
abstract class RoutedMenusBase
{
    var $routeName;

    var $helper;

    /**
     * set the helper
     * @param mixed $helper 
     * @return void 
     */
    final function setHelper($helper)
    {
        $this->helper = $helper;
    }
    public static function BindClass(string $class_name, $ctrl, $helper, $listener)
    {
        !is_subclass_of($class_name, RoutedMenusBase::class) && igk_die("not a routed menu class");
        $regex = '/^(?P<is_async>async_)?(?P<name>.+)$/';
        $rp = new Replacement;
        $rp->add("/_+/", '.');
        // get menus definitions         
        if ($methods = igk_sys_reflect_class($class_name)->getMethods(ReflectionMethod::IS_PUBLIC)) {
            $cl = new $class_name;
            $cl->setHelper($helper);
            foreach ($methods as $m) {
                if (!($m->getDeclaringClass()->name == $class_name)) {
                    continue;
                }
                if ($m->isStatic()) {
                    continue;
                }
                $s = $m->getName();
                if (!preg_match($regex, $s, $tab)) {
                    continue;
                }

                $is_async = !empty($tab['is_async']);
                $r = $cl->routeName ?? basename(igk_uri($class_name));
                $name = $tab['name'];
                $key = trim($rp->replace($name), '.');
                $routed_method = new RoutedMenuDefinition();
                $routed_method->name = $r . '.' . $key;
                $routed_method->route = "/" . str_replace('_', '-', strtolower($name));
                $routed_method->title = $name;
                $routed_method->component = '<h2>routed menu</h2>';
                // $routed_method->id = StringUtility::CamelClassName($routed_method->name );

                //filter routed menu before adding  
                $def = call_user_func_array([$cl, $m->getName()], [$routed_method, $ctrl]);
                if ($def !== false) {
                    $listener($routed_method, $is_async);
                }
            }
        }
    }
}
