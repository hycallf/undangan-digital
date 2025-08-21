<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

// ambil user role dari props
const page = usePage()
const role = page.props.auth.user.role

// load dashboard sesuai role
let DashboardComponent = null
if (role === 'admin') {
  DashboardComponent = (await import('@/Pages/Dashboard/Admin.vue')).default
} else if (role === 'receptionist') {
  DashboardComponent = (await import('@/Pages/Dashboard/Receptionist.vue')).default
} else {
  DashboardComponent = {
    template: `<div class="p-6 text-red-600">Role tidak dikenali</div>`
  }
}
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <!-- Render dashboard sesuai role -->
          <component :is="DashboardComponent" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
