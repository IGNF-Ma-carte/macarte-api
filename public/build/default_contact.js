(()=>{"use strict";function e(e){const r=new XMLHttpRequest;if(r.open("GET",e,!1),r.send(null),200===r.status)try{/.json$/.test(e)?window.maCarteOptions=JSON.parse(r.responseText):new Function(r.responseText)()}catch(e){}}let r={server:"https://server.ign.fr/",viewer:"https://server/$TYPE/$ID/$TITLE",userProfile:"https://server/user/$NAME",editor:"https://server/edition/$TYPE/$ID",sitePiwik:239,gppKey:"0gd4sx9gxx6ves3hf3hfeyhw",edugeoKey:"1mgehldv90vifl6s5ksf900i"};if(window.maCarteOptions||(e("./config.json"),/gitlab.io/.test(window.location.origin)||e(window.location.origin+"/config-server.json")),window.maCarteOptions){for(let e in window.maCarteOptions)r[e]=window.maCarteOptions[e];delete window.maCarteOptions}else console.error("NO CONFIG FILE!\n Ajouter un fichier confg.json dans le répertoire des assets...");const s=r;/\/$/.test(s.server)||(s.server+="/");const t={home:s.server,signal:s.server+"signaler",contact:s.server+"nous-contacter",api:s.server+"api",media:s.server+"api/image",macarte:s.server+"edition/carte",mestat:s.server+"edition/statistique",narration:s.server+"edition/narration",geocod:s.server+"edition/adresses",search:s.server+"atlas",createAccount:s.server+"creer-un-compte",initPassword:s.server+"recuperer-mon-mot-de-passe",unlockAccount:s.server+"debloquer-mon-compte",user:s.server+"mon-compte",profil:s.server+"mon-compte/#profil",mescartes:s.server+"mon-compte/#cartes",mesmedias:s.server+"mon-compte/#medias",mention:s.server+"mentions-legales",cgu:s.server+"cgu",cookie:s.server+"cookies-et-statistiques",doc:s.server+"aide",blog:s.server+"aide/blog",tuto:s.server+"aide/tutoriels",faq:s.server+"aide/faq",version:s.server+"aide/notes-de-version"};for(let e in t)s[e]&&(t[e]=s[e]);const o=t,i={__macarte__:o.macarte,__statistic__:o.mestat,__storymap__:o.narration,__mesadresses__:o.geocod,__atlas__:o.search,__myaccount__:o.user,__myprofile__:o.profil,__mymaps__:o.mescartes,__mymedias__:o.mesmedias,__about__:o.about,__tutos__:o.tuto,__doc__:o.doc,__version__:o.version,__blog__:o.blog};document.querySelectorAll(".main a").forEach((e=>{let r=e.getAttribute("href");i[r]&&e.setAttribute("href",i[r])}))})();