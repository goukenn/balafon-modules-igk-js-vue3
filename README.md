# igk\js\Vue3 module

use load vue3 js library

## import in view context

```php
igk_require_module(igk\js\vue3::class)
```

## dynamic import vue SFC standalone file with balafon.corejs

```js
const xsample = await igk.js.vue3.loadScript("./assets/winui/xsample.vue");
```


## Create vue3 application 

```php

$t->div()->vue_app(
    "app",[
        <<<JS
data(){
    return {
        x: "Hello Vue3 app !!!"
    }
}
JS
    ]
)->host(function($a){
    $a->div()->panelbox()->Content = "message : {{ x }}";
});
```


# Vue3 component

## vue_app
create a vue app 

## vue_scripttemplate(string $id)
create a vue script template


$app->setDefs($script_definition)

## utilisation de componsent
```
$app->uses('defineComponent')
```

## utilisation de plusieurs library de vue additionnel
```
$app->uses(['defineComponent', 'ref'])
```


## import vue task in lazy loading
in Views define  .vue-routes.pinc

```PHP
<?php

return [
    "/"=>VueHelper::LazyLoad($ctrl::uri("?page=home")),        
    "/projects"=>VueHelper::LazyLoad($ctrl::uri("?page=projects")),        
    "/privacy"=>VueHelper::LazyLoad($ctrl::uri("?page=privacy")),        
    "/about"=>VueHelper::LazyLoad($ctrl::uri("?page=about")),        
    "/tasks"=>VueHelper::LazyLoad($ctrl::uri("?page=tasks")),        
];
```

Import it on a vues
```PHP
$router = new VueRouter;
$router->baseUri = '/';
$router->load(ViewHelper::Include("/.vue-routes.pinc"));
```


# Route : Import vue component by name

```php
$router->addRouteWithDefinedComponent('/privacy', 'lazyPrivacy', ['useProps'=>false]);
```

will generate a code that will include . NOTE component must be define before the rendering 
in defs application is a good place.

```php
->vue_app('main',[...])->setDefs(<<<JS
const lazyPrivacy = () => import('/lazyPrivacy.vue')
JS)
```


- Fix teleport generation 