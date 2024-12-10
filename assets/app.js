//
//
//
// /*
//  * Welcome to your app's main JavaScript file!
//  *
//  * This file will be included onto the page via the importmap() Twig function,
//  * which should already be in your base.html.twig.
//  */
// import './styles/app.css';
// import '@hotwired/turbo';
// import * as Turbo from '@hotwired/turbo';
//
// // Configurer Turbo
// Turbo.setProgressBarDelay(1);
//
// // On peut aussi utiliser start() pour s'assurer que Turbo est initialisé
// Turbo.start();
// import '@hotwired/stimulus';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');


/******************************************Menu burger - mobile********************************************/
document.addEventListener('DOMContentLoaded', function() {
    const menuButton = document.querySelector('#menu-toggle');
    if (menuButton) {
        menuButton.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu) {
                //console.log('mobileMenu:', mobileMenu);
                mobileMenu.classList.toggle('visible');
            }
        });
    }// console.log('menuButton:', menuButton);
});
/**********************************************************************************************************/


