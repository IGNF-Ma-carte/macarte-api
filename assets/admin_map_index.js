import Routing from './js/FOSsRouting';
import listCarte from 'mcutils/api/ListCarte'
import API from 'mcutils/api/MacarteApi'
import options from 'mcutils/config/config'
import 'font-ign/public/font-ign.css'
import loadFonts from 'mcutils/font/loadFonts'
loadFonts();

const api = new API(options.server);

const list = new listCarte(api, {
    context: 'atlas',
    search: true,
    //selection: true,
    target: document.getElementById('result')
});

if(window.user){
    list.removeFilter('user');
    list.setFilter('user', window.user)
}

//au click sur une carte, on affiche les détails dans un nouvel onglet
list.on('click', (e) => {
    let server = options.server.replace('/admin/api', '');
    if(server.slice(-1) == '/'){
        server = server.slice(0, -1)
    }
    let url = server + Routing.generate('admin_map_view', { idView: e.item.view_id });
    window.open(url, "_blank");
});

list.search();

