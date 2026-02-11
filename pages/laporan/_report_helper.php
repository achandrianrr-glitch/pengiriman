<?php
// pages/laporan/_report_helper.php

if (!function_exists('laporan_try_select')) {
    function laporan_try_select($db, $sql, $params = [])
    {
        try {
            $res = $db->select($sql, $params);
            return $res;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('laporan_detect_date_column')) {
    function laporan_detect_date_column($db)
    {
        // Coba beberapa nama kolom tanggal yang umum dipakai
        $candidates = [
            'tanggal_pengiriman',
            'created_at',
            'tanggal_input',
            'tanggal',
            'tgl_kirim'
        ];

        foreach ($candidates as $col) {
            $test = laporan_try_select($db, "SELECT `$col` AS t FROM pengiriman LIMIT 1");
            if (is_array($test)) {
                return $col;
            }
        }
        // fallback default (kalau tabel belum punya kolom tanggal, nanti ditampilkan notice di UI)
        return $candidates[0];
    }
}

if (!function_exists('laporan_fetch_rows')) {
    function laporan_fetch_rows($db, $start, $end, $q, &$dateColUsed, &$dateColOk)
    {
        $dateColUsed = laporan_detect_date_column($db);

        // cek apakah benar kolom itu valid
        $dateColOk = is_array(laporan_try_select($db, "SELECT `$dateColUsed` AS t FROM pengiriman LIMIT 1"));

        // Jika kolom tanggal tidak ada, tetap ambil data tanpa filter tanggal
        $params = [];
        $where = "WHERE 1=1";

        if ($dateColOk) {
            $where .= " AND `$dateColUsed` BETWEEN ? AND ?";
            $params[] = $start . " 00:00:00";
            $params[] = $end . " 23:59:59";
        }

        if ($q !== '') {
            $where .= " AND (
                COALESCE(nomor_resi,'') LIKE ?
                OR COALESCE(pengirim_nama,'') LIKE ?
                OR COALESCE(penerima_nama,'') LIKE ?
            )";
            $like = "%" . $q . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Ambil semua kolom biar aman (tidak error kalau ada kolom yang beda2)
        // Tambahkan alias tanggal_view untuk tampilan
        $sql = "SELECT p.*" . ($dateColOk ? ", p.`$dateColUsed` AS tanggal_view" : "") . "
                FROM pengiriman p
                $where
                ORDER BY " . ($dateColOk ? "p.`$dateColUsed` DESC" : "p.nomor_resi DESC");

        $rows = laporan_try_select($db, $sql, $params);
        return is_array($rows) ? $rows : [];
    }
}
