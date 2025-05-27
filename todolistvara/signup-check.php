<?php
session_start();
include "db_conn.php"; // Pastikan file ini ada dan berisi koneksi database

// Jika sudah login, redirect ke home
if (isset($_SESSION['id'])) {
    header("Location: home.php");
    exit();
}

if (isset($_POST['uname']) && isset($_POST['password']) && isset($_POST['name']) && isset($_POST['re_password'])) {

    function validate($data){
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);
       return $data;
    }

    $uname = validate($_POST['uname']);
    $pass = validate($_POST['password']);
    $re_pass = validate($_POST['re_password']);
    $name = validate($_POST['name']);

    // Validasi input kosong
    if (empty($uname)) {
        header("Location: signup.php?error=User Name is required&name=$name&uname=$uname");
        exit();
    } else if(empty($pass)){
        header("Location: signup.php?error=Password is required&name=$name&uname=$uname");
        exit();
    } else if(empty($re_pass)){
        header("Location: signup.php?error=Re-enter Password is required&name=$name&uname=$uname");
        exit();
    } else if(empty($name)){
        header("Location: signup.php?error=Name is required&name=$name&uname=$uname");
        exit();
    } else if($pass !== $re_pass){
        header("Location: signup.php?error=The confirmation password does not match&name=$name&uname=$uname");
        exit();
    } else {
        // Hashing password dengan password_hash() (SANGAT DIREKOMENDASIKAN)
        $pass = password_hash($pass, PASSWORD_DEFAULT); // Gunakan PASSWORD_DEFAULT untuk algoritma hashing terbaru

        // Cek apakah username sudah ada
        $sql_check_uname = "SELECT * FROM users_regis WHERE user_name = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check_uname);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "s", $uname);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($result_check) > 0) {
                header("Location: signup.php?error=The username is taken, try another one&name=$name&uname=$uname");
                exit();
            }
            mysqli_stmt_close($stmt_check);
        } else {
            header("Location: signup.php?error=Database error during username check: " . mysqli_error($conn));
            exit();
        }

        // Jika username belum ada, masukkan data baru
        $sql_insert = "INSERT INTO users_regis(user_name, password, name) VALUES(?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "sss", $uname, $pass, $name);
            if (mysqli_stmt_execute($stmt_insert)) {
                // Registrasi berhasil, redirect ke halaman login dengan pesan sukses
                header("Location: index.php?success=Your account has been created successfully");
                exit();
            } else {
                header("Location: signup.php?error=Unknown error occurred during registration: " . mysqli_stmt_error($stmt_insert));
                exit();
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            header("Location: signup.php?error=Database error during registration: " . mysqli_error($conn));
            exit();
        }
    }
} else {
    // Jika tidak ada data POST (akses langsung ke signup-check.php), redirect kembali ke halaman signup
    header("Location: signup.php");
    exit();
}