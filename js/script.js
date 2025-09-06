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