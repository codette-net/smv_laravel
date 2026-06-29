import axios from 'axios';
import Alpine from 'alpinejs';
import AOS from 'aos';

window.Alpine = Alpine;
Alpine.start();

AOS.init({
    once: true,
    // disable: 'phone',
    duration: 500,
    easing: 'ease-out-cubic',
});

console.log(AOS)

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
