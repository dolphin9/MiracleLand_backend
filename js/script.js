
document.addEventListener('DOMContentLoaded', function() {
    // 导航栏交互效果
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('header u1');

    navbarToggler.addEventListener('click', function() {
        navbarCollapse.classList.toggle('show');
    });
});
