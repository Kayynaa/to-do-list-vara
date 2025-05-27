<?php

session_start();

include "db_conn.php"; // Pastikan file ini ada dan berisi koneksi database

// Jika sudah login, redirect ke home
if (isset($_SESSION['id'])) {
    header("Location: home.php");
    exit();
}

// Proses jika ada data POST dari form login
if (isset($_POST['uname']) && isset($_POST['password'])) {

    function validate($data){
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);
       return $data;
    }

    $uname = validate($_POST['uname']);
    $pass = validate($_POST['password']); // Password yang diinput pengguna (belum di-hash)

    if (empty($uname)) {
        header("Location: index.php?error=User Name is required");
        exit();
    }else if(empty($pass)){
        header("Location: index.php?error=Password is required");
        exit();
    }else{
        // *** PERUBAHAN PENTING DI SINI ***

        // 1. Kita tidak lagi melakukan md5($pass) di sini.
        //    MD5($pass) yang sebelumnya ada di sini:
        //    $pass = md5($pass);
        //    SUDAH DIHAPUS karena tidak lagi sesuai dengan password_hash() di signup.

        // 2. Kita akan mengambil hash password dari database terlebih dahulu
        //    berdasarkan username yang diinput.

        // Menggunakan prepared statement untuk keamanan
        // Perhatikan: query SQL hanya mencari berdasarkan user_name, BUKAN user_name DAN password
        $sql = "SELECT id, user_name, name, password FROM users_regis WHERE user_name = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $uname);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) === 1) {
                $row = mysqli_fetch_assoc($result);
                $hashed_password_from_db = $row['password']; // Ambil hash password dari database

                // KUNCI PERUBAHAN: Gunakan password_verify() untuk membandingkan password yang diinput
                // dengan hash yang tersimpan di database.
                // password_verify() secara otomatis menangani algoritma hashing yang digunakan oleh password_hash()
                if (password_verify($pass, $hashed_password_from_db)) {
                    // Jika verifikasi berhasil, atur session dan redirect ke home
                    $_SESSION['user_name'] = $row['user_name'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['id'] = $row['id'];
                    header("Location: home.php");
                    exit();
                } else {
                    // Jika password tidak cocok setelah verifikasi
                    header("Location: index.php?error=Incorect User name or password");
                    exit();
                }
            } else {
                // Jika username tidak ditemukan di database
                header("Location: index.php?error=Incorect User name or password");
                exit();
            }
            mysqli_stmt_close($stmt);
        } else {
            // Jika prepared statement gagal disiapkan
            header("Location: index.php?error=Database query error: " . mysqli_error($conn));
            exit();
        }
    }
} else {
    // Jika tidak ada data POST (akses langsung ke login.php tanpa submit form), redirect kembali ke halaman login (index.php)
    header("Location: index.php");
    exit();
}
// Tidak perlu mysqli_close($conn) di sini karena akan ditutup otomatis saat skrip selesai atau di halaman lain jika perlu.