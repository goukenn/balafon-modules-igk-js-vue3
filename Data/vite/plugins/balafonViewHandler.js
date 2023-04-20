const { exec } = require('child_process');

function balafonViewHandler(options) {
    const name = 'balafon-view-handler';
    const filter = /\.phtml$/
    return {
        name,
        transform(src, id) {
            if (filter.test(id)) { 
                let wdir = options.workingdir || './';
                let ctrl = options.controller;
                let lang = options.lang || 'en'; 
                const cmd = 'cd '+wdir+' && balafon --wdir:'+ wdir
                + ' --lang:'+lang
                + ' --vue3:build-view '+ctrl+' '+id;
                var response = new Promise((resolve, reject)=>{
                    const d = exec(cmd, (error, stdout, stderr)=>{ 
                        if (stderr){
                            reject(stderr);
                            return;
                        }
                        console.log('[blf-viewhandler]'+stdout);
                        resolve({
                            code: stdout,
                            map:null
                        }); 
                    });

                }); 
                return response;
            }
        },
        moduleParsed(i){
            // 
        } 
    }
}
export default balafonViewHandler;