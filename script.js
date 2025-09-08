// RESPONSIVE NAVMENU
const navMenu = document.querySelector("#navmenu");
const navLinks = document.querySelector(".nav-links");

navMenu.onclick = () => {
    navMenu.classList.toggle("fa-xmark");
    navLinks.classList.toggle("open");
}

window.onscroll = () => {
    navMenu.classList.remove("fa-xmark");
    navLinks.classList.remove("open");
}
// SCROLL REVEAL
    ScrollReveal().reveal('header, .home', {
        origin: 'top',
        distance: '40px',
        duration: 1500,
        delay: 100,
        reset: true
    });

    ScrollReveal().reveal('.info', {
        origin: 'left',
        distance: '50px',
        duration: 1000,
        interval: 200,
        reset: true
    });


    ScrollReveal().reveal('.cards, .listing-btn', {
        origin: 'right',
        distance: '50px',
        duration: 1000,
        interval: 200,
        delay: 200,
        reset: true
    });

    ScrollReveal().reveal('footer', {
        origin: 'bottom',
        distance: '40px',
        duration: 1500,
        delay: 100,
        reset: true
    });