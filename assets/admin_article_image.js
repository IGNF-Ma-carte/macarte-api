import serviceURL from 'mcutils/api/serviceURL';
import API from 'mcutils/api/MacarteApi';
import ListMedias from 'mcutils/api/ListMedias';
import { addMediaDialog, updateMediaDialog } from 'mcutils/dialog/openMedia';
import dialogMessage from 'mcutils/dialog/dialogMessage';
import ol_ext_element from 'ol-ext/util/element';
import 'font-ign/public/font-ign.css';
import loadFonts from 'mcutils/font/loadFonts';
import './scss/admin_article_image.scss';
import dialog from 'mcutils/dialog/dialog';
loadFonts();

API.prototype.refreshToken =  function(callback) {
    callback(true)
};

API.prototype.isConnected =  function() {
    return true;
};

const api = new API(serviceURL.api);

const list = new ListMedias(api, {
    selection: true,
    search: true,
    check: true,
    limit: true,
    target: document.querySelector('.list-medias')
});

// list.search();

list.on('draw:item', (e) => {
    const opt = ol_ext_element.create('DIV', {
        parent: e.element,
        className: 'li-actions',
    });
    ol_ext_element.create('SPAN', {
        html: 'Modifier',
        click: () => {
            updateMediaDialog({
                media: e.item,
                folders: list.get('folders'),
                callback: () => {
                    list.setFolder(e.item.folder);
                    list.updateFolders();
                    list.search();
                }
            })
        },
        parent: opt,
        title : 'Modifier le média',
    });
});

// List header element
const listHeader = list.getHeaderElement();

// Disable boutons si pas d'images sélectionnées
list.on(['check', 'draw:list'], () => {
    const btn = listHeader.querySelectorAll('button.select')
    if (list.getChecked().length) {
      btn.forEach(b => b.classList.remove('button-disabled'))
    } else {
      btn.forEach(b => b.classList.add('button-disabled'))
    }
});

// Bouton et action "ajouter un media"
ol_ext_element.create('BUTTON', {
    className: 'button button-ghost',
    html: '<i class="fa fa-plus-circle fa-fw"></i> Ajouter un média',
    click: () => {
        addMediaDialog({
            callback: (e) => {
                list.updateFolders();
                if(list.get('folder') === e.item.folder){
                    list.showPage();
                }else{
                    list.setFolder(e.item.folder);
                }
            }
        }, list.get('folders'))
    },
    parent: listHeader
});

// Bouton et action "modifier le dossier des médias sélectionnés"
ol_ext_element.create('BUTTON', {
    className: 'button button-ghost select',
    html: '<i class="fa fa-folder fa-fw"></i> Changer de galerie...',
    click: () => {
        const sel = list.getChecked();
        if (!sel || !sel.length) {
            dialogMessage.showMessage('Sélectionnez des images à changer de dossier...')
            return;
        }
        list.getFolderDialog({
            prompt: 'Ecrire le nom de la galerie ou sélectionner dans la liste :'
        }, (folder) => {
            // Update media recursively
            const updateMedia = (e) => {
            if (e && e.error) {
                dialogMessage.showAlert('Une erreur est survenue !<br/>Impossible de changer de dossier...')
                list.updateFolders();
                list.showPage();
                return;
            }
            // Next selection
            const s = sel.pop()
            if (s) {
                if (s.folder !== folder) api.updateMediaFolder(s.id, folder, updateMedia);
                else updateMedia();
            } else {
                list.updateFolders();
                list.showPage();
            }
            }
            updateMedia();
        });
    },
    parent: listHeader
})

// Bouton supprimer
ol_ext_element.create('BUTTON', {
    className: 'button button-accent select',
    html: '<i class="fa fa-trash fa-fw"></i> Supprimer...',
    click: () => {
        const sel = list.getChecked();
        const max = sel.length;
        
        if (!sel || !sel.length) {
            dialogMessage.showMessage('Sélectionnez des images à supprimer...')
            return;
        }
        // Delete media recursively
        const deleteMedia = (e) => {
            if (e && e.error) {
                dialogMessage.showAlert('Une erreur est survenue !<br/>Impossible de supprimer une image...');
                list.showPage(list.get('currentPage'));
                return;
            }
            // Next selection
            const s = sel.pop()
            dialog.show('Suppression en cours...');
            dialog.setProgress(max - sel.length, max);
            if (s) {
                api.deleteMedia(s.id, deleteMedia);
            } else {
                list.updateFolders((folders) => {
                    dialog.hide();
                    if(list.get('folder') && folders.indexOf(list.get('folder')) < 0){
                        list.setFolder()
                    }else{
                        list.showPage(list.get('currentPage'));
                    }
                }); 
            }
        }
        // Ask for delete
        dialogMessage.showAlert(
            'Êtes-vous sûr de vouloir supprimer <b>' + sel.length + '</b> image' + (sel.length > 1 ? 's ?':' ?')
            + '<br/>Une fois supprimées, les images ne s\'afficheront plus sur les cartes.'
            + '<br/><b class="accent">Cette action est irréversible.</b>',
            { ok: 'supprimer', cancel: 'annuler'},
            (b) => {
                if (b==='ok') {
                    deleteMedia();
                }
                dialogMessage.close();
            } 
        )
        // Color button
        dialogMessage.element.querySelector('.ol-buttons input').className = 'button button-accent';
    },
    parent: listHeader
});
