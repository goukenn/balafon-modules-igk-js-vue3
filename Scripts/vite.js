'use strict;';
(function(){
    const _vite = igk.system.createNS('igk.js.vue3.vite', {
        initApp(app, options){                        
            // + | setup and initialize application bofore 
            // console.log(options.uses);
            if (options.uses){
                Object.entries(options.uses).forEach(([key, value])=>{ 
                   app.use(value);
                });            
            } 
            return app;
        },
        /**
         * 
         * @param {*} app application component not initialize
         * @param {*} options 
         */
        initAppFromLib(app, options){ 
            const { createApp, /* createWebHistory */} = options.lib;             
            _vite.initApp(createApp(app), options).mount(options.target);
        }
    });
})();