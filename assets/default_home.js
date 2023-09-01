import './scss/components/teaser.scss';
import './scss/advantage_category.scss'
import './scss/default_home.scss'
import carousel from 'mcutils/charte/carousel'

document.querySelectorAll('.category-advantage input').forEach( (input) => {
    input.remove();
})

// mise en orange du bouton sur le titre
// const titleLink = document.querySelector('header.o-page-title a');
// titleLink.classList.remove('btn--primary');
// titleLink.classList.add('btn--accent');

carousel();