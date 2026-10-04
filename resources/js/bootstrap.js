import axios from 'axios';
import Alpine from 'alpinejs';
import AOS from 'aos';
import richTextEditor from './rich-text-editor';

window.Alpine = Alpine;
Alpine.data('richTextEditor', richTextEditor);
Alpine.start();

AOS.init({
    once: true,
    // disable: 'phone',
    duration: 500,
    easing: 'ease-out-cubic',
});

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
