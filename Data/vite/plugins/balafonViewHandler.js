

const { exec } = require('child_process');

// import { exec } from 'child_process';

/**
 * help load controller modules definitions - with virtual demands
 * @param {*} options 
 * @returns 
 */
function balafonViewHandler(options) {
    const name = 'balafon-view-handler';
    const filter = /\.phtml$/
    const v_modules = ['virtual:balafon-pcss', 'virtual:balafon-view', 'virtual:balafon-api', 'virtual:balafon-corejs'];
    const v_idxs = []
    let wdir = options.workingdir || './';
    let ctrl = options.controller;
    let lang = options.lang || 'en';
    return {
        name,
        transform(src, id) {
            if (filter.test(id)) {
                let wdir = options.workingdir || './';
                let ctrl = options.controller;
                let lang = options.lang || 'en';
                const cmd = 'cd ' + wdir + ' && balafon --wdir:' + wdir
                    + ' --lang:' + lang
                    + ' --vue3:build-view ' + ctrl + ' ' + id;
                var response = new Promise((resolve, reject) => {
                    const d = exec(cmd, (error, stdout, stderr) => {
                        if (stderr) {
                            reject(stderr);
                            return;
                        }
                        // console.log('[blf-viewhandler]' + stdout);
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
            // console.log('load:', id);
            if (idx) {
                // get default style for controller 
                let name = v_idxs[id];
                let content = '';
                switch (name) {
                    case 'virtual:balafon-pcss':
                        const cmd = 'cd ' + wdir + ' && balafon '
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
                    case 'virtual:balafon-view':
                    case 'virtual:balafon-api':
                    case 'virtual:balafon-corejs':
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