import serviceURL from 'mcutils/api/serviceURL';

const translateUrls = {
    '__macarte__' : serviceURL.macarte,
    '__statistic__' : serviceURL.mestat,
    '__storymap__' : serviceURL.narration,
    '__mesadresses__' : serviceURL.geocod,
    '__atlas__' : serviceURL.search,
    '__myaccount__' : serviceURL.user,
    '__myprofile__' : serviceURL.profil,
    '__mymaps__' : serviceURL.mescartes,
    '__mymedias__' : serviceURL.mesmedias,
    '__about__' : serviceURL.about,
    '__tutos__' : serviceURL.tuto,
    '__doc__' : serviceURL.doc,
    '__version__' : serviceURL.version,
    '__blog__' : serviceURL.blog,
};

export default translateUrls;