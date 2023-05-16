import { createApp , h, Text} from 'vue'
import App from './App.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createI18n } from 'vue-i18n'
import './assets/main.css'
import 'virtual:balafon-pcss'
import { locale } from 'virtual:balafon-i18n'
import { getNS } from 'virtual:balafon-corejs-vite-helper'

const ready =  getNS('igk.ready'); 
if (ready){
    const _lib ={createApp, h, createRouter, createWebHistory, Text, createI18n};
    const _P = igk.system.createNS('igk.js.vue3.vite', {});
    igk.defineProperty(_P, 'appComponent', {
        get(){
            return App;
        }
    }); 
    const _NS = getNS(import.meta.env.VITE_IGK_ENTRY_NAMESPACE);
    _NS.lib = _lib;
    ready(function(){          
        igk.js.vue3.vite.initAppFromLib(App, _NS);
    });
} else {
    const router = createRouter({
        history : createWebHistory(''),
        routes: [
            {
                path:"/",
                component: { 
                    render(){
                        return h('div', 'Home page');
                    }
                }
            },
            {
                path:"/about",
                component: { 
                    render(){
                        return h('div', 'About Page');
                    }
                }
            }
        ]
    });
    const i18n = createI18n(locale('fr'));
    createApp(App).use(router).use(i18n)
    .mount('#app')
}