<?php

// @author: C.A.D. BONDJE DOUE
// @filename: IRouterParams.php
// @date: 20220726 17:51:00
// @desc: 

namespace igk\js\Vue3\Libraries;

/**
 * router param properties
 * @package igk\js\Vue3\Libraries
 * @property string $path path that will be matched
 * @property ?string $name name use for this route
 * @property ?string|JSAnonymousMethodExpression $redirect 
 * @property null|VueComponentName|VueComponentDefinition $component component definition
 * @property JSObjectExpression $components component definition {default: defaultComponent, ...}
 * @property null|string|array<string> $alias
 * @property null|array<IRouterParams> $children;
 * @property bool|JSObjectExpression|JSAnonymousMethodExpression $props use properties
 * @property ?string beforeEnter
 * @property ?JSObjectExpression $meta 
 */
interface IRouterParams{
}