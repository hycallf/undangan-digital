<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch, nextTick } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import CKEditor from '@/Components/CKEditor.vue';
import Swal from 'sweetalert2';

import { faArrowLeft, faQrcode, faFileImport, faFileExport, faSearch, faTimes, faCaretDown, faRetweet, faQuestionCircle, faPlus, faDownload, faUpload, faInfoCircle, faTrash, faUsers, faPaperPlane, faCheckCircle, faTimesCircle } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    event: Object,
    guests: Array,
});

// Get flash messages dari backend
const page = usePage();

// Hitung statistik menggunakan computed property
const totalRSVP = computed(() => props.guests.length);
const totalHadir = computed(() => props.guests.filter(g => g.attendance_status === 'present').length);
const totalBelumHadir = computed(() => totalRSVP.value - totalHadir.value);

// State untuk modal import
const confirmingImport = ref(false);
const showingHelp = ref(false);
const confirmingBulkDelete = ref(false);
const confirmingSendInvitations = ref(false);
const sendingInvitations = ref(false);
const showingMessageEditor = ref(false);
const loadingTemplate = ref(false);

// CKEditor instance
const editorInstance = ref(null);
const customMessage = ref('');
const messageTemplate = ref('');
const placeholders = ref({});

const importForm = useForm({
    file: null,
    mode: 'add',
});

const invitationForm = useForm({
    guest_ids: [],
    custom_message: '',
});

// State untuk bulk actions
const selectedGuests = ref(new Set());
const selectAll = ref(false);

// Watch untuk select all
watch(selectAll, (value) => {
    if (value) {
        selectedGuests.value = new Set(props.guests.map(guest => guest.id));
    } else {
        selectedGuests.value.clear();
    }
});

// Watch untuk individual selections
watch(() => selectedGuests.value.size, (size) => {
    selectAll.value = size === props.guests.length && size > 0;
});

const toggleGuestSelection = (guestId) => {
    if (selectedGuests.value.has(guestId)) {
        selectedGuests.value.delete(guestId);
    } else {
        selectedGuests.value.add(guestId);
    }
    selectedGuests.value = new Set(selectedGuests.value);
};

onMounted(async () => {
    // Load CKEditor script
    if (!window.ClassicEditor) {
        const script = document.createElement('script');
        script.src = 'https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js';
        script.onload = () => {
            console.log('CKEditor loaded');
        };
        document.head.appendChild(script);
    }
});

const initCKEditor = async () => {
    if (!window.ClassicEditor) return;

    await nextTick();

    const editorElement = document.querySelector('#message-editor');
    if (editorElement && !editorInstance.value) {
        try {
            const editor = await window.ClassicEditor.create(editorElement, {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'link', '|',
                    'undo', 'redo'
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }
                    ]
                }
            });

            editorInstance.value = editor;

            // Set initial content
            if (customMessage.value) {
                editor.setData(customMessage.value);
            }

            // Listen for changes
            editor.model.document.on('change:data', () => {
                customMessage.value = editor.getData();
            });

        } catch (error) {
            console.error('Error initializing CKEditor:', error);
        }
    }
};

const destroyCKEditor = () => {
    if (editorInstance.value) {
        editorInstance.value.destroy()
            .then(() => {
                editorInstance.value = null;
            })
            .catch(error => {
                console.error('Error destroying CKEditor:', error);
            });
    }
};

// Load message template from backend
const loadMessageTemplate = async () => {
    loadingTemplate.value = true;
    try {
        const response = await fetch(route('admin.events.guests.get-message-template', props.event.id));
        const data = await response.json();

        if (data.success) {
            messageTemplate.value = data.template;
            placeholders.value = data.placeholders;
            customMessage.value = data.template;

            // Update CKEditor content if initialized
            if (editorInstance.value) {
                editorInstance.value.setData(data.template);
            }
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading template:', error);
        Swal.fire('Error', 'Gagal memuat template pesan', 'error');
    } finally {
        loadingTemplate.value = false;
    }
};

// Reset to template
const resetToTemplate = () => {
    if (messageTemplate.value) {
        customMessage.value = messageTemplate.value;
        if (editorInstance.value) {
            editorInstance.value.setData(messageTemplate.value);
        }
    }
};

// Insert placeholder
const insertPlaceholder = (placeholder) => {
    if (editorInstance.value) {
        const viewFragment = editorInstance.value.data.processor.toView(placeholder);
        const modelFragment = editorInstance.value.data.toModel(viewFragment);
        editorInstance.value.model.insertContent(modelFragment);
    }
};


const showImportAlert = (flash) => {
    if (flash.success) {
        Swal.fire({
            icon: 'success',
            title: 'Import Berhasil!',
            html: `
                <div class="text-left">
                    <p class="mb-2">✅ <strong>${flash.imported_count}</strong> kontak berhasil diimport</p>
                    <p class="mb-2">📊 Total tamu sekarang: <strong>${flash.total_guests}</strong></p>
                    <p class="text-sm text-gray-600">Mode: ${flash.mode === 'add' ? 'Tambah ke daftar' : 'Ganti semua data'}</p>
                </div>
            `,
            confirmButtonText: 'OK',
            confirmButtonColor: '#10B981',
            timer: 5000,
            timerProgressBar: true
        });
    } else if (flash.warning) {
        Swal.fire({
            icon: 'warning',
            title: 'Import Selesai',
            html: `
                <div class="text-left">
                    <p class="mb-2">⚠️ Tidak ada data tamu yang valid ditemukan</p>
                    <p class="text-sm text-gray-600">Pastikan format file sudah benar dan berisi data nama serta nomor telepon.</p>
                </div>
            `,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#F59E0B'
        });
    } else if (flash.error) {
        let details = '';
        if (flash.details && flash.details.length > 0) {
            details = '<ul class="text-left text-sm mt-2">';
            flash.details.forEach(detail => {
                details += `<li>• ${detail}</li>`;
            });
            details += '</ul>';
        }

        Swal.fire({
            icon: 'error',
            title: 'Import Gagal',
            html: `
                <div class="text-left">
                    <p class="mb-2">❌ ${flash.message}</p>
                    ${details}
                </div>
            `,
            confirmButtonText: 'OK',
            confirmButtonColor: '#EF4444'
        });
    }
};

// Watch untuk flash messages dan tampilkan SweetAlert
watch(() => page.props.flash, (flash) => {
    if (flash) {
        showImportAlert(flash);
    }
}, { immediate: true });



const openImportModal = () => {
    importForm.reset();
    confirmingImport.value = true;
};

const openHelpModal = () => {
    showingHelp.value = true;
};

const openMessageEditor = async () => {
    if (selectedGuests.value.size === 0) {
        Swal.fire('Perhatian!', 'Pilih tamu yang ingin dikirim undangan terlebih dahulu.', 'warning');
        return;
    }

    showingMessageEditor.value = true;
    await nextTick();
    await loadMessageTemplate();
    await initCKEditor();
};

const closeMessageEditor = () => {
    showingMessageEditor.value = false;
    destroyCKEditor();
    customMessage.value = '';
    messageTemplate.value = '';
    placeholders.value = {};
};

const importGuests = () => {
    importForm.post(route('admin.events.guests.import', props.event.id), {
        onSuccess: () => {
            confirmingImport.value = false;
            importForm.reset();
        },
        onError: (errors) => {
            console.error('Import errors:', errors);
        }
    });
};

const handleExport = (type) => {
    const exportUrl = route('admin.events.guests.export', {
        event: props.event.id,
        type: type
    });
    window.location.href = exportUrl;
};

// Fungsi untuk menghapus tamu individual
const deleteGuest = (guest) => {
    Swal.fire({
        title: 'Hapus Tamu?',
        html: `Apakah Anda yakin ingin menghapus <strong>${guest.name}</strong> dari daftar tamu?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.events.guests.destroy', [props.event.id, guest.id]), {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Tamu berhasil dihapus.', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Gagal menghapus tamu.', 'error');
                }
            });
        }
    });
};

// Fungsi untuk bulk delete
const confirmBulkDelete = () => {
    if (selectedGuests.value.size === 0) {
        Swal.fire('Perhatian!', 'Pilih tamu yang ingin dihapus terlebih dahulu.', 'warning');
        return;
    }
    confirmingBulkDelete.value = true;
};

const executeBulkDelete = () => {
    const guestIds = Array.from(selectedGuests.value);

    router.post(route('admin.events.guests.bulk-delete', props.event.id), {
        guest_ids: guestIds
    }, {
        onSuccess: () => {
            confirmingBulkDelete.value = false;
            selectedGuests.value.clear();
            selectAll.value = false;
            Swal.fire('Berhasil!', `${guestIds.length} tamu berhasil dihapus.`, 'success');
        },
        onError: () => {
            Swal.fire('Error!', 'Gagal menghapus tamu.', 'error');
        }
    });
};

const sendInvitationsWithCustomMessage = () => {
    const guestIds = Array.from(selectedGuests.value);
    sendingInvitations.value = true;

    // Set form data
    invitationForm.guest_ids = guestIds;
    invitationForm.custom_message = customMessage.value;

    invitationForm.post(route('admin.events.guests.send-invitations', props.event.id), {
        onSuccess: (response) => {
            showingMessageEditor.value = false;
            sendingInvitations.value = false;
            destroyCKEditor();
            selectedGuests.value.clear();
            selectAll.value = false;

            if (response.props.flash?.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Undangan Terkirim!',
                    html: `
                        <div class="text-left">
                            <p class="mb-2">✅ <strong>${response.props.flash.sent_count}</strong> undangan berhasil dikirim</p>
                            <p class="mb-2">📱 Total tamu yang dihubungi: <strong>${guestIds.length}</strong></p>
                            ${response.props.flash.failed_count > 0 ? `<p class="text-red-600">❌ ${response.props.flash.failed_count} gagal dikirim</p>` : ''}
                        </div>
                    `,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10B981'
                });
            }
        },
        onError: () => {
            sendingInvitations.value = false;
            Swal.fire('Error!', 'Gagal mengirim undangan.', 'error');
        }
    });
};

// Preview message function
const previewMessage = async () => {
    if (selectedGuests.value.size === 0) {
        Swal.fire('Perhatian!', 'Pilih minimal satu tamu untuk preview.', 'warning');
        return;
    }

    const firstGuestId = Array.from(selectedGuests.value)[0];

    try {
        const response = await fetch(route('admin.events.guests.preview-message', props.event.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                guest_id: firstGuestId,
                custom_message: customMessage.value
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                title: `Preview Pesan untuk ${data.guest_name}`,
                html: `
                    <div class="text-left">
                        <div class="bg-green-50 p-4 rounded-lg mb-4">
                            <p class="text-sm"><strong>Nomor:</strong> ${data.guest_phone}</p>
                            <p class="text-sm"><strong>Status:</strong> ${data.phone_valid ? '✅ Valid' : '❌ Tidak Valid'}</p>
                        </div>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">Pesan yang akan dikirim:</h4>
                            <div class="text-sm whitespace-pre-wrap text-left">${data.message}</div>
                        </div>
                    </div>
                `,
                width: '600px',
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    } catch (error) {
        console.error('Preview error:', error);
        Swal.fire('Error', 'Gagal memuat preview pesan', 'error');
    }
};

const headers = [
    { text: "", value: "checkbox", sortable: false, width: 50 },
    { text: "Nama Tamu", value: "name", sortable: true },
    { text: "No. Telepon", value: "phone", sortable: true },
    { text: "Status Konfirmasi", value: "confirmation_status", sortable: true },
    { text: "Status Kehadiran", value: "attendance_status" },
    { text: "Waktu Check-in", value: "check_in_time", sortable: true },
    { text: "Foto", value: "photo_path" },
    { text: "Aksi", value: "actions", sortable: false },
];
const searchValue = ref('');

// Computed untuk filtered guests
const filteredGuests = computed(() => {
    if (!searchValue.value) return props.guests;

    return props.guests.filter(guest =>
        guest.name.toLowerCase().includes(searchValue.value.toLowerCase()) ||
        guest.phone?.toLowerCase().includes(searchValue.value.toLowerCase())
    );
});
</script>

<template>
    <Head :title="'Tamu ' + event.groom?.nickname + ' & ' + event.bride?.nickname" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-bold text-2xl text-gray-900">Daftar Tamu</h1>
                    <p class="text-gray-600 mt-1">{{ event.groom?.nickname }} & {{ event.bride?.nickname }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="text-sm text-gray-500">
                        Total: <span class="font-semibold text-gray-900">{{ totalRSVP }}</span> tamu
                    </div>
                </div>
            </div>
        </template>

        <!-- Enhanced Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-blue-100 uppercase tracking-wider">Total RSVP</h3>
                        <p class="text-3xl font-bold mt-2">{{ totalRSVP }}</p>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-emerald-100 uppercase tracking-wider">Sudah Hadir</h3>
                        <p class="text-3xl font-bold mt-2">{{ totalHadir }}</p>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 w-full bg-white bg-opacity-20 rounded-full h-2">
                    <div class="bg-white h-2 rounded-full transition-all duration-500" :style="`width: ${totalRSVP > 0 ? (totalHadir / totalRSVP) * 100 : 0}%`"></div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-orange-500 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-amber-100 uppercase tracking-wider">Belum Hadir</h3>
                        <p class="text-3xl font-bold mt-2">{{ totalBelumHadir }}</p>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-purple-100 uppercase tracking-wider">Terpilih</h3>
                        <p class="text-3xl font-bold mt-2">{{ selectedGuests.size }}</p>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m5 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m-5 4v6m5-6v6m-5 0H9m5 0h2"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mb-6">
            <Link :href="route('admin.events.index')" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200">
                <font-awesome-icon :icon="faArrowLeft" class="mr-2" />
                Kembali ke Daftar Event
            </Link>
        </div>

        <!-- Main Content -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
            <!-- Enhanced Action Buttons -->
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Primary Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Check-in Button -->
                        <Link :href="route('checkin.scanner', event.id)" as="button" class="btn-check-in">
                            <font-awesome-icon :icon="faQrcode" class="mr-2" />
                            <span>Scan QR Check-in</span>
                        </Link>

                        <!-- Send Invitations Button -->
                        <button @click="openMessageEditor"
                                :disabled="selectedGuests.size === 0"
                                :class="selectedGuests.size === 0 ? 'btn-send-disabled' : 'btn-send'">
                            <font-awesome-icon :icon="faPaperPlane" class="mr-2" />
                            <span>Kirim Undangan ({{ selectedGuests.size }})</span>
                        </button>
                    </div>

                    <!-- Import/Export Actions -->
                    <div class="flex items-center gap-1">
                        <Dropdown align="right" width="56">
                            <template #trigger>
                                <button class="btn-import">
                                    <font-awesome-icon :icon="faFileImport" class="mr-2" />
                                    <span>Import Tamu</span>
                                    <font-awesome-icon :icon="faCaretDown" class="ml-2" />
                                </button>
                            </template>
                            <template #content>
                                <div class="py-1">
                                    <button @click="openImportModal" class="dropdown-item">
                                        <font-awesome-icon :icon="faUpload" class="mr-3 text-gray-400" />
                                        Import dari File Excel/CSV
                                    </button>
                                    <button @click="handleExport('template')" class="dropdown-item">
                                        <font-awesome-icon :icon="faDownload" class="mr-3 text-gray-400" />
                                        Download Template Excel
                                    </button>
                                </div>
                            </template>
                        </Dropdown>

                        <!-- Help Button -->
                        <button @click="openHelpModal" class="btn-help" title="Bantuan Import Kontak">
                            <font-awesome-icon :icon="faQuestionCircle" />
                        </button>
                    </div>

                    <!-- Export Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="btn-export">
                                <font-awesome-icon :icon="faFileExport" class="mr-2" />
                                <span>Export Data</span>
                                <font-awesome-icon :icon="faCaretDown" class="ml-2" />
                            </button>
                        </template>
                        <template #content>
                            <div class="py-1">
                                <button @click="handleExport('xlsx')" class="dropdown-item">
                                    <svg class="mr-3 w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                    </svg>
                                    Export ke Excel (.xlsx)
                                </button>
                                <button @click="handleExport('pdf')" class="dropdown-item">
                                    <svg class="mr-3 w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>
                                    </svg>
                                    Export ke PDF (.pdf)
                                </button>
                            </div>
                        </template>
                    </Dropdown>

                    <!-- Bulk Actions -->
                    <div class="flex items-center gap-2" v-if="selectedGuests.size > 0">
                        <div class="h-6 w-px bg-gray-300"></div>
                        <button @click="confirmBulkDelete" class="btn-delete">
                            <font-awesome-icon :icon="faTrash" class="mr-2" />
                            <span>Hapus ({{ selectedGuests.size }})</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="flex-1 max-w-md ml-auto">
                        <div class="relative">
                            <TextInput
                                v-model="searchValue"
                                type="text"
                                class="block w-full pl-10 pr-4 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg"
                                placeholder="Cari nama, nomor telepon..."
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <font-awesome-icon :icon="faSearch" class="text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Data Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    v-model="selectAll"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Tamu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Telepon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Konfirmasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kehadiran</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="guest in filteredGuests" :key="guest.id"
                            :class="selectedGuests.has(guest.id) ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="transition-colors duration-150">
                            <!-- Checkbox -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedGuests.has(guest.id)"
                                    @change="toggleGuestSelection(guest.id)"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </td>

                            <!-- Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="ml-0">
                                        <div class="text-sm font-medium text-gray-900">{{ guest.name }}</div>
                                        <div class="text-sm text-gray-500">ID: #{{ guest.id }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Phone -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ guest.phone || '-' }}</div>
                            </td>

                            <!-- Confirmation Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-green-100 text-green-800 border-green-200': guest.confirmation_status === 'confirmed',
                                    'bg-red-100 text-red-800 border-red-200': guest.confirmation_status === 'declined',
                                    'bg-yellow-100 text-yellow-800 border-yellow-200': guest.confirmation_status === 'pending',
                                    'bg-gray-100 text-gray-800 border-gray-200': !guest.confirmation_status
                                }" class="px-3 py-1 text-xs font-medium rounded-full border">
                                    <font-awesome-icon
                                        :icon="guest.confirmation_status === 'confirmed' ? faCheckCircle :
                                              guest.confirmation_status === 'declined' ? faTimesCircle :
                                              faQuestionCircle"
                                        class="mr-1"
                                    />
                                    {{
                                        guest.confirmation_status === 'confirmed' ? 'Konfirmasi'
                                        : guest.confirmation_status === 'declined' ? 'Menolak'
                                        : guest.confirmation_status === 'pending' ? 'Pending'
                                        : 'Belum Respon'
                                    }}
                                </span>
                            </td>

                            <!-- Attendance Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-green-100 text-green-800 border-green-200': guest.attendance_status === 'present',
                                    'bg-yellow-100 text-yellow-800 border-yellow-200': guest.attendance_status === 'planned',
                                    'bg-red-100 text-red-800 border-red-200': guest.attendance_status === 'absent',
                                    'bg-gray-100 text-gray-800 border-gray-200': !guest.attendance_status
                                }" class="px-3 py-1 text-xs font-medium rounded-full border">
                                    {{
                                        guest.attendance_status === 'present' ? 'Hadir'
                                        : guest.attendance_status === 'planned' ? 'Direncanakan'
                                        : guest.attendance_status === 'absent' ? 'Tidak Hadir'
                                        : '-'
                                    }}
                                </span>
                            </td>

                            <!-- Check-in Time -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ guest.check_in_time || '-' }}</span>
                            </td>

                            <!-- Photo -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex justify-center">
                                    <img v-if="guest.photo_path"
                                         :src="'/storage/' + guest.photo_path"
                                         alt="Foto Tamu"
                                         class="w-12 h-12 object-cover rounded-lg border border-gray-200 shadow-sm">
                                    <div v-else class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- View Invitation Link -->
                                    <a :href="route('invitation.show', event.slug) + '?to=' + guest.unique_identifier"
                                       target="_blank"
                                       class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                       title="Lihat Undangan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    <!-- Delete Button -->
                                    <button @click="deleteGuest(guest)"
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            title="Hapus Tamu">
                                        <font-awesome-icon :icon="faTrash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredGuests.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium">
                                        {{ searchValue ? 'Tidak ada tamu yang sesuai dengan pencarian' : 'Belum ada tamu yang terdaftar' }}
                                    </p>
                                    <p class="text-gray-400 text-sm mt-1">
                                        {{ searchValue ? 'Coba kata kunci lain' : 'Mulai dengan mengimport daftar tamu' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Import Modal (Same as before) -->
        <Modal :show="confirmingImport" @close="confirmingImport = false">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-blue-100 rounded-full mr-3">
                        <font-awesome-icon :icon="faFileImport" class="text-blue-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Import Daftar Tamu</h2>
                </div>

                <p class="text-sm text-gray-600 mb-6">
                    Upload file dalam format Excel (.xlsx), CSV, atau vCard (.vcf) untuk menambahkan tamu ke dalam daftar.
                </p>

                <div class="space-y-6">
                    <!-- Mode Import -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Mode Import</label>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" v-model="importForm.mode" value="add" class="form-radio text-blue-600">
                                <div class="ml-3">
                                    <div class="flex items-center">
                                        <font-awesome-icon :icon="faPlus" class="text-green-500 mr-2" />
                                        <span class="text-sm font-medium text-gray-900">Tambah ke Daftar Existing</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Menambahkan tamu baru tanpa menghapus data yang sudah ada</p>
                                </div>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" v-model="importForm.mode" value="replace" class="form-radio text-red-600">
                                <div class="ml-3">
                                    <div class="flex items-center">
                                        <font-awesome-icon :icon="faRetweet" class="text-red-500 mr-2" />
                                        <span class="text-sm font-medium text-gray-900">Ganti Semua Data</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Menghapus semua data existing dan menggantinya dengan data baru</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="text-sm text-gray-600">
                                    <label class="cursor-pointer font-medium text-blue-600 hover:text-blue-500">
                                        Klik untuk upload file
                                        <input type="file"
                                               class="sr-only"
                                               @input="importForm.file = $event.target.files[0]"
                                               accept=".xlsx,.xls,.csv,.vcf" />
                                    </label>
                                    <p class="text-xs">atau drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">Excel, CSV, vCard sampai 10MB</p>
                            </div>
                        </div>
                        <div v-if="importForm.file" class="mt-2 text-sm text-gray-600">
                            File terpilih: {{ importForm.file.name }}
                        </div>
                        <InputError :message="importForm.errors.file" class="mt-2" />
                        <InputError :message="importForm.errors.mode" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-8">
                    <SecondaryButton @click="confirmingImport = false">Batal</SecondaryButton>
                    <PrimaryButton @click="importGuests" :class="{ 'opacity-25': importForm.processing }" :disabled="importForm.processing || !importForm.file">
                        <font-awesome-icon :icon="faUpload" class="mr-2" />
                        {{ importForm.processing ? 'Mengimpor...' : 'Import Sekarang' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Bulk Delete Confirmation Modal -->
        <Modal :show="confirmingBulkDelete" @close="confirmingBulkDelete = false">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-red-100 rounded-full mr-3">
                        <font-awesome-icon :icon="faTrash" class="text-red-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Konfirmasi Hapus Tamu</h2>
                </div>

                <div class="mb-6">
                    <p class="text-gray-600 mb-4">
                        Anda akan menghapus <strong>{{ selectedGuests.size }}</strong> tamu yang dipilih.
                        Aksi ini tidak dapat dibatalkan.
                    </p>

                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    <strong>Peringatan:</strong> Data tamu yang dihapus termasuk riwayat konfirmasi, check-in, dan foto tidak dapat dikembalikan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="confirmingBulkDelete = false">Batal</SecondaryButton>
                    <DangerButton @click="executeBulkDelete">
                        <font-awesome-icon :icon="faTrash" class="mr-2" />
                        Hapus {{ selectedGuests.size }} Tamu
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Send Invitations Modal -->
        <Modal :show="confirmingSendInvitations" @close="confirmingSendInvitations = false">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-green-100 rounded-full mr-3">
                        <font-awesome-icon :icon="faPaperPlane" class="text-green-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Kirim Undangan WhatsApp</h2>
                </div>

                <div class="mb-6">
                    <p class="text-gray-600 mb-4">
                        Anda akan mengirim undangan WhatsApp ke <strong>{{ selectedGuests.size }}</strong> tamu yang dipilih.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Info:</strong> Setiap tamu akan mendapat link undangan personal yang unik.
                                    Mereka dapat menggunakan link tersebut untuk konfirmasi kehadiran.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Perhatian:</strong> Pastikan saldo Fonnte mencukupi untuk mengirim {{ selectedGuests.size }} pesan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="confirmingSendInvitations = false" :disabled="sendingInvitations">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton @click="sendInvitations"
                                   :class="{ 'opacity-25': sendingInvitations }"
                                   :disabled="sendingInvitations">
                        <font-awesome-icon :icon="faPaperPlane" class="mr-2" />
                        {{ sendingInvitations ? 'Mengirim...' : `Kirim ke ${selectedGuests.size} Tamu` }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Help Modal (Same as before but enhanced) -->
        <Modal :show="showingHelp" @close="showingHelp = false" max-width="4xl">
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-blue-100 rounded-full mr-3">
                        <font-awesome-icon :icon="faInfoCircle" class="text-blue-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Panduan Import Kontak</h2>
                </div>

                <div class="space-y-6">
                    <!-- Google Contacts Export Guide -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-blue-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Cara Export dari Google Contacts
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white text-sm font-bold rounded-full flex items-center justify-center mr-3 mt-0.5">1</div>
                                    <div>
                                        <p class="text-sm text-gray-700">Buka <strong>contacts.google.com</strong></p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white text-sm font-bold rounded-full flex items-center justify-center mr-3 mt-0.5">2</div>
                                    <div>
                                        <p class="text-sm text-gray-700">Pilih kontak yang ingin di-export (atau pilih semua)</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white text-sm font-bold rounded-full flex items-center justify-center mr-3 mt-0.5">3</div>
                                    <div>
                                        <p class="text-sm text-gray-700">Klik <strong>"Export"</strong> di menu samping</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white text-sm font-bold rounded-full flex items-center justify-center mr-3 mt-0.5">4</div>
                                    <div>
                                        <p class="text-sm text-gray-700">Pilih format <strong>"vCard (.vcf)"</strong> untuk hasil terbaik</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white text-sm font-bold rounded-full flex items-center justify-center mr-3 mt-0.5">5</div>
                                    <div>
                                        <p class="text-sm text-gray-700">Download dan upload file tersebut di sini</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border">
                                <div class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center mb-2">
                                    <video controls loop width="100%" height="100%" class="rounded-lg">
        <source src="/Tutorial_Export_Kontak_Google.mp4" type="video/mp4" />
        Browser Anda tidak mendukung video.
    </video>
                                </div>
                                <p class="text-xs text-gray-500 text-center">Video tutorial akan segera tersedia</p>
                            </div>
                        </div>
                    </div>

                    <!-- Format Support -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Format File yang Didukung</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                                    </svg>
                                    <h4 class="font-medium text-green-900">Excel (.xlsx)</h4>
                                </div>
                                <p class="text-sm text-green-700">Format tabel dengan kolom Nama dan No. Telepon</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <h4 class="font-medium text-blue-900">CSV</h4>
                                </div>
                                <p class="text-sm text-blue-700">File teks dengan pemisah koma atau titik koma</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h4 class="font-medium text-purple-900">vCard (.vcf)</h4>
                                </div>
                                <p class="text-sm text-purple-700">Format standar kontak (Recommended)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Tips Penting</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Pastikan kolom nama dan nomor telepon sudah terisi dengan benar</li>
                                        <li>Nomor telepon sebaiknya dalam format internasional (+62xxx)</li>
                                        <li>Hindari karakter khusus dalam nama tamu</li>
                                        <li>File maksimal 10MB dengan maksimal 5000 kontak</li>
                                        <li>Gunakan mode "Tambah" jika ingin mempertahankan data existing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <SecondaryButton @click="showingHelp = false">
                        Mengerti
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingMessageEditor" @close="closeMessageEditor" max-width="6xl">
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-green-100 rounded-full mr-3">
                        <font-awesome-icon :icon="faEdit" class="text-green-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Edit Pesan Undangan WhatsApp</h2>
                </div>

                <div class="mb-6">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Tips:</strong> Anda dapat mengedit template default atau membuat pesan khusus.
                                    Gunakan placeholder untuk data dinamis yang akan diganti otomatis untuk setiap tamu.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placeholder Reference -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Editor Section -->
                    <div class="lg:col-span-2">
                        <div class="mb-4 flex justify-between items-center">
                            <label class="block text-sm font-medium text-gray-700">Pesan Undangan</label>
                            <div class="flex space-x-2">
                                <button @click="resetToTemplate"
                                        :disabled="loadingTemplate"
                                        class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                    🔄 Reset ke Template
                                </button>
                                <button @click="previewMessage"
                                        class="text-xs px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-md transition-colors">
                                    👁️ Preview
                                </button>
                            </div>
                        </div>

                        <!-- CKEditor Container -->
                        <div class="border rounded-lg">
                            <div v-if="loadingTemplate" class="p-8 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                                <p class="mt-2 text-sm text-gray-600">Memuat template...</p>
                            </div>
                            <div v-else id="message-editor" class="min-h-[300px]"></div>
                        </div>
                    </div>

                    <!-- Placeholder Panel -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">📝 Placeholder yang Tersedia</h3>
                            <div class="space-y-2">
                                <div v-for="(description, placeholder) in placeholders" :key="placeholder">
                                    <button @click="insertPlaceholder(placeholder)"
                                            class="w-full text-left p-2 text-xs bg-white border border-gray-200 rounded hover:bg-blue-50 hover:border-blue-300 transition-colors">
                                        <div class="font-mono text-blue-600">{{ placeholder }}</div>
                                        <div class="text-gray-500 mt-1">{{ description }}</div>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-xs font-medium text-gray-700 mb-2">Contoh Penggunaan:</h4>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <p>• <code>{groom_name}</code> → {{ event.groom?.name }}</p>
                                    <p>• <code>{ceremony_date}</code> → Sabtu, 15 Juni 2024</p>
                                    <p>• <code>{ceremony_location}</code> → {{ event.ceremonies?.[0]?.location }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Character Counter -->
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-yellow-800">Panjang Pesan:</span>
                                <span :class="customMessage.length > 1600 ? 'text-red-600 font-semibold' : 'text-yellow-800'">
                                    {{ customMessage.length }} karakter
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-yellow-700">
                                WhatsApp optimal: max 1600 karakter
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mt-8 pt-6 border-t">
                    <div class="text-sm text-gray-600">
                        Akan dikirim ke <strong>{{ selectedGuests.size }}</strong> tamu terpilih
                    </div>
                    <div class="flex space-x-3">
                        <SecondaryButton @click="closeMessageEditor" :disabled="sendingInvitations">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton @click="sendInvitationsWithCustomMessage"
                                       :class="{ 'opacity-25': sendingInvitations }"
                                       :disabled="sendingInvitations || !customMessage.trim()">
                            <font-awesome-icon :icon="faPaperPlane" class="mr-2" />
                            {{ sendingInvitations ? 'Mengirim...' : `Kirim ke ${selectedGuests.size} Tamu` }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Enhanced Button Styles */
.btn-check-in {
    @apply inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105 hover:shadow-xl;
}

.btn-send {
    @apply inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105;
}

.btn-send-disabled {
    @apply inline-flex items-center px-6 py-3 bg-gray-300 text-gray-500 font-semibold rounded-xl cursor-not-allowed;
}

.btn-import {
    @apply inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl;
}

.btn-export {
    @apply inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl;
}

.btn-delete {
    @apply inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium rounded-lg shadow-md transition-all duration-200 hover:shadow-lg;
}

.btn-help {
    @apply inline-flex items-center justify-center w-12 h-12 bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-800 rounded-xl shadow-md border border-gray-200 transition-all duration-200 hover:shadow-lg;
}

.dropdown-item {
    @apply flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150;
}

/* Custom radio styles */
.form-radio {
    @apply h-4 w-4 border-gray-300 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
}

/* Enhanced Table Styles */
table {
    @apply border-collapse;
}

thead th {
    @apply bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 font-semibold text-xs uppercase tracking-wider border-b border-gray-200;
}

tbody tr:nth-child(even) {
    @apply bg-gray-50;
}

tbody tr:hover {
    @apply shadow-sm;
}

/* Animation classes */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}

.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}

/* Stats cards animation */
.grid > div {
    animation: slideIn 0.6s ease-out;
}
.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.2s; }
.grid > div:nth-child(3) { animation-delay: 0.3s; }
.grid > div:nth-child(4) { animation-delay: 0.4s; }

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-check-in,
    .btn-send,
    .btn-import,
    .btn-export {
        @apply w-full justify-center mb-2;
    }

    .flex-wrap.gap-3 {
        @apply flex-col;
    }

    .flex-1.max-w-md.ml-auto {
        @apply ml-0 max-w-none;
    }
}

/* Enhanced hover effects */
.bg-white:hover {
    @apply shadow-lg transition-shadow duration-300;
}

/* Selection highlighting */
.bg-indigo-50 {
    @apply border-l-4 border-indigo-400;
}

/* Custom checkbox styles */
input[type="checkbox"]:checked {
    @apply bg-indigo-600 border-indigo-600;
}

/* Progress bar animations */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Modal improvements */
.modal-content {
    @apply animate-slide-in;
}

/* Button loading state */
.opacity-25 {
    @apply cursor-not-allowed;
}

/* Enhanced shadows */
.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* CKEditor styling */
#message-editor {
    min-height: 300px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.ck-editor__editable {
    min-height: 300px !important;
}
</style>
