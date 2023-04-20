// vite.config.js
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite';
import vue from "@vitejs/plugin-vue";
'<% project.imported.plugins %>'

export default defineConfig({
    plugins:[
        '<% project.plugins %>'
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
    },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    }
})