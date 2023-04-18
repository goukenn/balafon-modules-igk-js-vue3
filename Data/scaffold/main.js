(async function (use, target, createApp) {
    try {
        window.igk = window.igk || {system:{getNS(l,p){p = p || window; const tab = l.split('.').filter(a=>a); for(var i in tab){let n = tab[i];if (!(n in p))return null;p = p[n];}return p;}}};
        const options = use();
        if (options) {
            target = options.target || target;

            const coreCreateApp = createApp;
            function _createApp(args) {
                const app = coreCreateApp(args);
                if (typeof (options.init) == 'function') {
                    options.init(app);
                }
                if (options.uses) {
                    options.uses.forEach(element => {
                        app.use(element);
                    });
                }
                return app;
            }
            createApp = _createApp;
        }
    } catch (e) {
        console.log('no config: ' + e);
    }
    createApp(Main).mount(target);
})(() =>  typeof(igk.system.getNS) !='undefined'? igk.system.getNS('<% igk.js.vue3.configs.entry %>') : null, "<% igk.js.vue3.configs.target %>", createApp);