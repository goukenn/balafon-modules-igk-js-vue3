// vite.config.js
import { defineConfig } from 'vite';
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins:[
        vue()
    ],
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
              chunkFileNames: '[name]-[hash].js',
              manualChunks: (id) => {
                if (id.includes('node_modules')) {  
                   return "vendor";   
                }
              }
            }
          }
    }
})