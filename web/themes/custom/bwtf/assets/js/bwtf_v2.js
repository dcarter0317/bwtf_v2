// SWIPER JS

const swiper = new Swiper(".mySwiper", {
  loop: true,
  centeredSlides: true,

  
  autoplay: {
    delay: 10000,
    disableOnInteraction: false,
  },

  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});

// MENU
const menuBtn = document.querySelector('.menu-btn');
const navList = document.querySelector('.nav');

menuBtn.addEventListener('click', openMenu);

function openMenu() {
  menuBtn.classList.toggle('open');
  navList.classList.toggle('nav-open');
}