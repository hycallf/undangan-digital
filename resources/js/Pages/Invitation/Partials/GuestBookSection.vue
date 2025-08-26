<script setup>
import { computed } from 'vue';

const props = defineProps({
    guests: Array,
});

// Filter dan sort guests untuk guestbook
const guestBookEntries = computed(() => {
    if (!props.guests || props.guests.length === 0) return [];

    return props.guests
        .filter(guest =>
            guest.confirmation_status !== 'pending' &&
            guest.message &&
            guest.message.trim().length > 0
        )
        .sort((a, b) => {
            // Sort by creation date descending (newest first)
            if (a.created_at && b.created_at) {
                return new Date(b.created_at) - new Date(a.created_at);
            }
            // If no dates, sort by name
            return a.name.localeCompare(b.name);
        });
});

const getConfirmationBadge = (status) => {
    switch(status) {
        case 'confirmed':
            return {
                class: 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200',
                text: '✅ Hadir',
                icon: '✅'
            };
        case 'declined':
            return {
                class: 'px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200',
                text: '🙏 Berhalangan',
                icon: '🙏'
            };
        default:
            return {
                class: 'px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200',
                text: '❓ Belum Konfirmasi',
                icon: '❓'
            };
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return '';
    }
};

const truncateMessage = (message, maxLength = 200) => {
    if (!message || message.length <= maxLength) return message;
    return message.substring(0, maxLength) + '...';
};
</script>

<template>
    <section id="guestbook" class="p-8 sm:p-12" style="background-color: var(--color-secondary);" data-aos="fade-up">
        <div class="text-center mb-8">
            <h2 class="font-heading text-4xl mt-2" style="color: var(--color-text-dark);">
                📖 Buku Tamu Digital
            </h2>
            <p class="mt-2 font-body text-md" style="color: var(--color-text-muted);">
                Ucapan dan doa dari sahabat dan kerabat tercinta
            </p>
            <div class="mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-white bg-opacity-50 text-gray-600">
                    💬 {{ guestBookEntries.length }} Ucapan
                </span>
            </div>
        </div>

        <!-- Guest Book Entries Container -->
        <div class="max-w-4xl mx-auto">
            <div v-if="guestBookEntries && guestBookEntries.length > 0"
                 class="h-96 overflow-y-auto space-y-4 p-4 bg-white bg-opacity-90 rounded-2xl shadow-lg border">

                <div
                    v-for="guest in guestBookEntries"
                    :key="guest.id"
                    class="group p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-200"
                >
                    <!-- Header: Name, Status, Date -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <!-- Avatar Circle -->
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                {{ guest.name.charAt(0).toUpperCase() }}
                            </div>

                            <!-- Name and Status -->
                            <div>
                                <p class="font-body font-bold text-base text-gray-900">
                                    {{ guest.name }}
                                </p>
                                <div class="flex items-center space-x-2">
                                    <span :class="getConfirmationBadge(guest.confirmation_status).class">
                                        {{ getConfirmationBadge(guest.confirmation_status).text }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="text-right">
                            <p class="text-xs text-gray-400">
                                {{ formatDate(guest.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="ml-13">
                        <div class="bg-gray-50 rounded-lg p-3 relative">
                            <!-- Speech bubble arrow -->
                            <div class="absolute left-0 top-3 transform -translate-x-1 w-0 h-0 border-t-4 border-b-4 border-r-4 border-transparent border-r-gray-50"></div>

                            <p class="font-body text-sm text-gray-700 leading-relaxed">
                                "{{ truncateMessage(guest.message) }}"
                            </p>

                            <!-- Show full message button if truncated -->
                            <button v-if="guest.message && guest.message.length > 200"
                                    class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 underline">
                                Baca selengkapnya
                            </button>
                        </div>
                    </div>

                    <!-- Additional Info (Phone if available) -->
                    <div v-if="guest.phone" class="ml-13 mt-2">
                        <p class="text-xs text-gray-500">
                            📱 {{ guest.phone }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center h-64 bg-white bg-opacity-50 rounded-2xl shadow-inner border-2 border-dashed border-gray-300">
                <div class="text-center">
                    <div class="mx-auto w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">Belum Ada Ucapan</h3>
                    <p class="text-gray-500 text-sm max-w-md">
                        Jadilah yang pertama memberikan ucapan dan doa terbaik untuk pengantin!
                    </p>
                    <div class="mt-4">
                        <a href="#rsvp" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-sm font-medium hover:from-blue-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-105">
                            ✍️ Tulis Ucapan Pertama
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="mt-8 max-w-2xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-white bg-opacity-70 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ guests?.filter(g => g.confirmation_status === 'confirmed').length || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">✅ Akan Hadir</div>
                </div>
                <div class="bg-white bg-opacity-70 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ guests?.filter(g => g.confirmation_status === 'declined').length || 0 }}
                    </div>
                    <div class="text-sm text-gray-600">🙏 Berhalangan</div>
                </div>
                <div class="bg-white bg-opacity-70 rounded-xl p-4 text-center col-span-2 md:col-span-1">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ guestBookEntries.length }}
                    </div>
                    <div class="text-sm text-gray-600">💬 Ucapan</div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Custom scrollbar untuk area guest book */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.5);
}

/* Hover effects */
.group:hover .transform {
    transform: translateY(-1px);
}

/* Animation untuk masuk */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group {
    animation: fadeInUp 0.5s ease-out;
}

/* Stagger animation untuk multiple items */
.group:nth-child(1) { animation-delay: 0.1s; }
.group:nth-child(2) { animation-delay: 0.2s; }
.group:nth-child(3) { animation-delay: 0.3s; }
.group:nth-child(4) { animation-delay: 0.4s; }
.group:nth-child(5) { animation-delay: 0.5s; }
</style>
