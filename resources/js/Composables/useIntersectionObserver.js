// resources/js/Composables/useIntersectionObserver.js
import { ref } from 'vue';

export function useIntersectionObserver() {
    // State untuk menyimpan ID section yang sedang aktif
    const activeSection = ref('');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                // Jika section masuk ke dalam viewport (terlihat di layar)
                if (entry.isIntersecting) {
                    // Update state dengan ID section tersebut
                    activeSection.value = entry.target.id;
                }
            });
        },
        {
            // Opsi: Section dianggap "terlihat" jika 50% bagian tengahnya masuk layar
            rootMargin: '-50% 0px -50% 0px',
            threshold: 0
        }
    );

    // Fungsi untuk mulai mengamati semua section yang memiliki ID
    const observeSections = () => {
        document.querySelectorAll('section[id]').forEach((section) => {
            observer.observe(section);
        });
    };

    return {
        activeSection,
        observeSections,
    };
}
