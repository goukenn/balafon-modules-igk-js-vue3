const { exec } = require('child_process');

function balafonViewHandler(option) {
    const name = 'balafon-view-handler';
    const filter = /\.phtml$/
    return {
        name,
        transform(src, id) {
            if (filter.test(id)) { 
                const cmd = 'cd '+option.workingdir+' && balafon --wdir:'+ option.workingdir 
                + ' --lang:en'
                + ' --vue3:build-view '+option.controller+' '+id;
                var response = new Promise((resolve, reject)=>{
                    const d = exec(cmd, (error, stdout, stderr)=>{ 
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