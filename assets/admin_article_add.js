import serviceURL from 'mcutils/api/serviceURL';
import API from 'mcutils/api/MacarteApi'
import MDEditor from 'mcutils/md/MDEditor';
import 'font-ign/public/font-ign.css';
import './scss/admin_article_add.scss';
import InputMedia from 'mcutils/control/InputMedia';
import './scss/advantage_category.scss'

/********************************************/
/* connexion via symfony PHP, pas via l'API */
/********************************************/
API.prototype.refreshToken =  function(callback) {
    callback(true)
};

API.prototype.isConnected =  function() {
    return true;
};

new API(serviceURL.api);


/*****************************************/
/* ajouter des images de la bibliothèque */
/*****************************************/
const imedia = new InputMedia({ 
    thumb: false,
    add: true,
    input: document.querySelector('input#imgUrl')
});
imedia.set('fullpath', true);

/**********************/
/* AFFICHAGE MARKDOWN */
/**********************/
const editor = new MDEditor({
    input: document.querySelector('textarea'),
    data: document.querySelector('textarea').value,
    output: document.querySelector('.md-view')
})
editor.setDialogClassName('md category-advantage');


/********************/
/* GESTION DES TAGS */
/********************/
// documentation : https://symfony.com/doc/current/form/form_collections.html#form-collections-remove

document.querySelectorAll('.remove-existing-tag').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('li').remove();
    });
});

document.querySelectorAll('ul.tag input').forEach((input) => {
    input.removeAttribute('required');
});

const addFormToCollection = (e) => {
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);

    const item = document.createElement('li');
    item.innerHTML = collectionHolder
        .dataset
        .prototype
        .replace(
            /__name__/g,
            collectionHolder.dataset.index
        );
    item.querySelector('input').removeAttribute('required');

    collectionHolder.appendChild(item);
    collectionHolder.append('\n');

    collectionHolder.dataset.index++;

    // add a delete link to the new form
    addTagFormDeleteLink(item);
};

const addTagFormDeleteLink = (item) => {
    const removeFormDiv = document.createElement('DIV');
    removeFormDiv.classList = 'remove-tag';

    const removeFormButton = document.createElement('BUTTON');
    removeFormButton.innerHTML = '<i class="fa fa-minus" aria-hidden="true"></i>';
    removeFormButton.classList = 'btn btn-sm btn-danger';
    removeFormButton.setAttribute('title', 'Enlever ce tag');

    removeFormDiv.append(removeFormButton);

    item.append(removeFormDiv);

    removeFormButton.addEventListener('click', (e) => {
        e.preventDefault();
        // remove the li for the tag form
        item.remove();
    });
}

document
    .querySelectorAll('.add_item_link')
    .forEach(btn => {
        btn.addEventListener("click", addFormToCollection)
    })
;
