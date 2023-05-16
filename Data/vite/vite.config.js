// vite.config.js
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite';
import vue from "@vitejs/plugin-vue";
'<% project.imported.plugins %>'

export default defineConfig({
    plugins:[
        '<% project.plugins %>'
    ],
    /* important for project application. will allow the project to serve a main root application */
    base:'./', 
    build:{
        manifest:true,
        outDir:'<% project.outdir %>',
        emptyOutDir:true,
        sourceMap:true,
        copyPublicDir:false,
        rollupOptions: {
            output: {
              entryFileNames: 'app-[hash].js',
              assetFileNames: '[ext]/[name].[ext]', 
              chunkFileNames: (i)=>{ 
                /* help build split according to src/components/lib */
                let dir = '', n='', search='', idx=null;
                if (i.name == 'vendor'){
                  return '[name]-[hash].js';
                } 
                if (i.isDynamicEntry && i.facadeModuleId){
                    n = i.facadeModuleId;
                    search = __dirname +'/src/components/lib/';
                    if (n.indexOf(search) == 0){
                        n = n.substring(search.length);
                        idx = n.lastIndexOf('/');
                        if (idx != -1){
                          n = n.substring(0, idx);
                        }
                        dir = n+"/";
                    } 
                }
                return `js/${dir}[name].js`;
              },
              manualChunks: (id) => {
                if (id.includes('node_modules')) {  
                   return "vendor";   
                }
              }
            }
          }
    },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    }
})