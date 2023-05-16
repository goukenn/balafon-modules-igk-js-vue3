

const { exec } = require('child_process');
import { loadEnv } from 'vite';

async function export_cmd(cmd) {
    var response = await new Promise((resolve, reject) => {
        const d = exec(cmd, (error, stdout, stderr) => {
            if (stderr) {
                reject(stderr);
                return;
            }
            resolve(stdout);
        });
    }).catch((e) => {
        console.log('error : ', e);
    });
    return response;
}


/**
 * help load controller modules definitions - with virtual demands
 * @param {*} options {
 * controller: 'core project controller'
 * }
 * @returns 
 */
function balafonViewHandler(options) {
    const name = 'balafon-view-handler';
    const filter = /\.phtml$/
    const v_modules = [
        'virtual:balafon-pcss',   // get project style
        'virtual:balafon-i18n',   // get project locale(lang) function 
        'virtual:balafon-view',   //
        'virtual:balafon-api',    // get api uris
        'virtual:balafon-corejs',
        'virtual:balafon-routes', // to inject routes() 
        'virtual:balafon-menus',  // to inject menus()
        'virtual:balafon-corejs-vite-helper',
    ];
    const v_idxs = []
    const v_pwd = process.cwd();
    let _env = loadEnv(process.env.NODE_MODE, v_pwd);
    let wdir = options.workingdir || _env.VITE_IGK_WORKING_DIR || process.env.IGK_VITE_WORKING_DIR || './';
    let ctrl = options.controller;
    let lang = options.lang || 'en';
    const refUri = options.refUri || (_env.VITE_APP_URL ? 'import.meta.env.VITE_APP_URL' : null);
    const route_name = options.route_name || 'vue-dashboard-router.pinc';
    const menu_name = options.route_name || 'vue-dashboard-menus.pinc';
    const api_name = options.api_name || 'vue-dashboard-api.pinc';
    return {
        name,
        transform(src, id) {
            if (filter.test(id)) {
                let lang = options.lang || 'en';
                const cmd = 'cd ' + wdir + ' && balafon --wdir:' + wdir
                    + ' --app-dir:' + v_pwd
                    + ' --lang:' + lang
                    + ' --vue3:build-view ' + ctrl + ' ' + id;
                var response = new Promise((resolve, reject) => {
                    const d = exec(cmd, (error, stdout, stderr) => {
                        if (stderr) {
                            reject(stderr);
                            return;
                        }
                        resolve({
                            code: stdout,
                            map: null
                        });
                    });

                });
                return response;
            }
        },
        moduleParsed(i) {
            // 
        },
        resolveId(id) {
            const idx = v_modules.indexOf(id);
            if (idx !== -1) {
                let v_idx = '\0' + v_modules[idx];
                v_idxs[v_idx] = id;
                return v_idx;
            }
        },
        async load(id) {
            const idx = id in v_idxs;
            if (idx) {
                // get default style for controller 
                let name = v_idxs[id];
                let content = '';
                let cmd = '';
                switch (name) {
                    case 'virtual:balafon-pcss':
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --gen:css ' + ctrl;
                        var response = await new Promise((resolve, reject) => {
                            const d = exec(cmd, (error, stdout, stderr) => {
                                if (stderr) {
                                    reject(stderr);
                                    return;
                                }
                                content = stdout;
                                let p = `let l = document.createElement("style"); document.body.appendChild(l); l.append(\`${content}\`);`;
                                resolve(p);
                            });
                        });
                        return response;
                    case 'virtual:balafon-corejs-vite-helper':// core helper 
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --vue3:vite-get-document balafon-corejs-vite-helper ' + ctrl;
                        var response = await new Promise((resolve, reject) => {
                            const d = exec(cmd, (error, stdout, stderr) => {
                                if (stderr) {
                                    reject(stderr);
                                    return;
                                }
                                resolve(stdout);
                            });
                        }).catch((e) => {
                            console.log('error : ', e);
                        });
                        // console.log('initialize command response : '+name, response);
                        return response;
                    case 'virtual:balafon-corejs':
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --vue3:vite-get-document balafon-corejs ' + ctrl;
                        var response = await new Promise((resolve, reject) => {
                            const d = exec(cmd, (error, stdout, stderr) => {
                                if (stderr) {
                                    reject(stderr);
                                    return;
                                }
                                resolve(stdout);
                            });
                        }).catch((e) => {
                            console.log('error : ', e);
                        });
                        return { code: response };
                    case 'virtual:balafon-i18n':
                        /**
                         *  export { locale(lang) },
                         */
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --vue3:vite-get-document balafon-i18n ' + ctrl + ' ' + lang;
                        return export_cmd(cmd);
                    case 'virtual:balafon-routes':
                        /**
                         *  export { routes() },
                         */
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --vue3:vite-get-document balafon-routes '
                            + [ctrl, route_name, "'/* @vite-ignore */ " + refUri + "'"].join(' ');
                        return export_cmd(cmd);
                    case 'virtual:balafon-menus':
                        /**
                         *  export { menus() },
                         */
                        cmd = 'cd ' + wdir + ' && balafon '
                            + ' --vue3:vite-get-document balafon-menus '
                            + [ctrl, menu_name].join(' ');
                        return export_cmd(cmd);
                    case 'virtual:balafon-api':
                             /**
                         *  export { api() },
                         */
                        cmd = 'cd ' + wdir + ' && balafon '
                        + ' --vue3:vite-get-document balafon-api '
                        + [ctrl, api_name].join(' ');
                        return export_cmd(cmd);
                    case 'virtual:balafon-view':
                    default:
                        return 'export const msg="not implement";';
                }
            }
        },
        buildStart() {
            console.log("[BLF]: start build *****************************");
        }
    }
}
export {
    balafonViewHandler
}