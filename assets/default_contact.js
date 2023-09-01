import translateUrls from './translateUrls';

document.querySelectorAll('.main a').forEach( (a) => {
    let href = a.getAttribute('href');
    if(translateUrls[href]){
        a.setAttribute('href', translateUrls[href]);
    }
})