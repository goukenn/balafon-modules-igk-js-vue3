/* helper to resolve namespace */
window.igk = window.igk || {system:{getNS(l,p){p = p || window; const tab = l.split('.').filter(a=>a); for(var i in tab){let n = tab[i];if (!(n in p))return null;p = p[n];}return p;}}};
export const getNS=window.igk.system.getNS;