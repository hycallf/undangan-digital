<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Daftar Tamu</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }

        .header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }

        .event-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .event-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .status-present {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }

        .status-planned {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }

        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Daftar Tamu</h1>
        <h2>{{ $event->groom->nickname ?? 'Mempelai Pria' }} & {{ $event->bride->nickname ?? 'Mempelai Wanita' }}</h2>
    </div>

    <div class="event-info">
        <h3>Informasi Acara</h3>
        <p><strong>Tanggal:</strong>
            {{ $event->ceremonies ? $event->ceremonies[0]->ceremony_date : '-' }}
        </p>

        @php

            $ceremonies = $event->ceremonies->sortBy([['ceremony_date', 'asc'], ['start_time', 'asc']]);

            $firstCeremony = $event->ceremonies->first();
            $lastCeremony = $event->ceremonies->last();
        @endphp
        <p>
            <strong>Waktu:</strong>
            {{ $firstCeremony ? $firstCeremony->start_time : '-' }}
            sampai Selesai
        </p>
        <p><strong>Tempat:</strong>
            {{ $event->ceremonies ? $event->ceremonies[0]->location : '-' }}</p>
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="stat-number">{{ $guests->count() }}</div>
            <div class="stat-label">Total Tamu</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $guests->where('attendance_status', 'present')->count() }}</div>
            <div class="stat-label">Sudah Hadir</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $guests->where('attendance_status', 'planned')->count() }}</div>
            <div class="stat-label">Direncanakan</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $guests->where('attendance_status', 'absent')->count() }}</div>
            <div class="stat-label">Tidak Hadir</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Nama</th>
                <th style="width: 20%;">No. Telepon</th>
                <th style="width: 15%;">Status Kehadiran</th>
                <th style="width: 20%;">Waktu Check-in</th>
                <th style="width: 10%;">Foto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($guests as $index => $guest)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $guest->name }}</td>
                    <td>{{ $guest->phone ?? '-' }}</td>
                    <td>
                        @php
                            $statusClass = match ($guest->attendance_status) {
                                'present' => 'status-present',
                                'planned' => 'status-planned',
                                'absent' => 'status-absent',
                                default => '',
                            };

                            $statusText = match ($guest->attendance_status) {
                                'present' => 'Hadir',
                                'planned' => 'Direncanakan',
                                'absent' => 'Tidak Hadir',
                                default => '-',
                            };
                        @endphp
                        <span class="{{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                    <td>{{ $guest->check_in_time ?? '-' }}</td>
                    <td>{{ $guest->photo_path ? 'Ada' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #666; font-style: italic;">
                        Belum ada tamu yang terdaftar
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i') }}</p>
        <p>Total {{ $guests->count() }} tamu</p>
    </div>
</body>

</html>
