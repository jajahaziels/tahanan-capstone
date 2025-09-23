// Responsive Toggle Form
const container = document.querySelector('.form-container');
const tenantBtn = document.querySelector('.tenant-btn');
const landlordBtn = document.querySelector('.landlord-btn');

tenantBtn.addEventListener('click',()=>{
    container.classList.add('active')
})

landlordBtn.addEventListener('click',()=>{
    container.classList.remove('active')
})