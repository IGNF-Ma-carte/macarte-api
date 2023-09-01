import md2html from 'mcutils/md/md2html';
import 'mcutils/font/loadFonts';
import 'font-ign/public/font-ign.css'
import Swal from "sweetalert2";

import "./scss/app.scss";
import translateUrls from './translateUrls';

window.swal = Swal;

// document.body.classList.add('wysiwyg');

/******************************/
/* met a jour le menu  gauche */
/******************************/

document.querySelectorAll('a'
    // '.navbar-nav--left a,'+ 
    // ' .header-principal__lang a,'+
    // ' .footer-contentinfo a'
).forEach( (a) => {
    let href = a.getAttribute('href');
    if(translateUrls[href]){
        a.setAttribute('href', translateUrls[href]);
    }
})


/*******************************************/
/* transforme les textes markdowns en html */
/*******************************************/
document.querySelectorAll("[data-mdarticle]").forEach( (div) => {
    if(window.articles[div.dataset.mdarticle]){
        const text = window.articles[div.dataset.mdarticle].replace(/\r\n/g,'\n');
        md2html.element(text, div);
    }
})

