const _NS = (()=>{
    if (typeof(configs)!= 'undefined'){
        return igk.system.getNS(configs.entryNamespace);
    }
    if (window.$appSetting){
        return window.$appSetting._NS;
    }
    return {};
})();