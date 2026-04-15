// RESPONSIVE NAVMENU
// Using var instead of const to avoid "already declared" conflict
// with landlord-header.php which also declares navMenu
var navMenu  = document.querySelector("#navmenu");
var navLinks = document.querySelector(".nav-links");

// Null check — navMenu may not exist on every page
if (navMenu) {
    navMenu.onclick = () => {
        navMenu.classList.toggle("fa-xmark");
        navLinks.classList.toggle("open");
    }

    window.onscroll = () => {
        navMenu.classList.remove("fa-xmark");
        navLinks.classList.remove("open");
    }
}

// SCROLL REVEAL
ScrollReveal().reveal('header, .home, #testimonials', {
    origin: 'top',
    distance: '20px',
    duration: 1500,
    delay: 100,
    reset: true
});

ScrollReveal().reveal('.info-animation', {
    origin: 'left',
    distance: '20px',
    duration: 1000,
    interval: 200,
    reset: true
});

ScrollReveal().reveal('.cards, .button-animation', {
    origin: 'right',
    distance: '10px',
    duration: 1000,
    interval: 200,
    delay: 200,
    reset: true
});

ScrollReveal().reveal('#map-section, footer', {
    origin: 'bottom',
    distance: '5px',
    duration: 1000,
    delay: 100,
    reset: true
});