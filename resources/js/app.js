import '../css/app.css';
import '../css/style.css';
import './bootstrap';

import AOS from 'aos';
import 'aos/dist/aos.css';
import VueScrollTo from 'vue-scrollto';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

/* === Integrasi Font Awesome === */
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// Impor ikon yang Anda butuhkan secara spesifik
import {
    faPlus, faPencilAlt, faTrash, faEye, faTachometerAlt, faCalendarAlt, faArrowLeft, faCheckCircle,
    faBars, faLink, faHeart, faCalendarCheck, faImages, faBookOpen,
    faHome
} from '@fortawesome/free-solid-svg-icons';

import {
    faFacebook, faInstagram, faTwitter
} from '@fortawesome/free-brands-svg-icons';
import InvitationLayout from './Layouts/InvitationLayout.vue';

// Tambahkan ikon ke library
library.add(faPlus, faPencilAlt, faTrash, faEye, faTachometerAlt, faCalendarAlt, faArrowLeft, faCheckCircle, faBars, faLink, faHeart, faCalendarCheck, faImages, faBookOpen, faHome,

faFacebook, faInstagram, faTwitter
);
/* ============================ */

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));

        page.then((module) => {
            // 2. Jika nama komponen dimulai dengan 'Invitation/',
            //    maka secara otomatis terapkan GuestLayout.
            if (name.startsWith('Invitation/')) {
                module.default.layout = InvitationLayout;
            }
        });

        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)

            app.use(VueScrollTo, {
                container: "body",
                duration: 1500, // Durasi scroll dalam milidetik. Angka lebih tinggi = lebih lambat.
                easing: "ease-in-out", // Jenis animasi
                offset: -60, // Jarak dari atas (berguna jika ada header fixed)
            });
            app.component('font-awesome-icon', FontAwesomeIcon); // Daftarkan komponen Font Awesome
            app.mount(el);
            AOS.init();

    },
    progress: {
        color: '#4B5563',
    },
});
