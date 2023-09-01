import './app'
import api from  'mcutils/api/api';
import ol_ext_element from 'ol-ext/util/element';
import serviceURL from 'mcutils/api/serviceURL';

document.querySelectorAll(".navbar-nav--portails .public-name a").forEach(elt => elt.innerHTML = '');

/******************************/
/* met a jour le menu  gauche */
/******************************/

const userDivs = document.querySelectorAll('.navbar-nav--portails');

function connect (compteur){
    compteur = compteur || 0;
    $.ajax({
        url: Routing.generate("app_session_token", [], true).replace(/^http:/, 'https:'),
        success: function(response){
            localStorage.setItem('MC@token',response.token);
            localStorage.setItem('MC@refreshToken',response.refresh_token);
        },
        complete: function(xhr, ajaxOptions, thrownError){
            api.whoami(me => {
                userDivs.forEach(div => {
                    const userMenu = ol_ext_element.create('UL', {
                        parent: div,
                        className: 'user-menu hide',
                    });
                    if (me.error) {
                        document.body.dataset.connected = "disconnected";
                        setDisconnectedMenu(userMenu);
                        if(compteur < 3 ){
                            setTimeout(() => { 
                                    connect(compteur + 1)
                            }, 500);
                        }
                    } else {
                        document.body.dataset.connected = "connected";
                        setConnectedMenu(userMenu, me);
                        div.querySelector('[data-attr="public-name"]').innerHTML = me.public_name;
                    }
                })
            }, true)
        }
    });
}
connect();


document.querySelectorAll('.disconnected, .connected').forEach(div => {
    div.querySelector('a').href = "javascript: void(0)";
    div.addEventListener('click', evt => {
        const isHidden = document.querySelector('.user-menu').classList.contains('hide');
        if (isHidden) {
            document.querySelectorAll('.user-menu').forEach( elt => {
                elt.classList.remove("hide");
            });
            const hidemenu = function(evt) {
                document.body.removeEventListener('click',  hidemenu)
                document.querySelectorAll('.user-menu').forEach( elt => {
                    elt.classList.add("hide");
                });
            }
            const listener = document.body.addEventListener('click',  hidemenu)
            evt.stopPropagation()
        }
    })
})



function setDisconnectedMenu(parent){
    const connectLi = ol_ext_element.create('LI', {
        parent: parent,
        className: "toto",
    });

    ol_ext_element.create('A', {
        parent: connectLi,
        href: Routing.generate('app_login'),
        html: "Me connecter"
    })

}
function setConnectedMenu(parent, user){
    const usernameLi = ol_ext_element.create('LI', {
        parent: parent,
        className: 'summary'
    });
    ol_ext_element.create('DIV', {
        parent: usernameLi,
        html: '<b>'+user.public_name+'</b>',
    });
    ol_ext_element.create('DIV', {
        parent: usernameLi,
        html: user.email,
    });

    ol_ext_element.create('LI', {
        parent: parent,
        className: 'separator',
    });

    const moncompteLi = ol_ext_element.create('LI', {
        parent: parent,
    });
    ol_ext_element.create('A', {
        parent: moncompteLi,
        html: 'Mon compte',
        href: serviceURL.user
    });

    const persoLi = ol_ext_element.create('LI', {
        parent: parent,
    });
    ol_ext_element.create('A', {
        parent: persoLi,
        html: 'Mes données personnelles',
        href: serviceURL.profil
    });

    const mescartesLi = ol_ext_element.create('LI', {
        parent: parent,
    });
    ol_ext_element.create('A', {
        parent: mescartesLi,
        html: 'Mes cartes',
        href: serviceURL.mescartes
    });

    const mesimagesLi = ol_ext_element.create('LI', {
        parent: parent,
    });
    ol_ext_element.create('A', {
        parent: mesimagesLi,
        html: 'Mes images',
        href: serviceURL.mesmedias
    });

    ol_ext_element.create('LI', {
        parent: parent,
        className: 'separator',
    });

    const logoutLi = ol_ext_element.create('LI', {
        parent: parent,
    });
    ol_ext_element.create('A', {
        parent: logoutLi,
        html: 'Me déconnecter',
        href: Routing.generate('app_logout')
    });

}
