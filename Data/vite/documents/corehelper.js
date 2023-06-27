/* helper to resolve namespace */
const pns =  {system:{getNS(l,p){p = p || window; const tab = l.split('.').filter(a=>a); for(var i in tab){let n = tab[i];if (!(n in p))return null;p = p[n];}return p;}}}; 
window.igk = window.igk || pns;  
export const getNS= pns.system.getNS;