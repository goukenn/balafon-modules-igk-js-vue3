<?php

namespace igk\js\Vue3\Libraries;

use IGK\Helper\StringUtility;
use IGK\System\IO\Path;
use IGKType;
use ReflectionMethod;

abstract class VueRouterUtility
{

    /**
     * build route from class name 
     * @param string $class_name 
     * @param string $path 
     * @return null|array 
     */
    public static function BuildRouteFrom(string $class_name, $path = '/', $refUri=null): ?array
    {
        $d = igk_sys_reflect_class($class_name);
        if (!($methods = $d->getMethods(ReflectionMethod::IS_PUBLIC))) {
            return null;
        }
        $v_otab = [];
        $a = $class_name;
        foreach ($methods as $method) {
            if ($method->isAbstract() || ($method->getDeclaringClass()->getName() != $a)) {
                continue;
            }
            // filter parameter 
            // filter documents 
            $ref = $method;
            $method = $ref->getName();
            $comment = $ref->getDocComment();
            // if (empty($ref->getDocComment()))
            // {
            //     continue;
            // }

            $info = new VueRouterInfo;
            $verbs = "get";
            if (preg_match("/_(?P<verb>(get|post|option|delete|put|store))$/", $method, $tab)) {
                $verbs = $tab['verb'];
                $method = igk_str_rm_last($method, '_' . $verbs);
            }
            // $info->deprecated = true;
            $info->description = "description of ... " . $method;
            $info->name = $method;
            $info->path = $path . $info->name . self::GetArgs($ref);
            $info->verb = $verbs;
            if ($method == 'index')
                $method = null;
            // if (SwaggerGenerator::UpdateRefInfo($g, $ref, $info, $doc)) {
            //     $args = ltrim($info->getArgs(), '/');
            //     $doc->addPath(Path::Combine($page, $method, $args), $verbs, $info);
            // }
            $info->component = sprintf('/* webpackChunkName: '.StringUtility::CamelClassName($info->name).' */()=>import(%s"%s")', 
                $refUri? $refUri.'+': null,
                Path::Combine($path, $info->name)
            );
            $key = $class_name . "/" . $info->name;
            if ($key) {
                if (isset($v_otab[$key])) {
                    $key .= '_' . $verbs;
                }
            }
            $v_otab[$key] = $info;
        }
        return $v_otab;
    }
    /**
     * retrieve default args methods 
     * @param ReflectionMethod $meth 
     * @return null|string 
     */
    public static function GetArgs(ReflectionMethod $meth): ?string
    {
        if ($g = $meth->getParameters()) {
            $sb = [];
            foreach ($g as $key => $value) {
                $type = null;
                $typen = null;
                $primary = false;
                if ($value->hasType()) {
                    $type = $value->getType();
                    $typen = $type->getName();
                    if (!($primary = IGKType::IsPrimaryType($typen)) && IGKType::IsInjectable($type->getName())) {
                        continue;
                    }
                }
                $s = ':' . $value->getName();

                if ($primary) {
                    switch (strtolower($typen)) {
                        case 'int':
                            $s .= '(\\\\d+)';
                            break;
                        case 'float':
                            $s .= '(\\\\d+(.\\\\d+)?)';
                            break;
                    }
                }
                if ($value->isOptional()) {
                    $s .= '?';
                } else {
                    if ($value->isVariadic()) {
                        if ($value->isDefaultValueAvailable()) {
                            $s .= '*';
                        } else {
                            $s .= '+';
                        }
                    }
                }
                $sb[] = $s;
            }
            if ($sb)
                return '/' . implode("/", $sb);
        }
        return null;
    }
}
