import { getViewerURL, getEditorURL } from 'mcutils/api/serviceURL';
import swal from 'sweetalert2';
import ol_ext_element from 'ol-ext/util/element';

/* global map, Routing, publicName */

init();

/**
 * Initialise l'affichage de la page
 */
function init(){
    const viewElt = document.querySelector('[data-attr="view_url"]');
    ol_ext_element.create('A', {
        parent: viewElt,
        text: getViewerURL(map),
        href: getViewerURL(map),
        target: '_blank'
    });
    const editElt = document.querySelector('[data-attr="edit_url"]');
    ol_ext_element.create('A', {
        parent: editElt,
        text: getEditorURL(map),
        href: getEditorURL(map),
        target: '_blank'
    });
    const iframeElt = document.querySelector('#view-iframe');
    ol_ext_element.create('IFRAME', {
        parent: iframeElt,
        src: getViewerURL(map),
    });
}

/***************************/
/* suppression de la carte */
/***************************/
document.querySelector('#delete-map').addEventListener( 'click', () => {
    swal.fire({
        title: 'Etes-vous sûr de vouloir supprimer cette carte ?',
        showDenyButton: true,
        confirmButtonText: 'Supprimer',
        denyButtonText: "Abandonner",
        customClass: {
            confirmButton: 'btn btn-danger',
            denyButton: 'btn btn-info',
        },
        buttonsStyling: false,
        reverseButtons: true,
    }).then(function(result){
        if(result.isConfirmed){
            window.open(Routing.generate('admin_map_delete', { id : map.id }), "_self" );
        }
    });
})

/******************************/
/* Bloquer/Débloquer la carte */
/******************************/
document.querySelector('input[data-attr="valid"').addEventListener('change', (e) => {
    mapPut('valid', e.target.checked);
})


/*********************/
/* Modifier la carte */
/*********************/
document.querySelectorAll('button[data-attr]').forEach( button => {
    button.addEventListener( 'click', (e) => {
        const attr = button.dataset.attr;
        const options = {};
        options.attr = attr;

        let html = '';
        switch(attr){
            case "title":
                options.title = "Modifier le titre";
                options.inputValidator = true;
                break;
            case "description":
                options.title = "Modifier la description";
                options.inputType = 'textarea';
                break;
            case 'theme':
                options.title = 'Modifier le thème';
                html = "<select>";
                themes.forEach( theme => {
                    html += '<option value="'+theme.id +'">'+theme.name +'</option>';
                })
                html += "</select>";
                options.html = html;
                break;
            case 'img_url':
                options.title = "Modifier l'image";
                options.input = 'url';
                break;
            case 'share':
                options.title = "Modifier la publication";
                html = '<p> Sélectionner la publication</p>'
                    + '<div>'
                    + '<label><input type="radio" name="share" value="atlas" /> Atlas</label><br> '
                    + '<label><input type="radio" name="share" value="private" /> Privé</label>'
                    + '</div>'
                ;
                options.html = html;
                break;
            case 'new_id_edit':
                options.title = "Modifier l'identification de connexion";
                options.inputType = null;
                break;
            default:
                return;
        }

        displayEditDialog(options);
    })
})

function displayEditDialog(options){
    const attr = options.attr;
    const html = options.html || 'Entrez la valeur';
    const title = options.title;
    const inputType = options.inputType === null ? null : (options.inputType || 'text');
    const inputValidator = options.inputValidator || false;

    const swalOptions = {
        title: title,
        confirmButtonText: "Valider",
        showCancelButton  :true,
        cancelButtonText: "Annuler",
    };

    switch(attr){
        case 'theme':
        case 'share':
            swalOptions.html = html;
            break;
        default: 
            swalOptions.input = inputType;
            swalOptions.inputLabel = "Entrez la nouvelle valeur";
            if(inputValidator){
                swalOptions.inputValidator = (value) => {
                    if (!value) {
                        return 'Cette valeur ne peut pas être vide';
                    }
                }
            }
    }

    swal.fire(swalOptions).then((result) => {
        if(result.isConfirmed){
            let value;
            let select;
            switch(attr){
                case 'theme':
                    select = document.querySelector('.swal2-popup select');
                    value = select.value;
                    break;
                case 'share':
                    value = document.querySelector('.swal2-popup input:checked').value;
                    break;
                case 'new_id_edit':
                    value = true;
                    break;
                default:
                    value =  result.value;
            }

            mapPut(attr, value);
        }
    });
}

/***************************/
/* Envoyer la modification */
/***************************/
function mapPut(attr, value){
    const url = Routing.generate('admin_api_map_put', { id: map.edit_id , attribute: attr });

    const request = new XMLHttpRequest();
    request.open('PUT', url);
    request.onload = () => {
        try{
            const resp = JSON.parse(request.responseText);
            displayNewValue(resp);
        }catch(e){
            swal.fire({
                icon: 'error',
                title: 'Une erreur est survenue',
                html: e,
            });
        }
    };
    request.send(JSON.stringify({
        value: value
    }));
}

/**
 * @param {Object} response
 *      @param {string} response.attribute
 *      @param {string} response.value
 *      @param {int} response.id
 */
function displayNewValue(response){
    const attr = response.attribute;
    const value = response.value;

    if(attr == 'new_id_edit'){
        map['edit_id'] = value;
    }else{
        map[attr] = value;
    }

    let msgTitle = 'La valeur a été modifiée'; 
    let elt = document.querySelector('span[data-attr="'+attr+'"]');

    switch(response.attribute){
        case 'new_id_edit':
            elt = document.querySelector('span[data-attr="edit_url"]');
            elt.innerHTML = '';
            ol_ext_element.create('A', {
                parent: elt,
                text: getEditorURL(map),
                href: getEditorURL(map),
                target: '_blank'
            });
            msgTitle = "L'identifiant de modification a été modifié";
            break;
        case 'img_url':
            elt.innerHTML = '';
            ol_ext_element.create('IMG', {
                parent: elt,
                src: value
            });
            msgTitle = "L'image a été modifiée";
            break;
        case 'valid':
            document.querySelector('#alert-map-invalid').classList.toggle('hide');
            if(value){
                msgTitle = "La carte a été validée";
            }else{
                msgTitle = "La carte a été invalidée";
            }
            break;
        
        case 'title':
            $('header h1').innerHtml = value;
        default:
            elt.innerText = value;
    }

    const editorElt = document.querySelector('span[data-attr="editor"]');
    editorElt.innerHTML = '';
    ol_ext_element.create('A', {
        parent: editorElt,
        text: publicName,
        href: Routing.generate('admin_user_view', {id: map.creator_id}),
        src: value
    });
    const dateElt = document.querySelector('span[data-attr="updated_at"]');
    let date = new Date();
    dateElt.innerText = date.toISOString().split('T')[0];

    swal.fire({
        icon: 'success',
        title: msgTitle,
        timer: 2000,
    });
};

