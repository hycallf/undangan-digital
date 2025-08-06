<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Anda bisa mengirim data ke frontend di sini, misalnya jumlah event, jumlah tamu, dll.
        // Untuk sekarang, kita hanya render halaman.
        return Inertia::render('Admin/Dashboard');
    }
}
