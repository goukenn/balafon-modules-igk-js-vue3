<?php
namespace igk\js\Vue3;

class VueConstants{
    // const API_URL = "https://unpkg.com/vue@3";
    // const API_URL = "https://unpkg.com/vue@3.2.37/dist/vue.global.js";
    // const API_OPS_URL = "https://unpkg.com/vue@3.2.37/dist/vue.global.prod.js";
    const API_URL = "https://unpkg.com/vue@3.2.47/dist/vue.global.js";
    const API_OPS_URL = "https://unpkg.com/vue@3.2.47/dist/vue.global.prod.js";
    const API_RUNTIME_URL = "https://unpkg.com/vue@3.2.47/dist/vue.runtime.global.js";
    const API_RUNTIME_OPS_URL = "https://unpkg.com/vue@3.2.47/dist/vue.runtime.global.prod.js";
    const WEB_CONTEXT = "vue3";

    const TEMPLATE_JS_TYPE = 'text/x-template';
    const CNF_VUE_CDN = 'vue3.cdn';
    const CNF_VUE_ROUTER_CDN = 'vue3.router_cdn';

    const FILE_EXT = '.vue';
    const CORE_JS_NAMESPACE = 'igk.vue3.components';
    const LIB_OPTIONS = 'vue3.Libraries';

    const BUILTIN_DIRECTIVE_CONDITIONAL = 'v-if|v-else|v-else-if';
    const BUILTIN_DIRECTIVE_PRESERVE = 'v-pre';
    const BUILTIN_DIRECTIVES = "v-once|v-html|v-text|".self::BUILTIN_DIRECTIVE_CONDITIONAL.'|'.self::BUILTIN_DIRECTIVE_PRESERVE;

    const JS_VUE_LIB = 'Vue';
    const JS_VUEEX_LIB = 'Vuex';
    const JS_VUE_ROUTER_LIB = 'VueRouter';

    const VUE_METHOD_RENDER = 'h';
    const VUE_METHOD_DEFINE_COMPONENT = 'defineComponent';
    const VUE_METHOD_DEFINE_ASYBC_COMPONENT = 'defineAsyncComponent';
    const VUE_METHOD_RESOLVE_COMPONENT = 'resolveComponent';
    const VUE_METHOD_RESOLVE_DYNAMIC_COMPONENT = 'resolveDynamicComponent';
    const VUE_METHOD_WITH_MODIFIERS = 'withModifiers';
    const VUE_METHOD_WITH_DIRECTIVES = 'withDirectives';

    const VUE_COMPONENT_TEXT = 'Text';
    const VUE_COMPONENT_TRANSITION = 'Transition'; 

    // const VUE_BUILDIN_COMPONENT = 'KeepAlive|Slot|Text|Teleport|Transition|TransitionGroup|Component|Template';
    const VUE_BUILDIN_COMPONENT = 'KeepAlive|Text|Teleport|Transition|TransitionGroup|Component|Template';
    const VUE_BUILDIN_SPECIAL_COMPONENT = 'Slot|Component|Template';

    const VUE_JS_SETUP_EXT = '.vue3-setup.js';

    const DEFAULT_MENU_CLASS  = 'igk-menu igk-vue-menu';

}