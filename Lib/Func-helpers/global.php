<?php

use igk\js\Vue3\Helpers\JSUtility;

if (!function_exists('vue_js_treat_expression')) {
    /**
     * treat js expression
     * @param string $expression 
     * @param array $vars variable to ignore
     * @return string 
     */
    function vue_js_treat_expression(string $expression, array $vars = [])
    {
        return JSUtility::TreatExpression($expression, $vars);
    }
}