// default entry script 
// @desc: igk.js.vue3

"use strict";
(function () {
    const _sfc_register = {};
    // store shared data between different app 
    const _app_shared_uses = {};
    /**
     * load .vue data
     * @param {t} t text data to load
     * @param {*} resolve promise resolution
     * @param {*} reject in case of eror
     */
    function _load_data(t, resolve, reject) {
        var g = igk.createNode("div");
        g.setHtml(t);
        var template = g.select(">template").first();
        var script = g.select(">script").first();
        var style = g.select(">style").first();
        let prom = [];
        let data = null;
        if (script) {
            // ----------------------------------------------
            // load script as a module
            //  
            var dataUri = '';
            if (script.o.hasAttribute('setup')) {
                let src = script.text();
                let vars = sfc.detectVars(src, 0);
                let s = ''
                if (vars) {
                    s = "\n" + ' return {';
                    var sp = '';
                    for (var i of vars) {
                        s += sp + i.name;
                        sp = ',';
                    }
                    s += '}';
                }
                // let fc = (new Function(`return { setup(props, ctx){ ${script.text()}${s} } };` )).apply(); 
                // data = fc;
                // TODO : EXTRA DCUMENT PROPERTY IN SETUP
                dataUri = igk.system.modules.esm`export default{ setup(props, ctx){${script.text()}${s}}}`;
            } else {
                dataUri = igk.system.modules.esm`${script.text()}`;
            }
            prom.push(
                import(dataUri).then((d) => {
                    data = d.default;
                }).catch(e => {
                    console.log("error: ", e);
                    reject();
                })
            );

        }
        if (style) {
            // TODO: Style import 
            let s = document.createElement('style');
            s.innerHTML = style.o.innerHTML;
            document.head.appendChild(s);
            const app_uuid = 'app';
            if (style.o.hasAttribute('scoped')) {
                let temp = "";

            }
        }
        Promise.all(
            prom
        ).catch(() => {

        })
            .then(() => {
                if (template) {
                    let rdata = {
                        "template": template.o.innerHTML,
                        ...data
                    };
                    resolve(rdata);
                } else if (reject) {
                    reject();
                }
            });
    };

    /**
     * detect variable in root scoope and return it
     * @param {String} src 
     * @param {Int} sourceDepth 
     */
    function _detect_vars(src, sourceDepth) {
        const len = src.length;
        let pos = 0; let ch = ''; let scomment = false; let end = false; let ret = [];
        let word = ''; var name = 0; let depth = 0;
        let _wtype = '';
        let skip = false;
        sourceDepth = igk.isUndef(sourceDepth) ? 0 : sourceDepth;
        while (!end && (pos < len)) {
            ch = src[pos];
            if (scomment) {
                if ((ch != '*') && (ch != '/')) {
                    scomment = false;
                }
            }
            switch (ch) {
                case '/':
                    if (scomment) {
                        // skip line 
                        pos = src.indexOf('\n', pos);
                        if (pos == -1) {
                            end = true;
                        }
                        scomment = false;
                    } else {
                        scomment = true;
                    }
                    ch = '';
                    break;
                case '*':
                    if (scomment) {
                        pos = src.indexOf('*/', pos);
                        if (pos == -1) {
                            end = true;
                        }
                        scomment = false;
                    }
                    break;
                case '{':
                    depth++;
                    break;
                case '}':
                    depth--;
                    break;
                case "\n":
                case ' ':
                case ',':
                    word = word.trim();
                    var new_instruct = skip && (ch == '\n');
                    if (word && !skip && (depth == sourceDepth)) {
                        if (/^(var|let|const|function)$/.test(word)) {
                            _wtype = word;
                            //expect read var name             
                            if (name = /^\s*,*\s*[_a-z]([_a-z0-9]+)?/id.exec(src.substring(pos))) {
                                ret.push({ name: name[0], type: word });
                                pos += name.indices[0][0] + name[0].length;
                            }
                        } else if (/return/.test(word)) {
                            end = true;
                            return null;
                        }
                        word = '';
                    }
                    if (skip && new_instruct) {
                        skip = false;
                    }
                    break;
                case '=':
                    skip = true;
                    break;
                case ';':
                    word = '';
                    ch = '';
                    skip = false;
                    break;
                case '\r':
                    ch = '';
                    break;
                case '"':
                case "'":
                    // skip string litteral: 
                    do {
                        pos = src.indexOf(ch, pos);
                        if (pos == -1) {
                            end = true;
                            break;
                        }
                    } while (src[pos - 1] == "\\");
                    break;
                default:
                    break;
            }
            if (end)
                break;
            word += ch;
            pos++;
        }
        return ret;
    }




    const sfc = {
        load(text) {
            var dataUri = igk.system.modules.esm`${script.text()}`;
            prom.push(
                import(dataUri).then((d) => {
                    data = d.default;
                })
            );
        },
        /**
         * Load single file component content
         * @param {*} text 
         * @param {*} resolve 
         * @param {*} reject 
         */
        loadSFC(text, resolve, reject) {
            return _load_data(text, resolve, reject);
        },
        detectVars: _detect_vars
    };

    const _NS = igk.system.createNS("igk.js.vue3", {
        sfc,
        import: async function (data) {
            const s = igk.system.modules.esm`${data}`;
            return import(s);
        },
        /**
         * resolve uri
         * @param {string} s 
         * @returns 
         */
        resolve(s) {
            if (document.baseURI && !s.startsWith("//")) {
                let _x = !1;
                if (s.startsWith("./")) {
                    _x = 2;
                } else if (s.startsWith("/")) {
                    _x = 1;
                }
                if (_x !== !1) {
                    s = document.baseURI + "/" + s.substring(_x);
                }
            }
            return s;
        },
        /**
         * load vue source script
         * @param {*} s uri target 
         * @returns 
         */
        loadScript(s) {
            s = _NS.resolve(s);
            var p = new Promise((resolve) => {
                return fetch(s, {
                    method: 'GET',
                    headers: {
                        "Content-Type": "text/xml",
                        "AJX": 1,
                        "IGK-AJX": 1,
                        "IGK-AJX-APP": "vue"
                    }
                }).then((d) => {
                    return d.text();
                }).then((t) => {
                    _load_data(t, resolve);
                });
            });
            return p;
        },
        /**
         * load script file 
         * @param {string} s 
         * @returns 
         */
        loadVueComponent(s) {
            return igk.js.vue3.loadScript(s);
        },
        /**
         * import vue for lazy 
         * @param {function|string} s 
         * @returns 
         */
        importVueComponent(s) {
            var p = new Promise((resolve) => {
                if (typeof (s) === 'function') {
                    resolve(s.apply());
                } else {
                    _load_data(s, resolve);
                }
            });
            return p;
        }
    });

    igk.appendProperties(_NS, {       
        shared(data) {
            for (var i in data) {
                _app_shared_uses[i] = data[i];
            }
        }
    });
    function initAppAndMount(app, t) {
        if (_app_shared_uses) {
            for (let i in _app_shared_uses)
                app.use(_app_shared_uses[i]);
        }
        app.mount(t);
    };

    igk.winui.initClassControl("igk-vue-clone", function () {
        // console.log('init clone ...');
        const { createApp } = Vue;
        const data = this.getAttribute('igk-data');
        let q = $igk(data).first();
        if (q) {
            let c = q.getAttribute('igk-clone-data') || q.getHtml();
            this.setHtml(c);
            initAppAndMount(createApp(), this.o);
        }
    });
    // +| dynamic create application with shared content
    igk.winui.initClassControl("igk-vue-app", function () {
        // console.log('init clone ...');
        const { createApp } = Vue;
        const data = this.getAttribute('igk-data');
        let q = $igk(data).first();
        if (q) {
            let c = q.getAttribute('igk-clone-data') || q.getHtml();
            this.setHtml(c);
            initAppAndMount(createApp(), this.o);
        }
    });

    // init root view clonable data before main - application - start
    $igk('.igk-vue-clonable').each_all(function () {
        this.o.setAttribute('igk-clone-data', this.getHtml());
    });
})();


(function () {
    let _app_component; 
    /**
     * 
     * @param {*} assets asset object
     * @param {*} component 
     * @param {*} prefix 
     */
    function refImport(assets, component, prefix) {
        prefix = prefix || '';
        let tab = component.split('|');
        component = tab[0];
        const ext = tab[1] ?? 'js';
        const s = prefix + "/" + component + "." + ext;
        if (s.startsWith('../')) {
            s = s.substring(3);
        }
        if (ext == 'vue') {
            return () => assets.import(s);
        }
        let uri = "/" + assets.path(s);
        return () => import(uri);
    };
    const _NS = igk.system.createNS('igk.js.vue3', {
        initAsset(asset) {
            const b = asset.assets;
            const { sfc } = igk.js.vue3;
            return {
                /**
                 * resolve path
                 * @param {string} path 
                 */
                path(path) {
                    return b + path;
                },
                /**
                 * import .vue file
                 * @param {string} path 
                 */
                import(path) {
                
                    const uri = b + path;
                    return new Promise((resolve, reject) => {
                        return window.fetch(uri).then((d) => {
                            return d.text();
                        }).then((src) => {
                            sfc.loadSFC(src, resolve, reject); 
                        }).catch((e) => {
                            console.debug("failed to download." + uri, e);
                        });
                    });
                }
            }
        },
        /**
         * 
         * @param {*} AppComponent 
         * @param {*} components 
         * @param {*} assets asset object 
         * @param {*} defineAsyncComponent 
         * @param {*} prefix 
         */
        loadAsyncJsComponent(AppComponent, components, assets, defineAsyncComponent, prefix) {
            for (let component of components) {
                let rf = defineAsyncComponent(refImport(assets, component, prefix));
                component = component.split('|')[0];
                if (component.indexOf('/') != -1) {
                    component = component.replaceAll('/', '.');
                    let tab = component.split('.').filter(a => a && (a.length > 0));
                    let ns = tab.slice(0, -1).join('.');
                    let name = tab.slice(-1)[0];
                    let p = {};
                    p[name] = rf;
                    igk.system.createPNS(AppComponent, ns, p)
                    //igk.system.createExtensionProperty(AppComponent, ns, p);
                } else {
                    AppComponent[component] = rf;
                }
            }
            _app_component = AppComponent;
        }
    });

    igk.defineProperty(_NS, 'appComponents', {
        get(){
            return _app_component;
        }
    })
})();