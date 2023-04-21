<?php

use igk\js\Vue3\Helpers\JSUtility;

if (!function_exists('vue_js_treat_expression')) {
    function vue_js_treat_expression(string $expression)
    {
        return JSUtility::TreatExpression($expression);
    }
}