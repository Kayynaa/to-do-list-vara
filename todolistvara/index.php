<?php
session_start();

if (isset($_SESSION['id'])) {
    header("Location: home.php"); // Pastikan ini mengarah ke halaman utama setelah login
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <link rel="stylesheet" type="text/css" href="login_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="header-info">
            <h1>Hello!</h1>
            <p>Welcome to To-Do List</p>
        </div>
        <h2 class="login-title">Login</h2>

        <?php
        // Menampilkan pesan error atau sukses dari redirect login.php atau signup-check.php
        if (isset($_GET['error'])) { ?>
            <p class="message error"><?= htmlspecialchars($_GET['error']); ?></p>
        <?php } ?>
        <?php if (isset($_GET['success'])) { ?>
            <p class="message success-message"><?= htmlspecialchars($_GET['success']); ?></p>
        <?php } ?>

        <form action="login.php" method="post" class="login-form">
            <div class="input-group">
                <input type="text" name="uname" placeholder="Username"
                       value="<?= isset($_GET['uname']) ? htmlspecialchars($_GET['uname']) : ''; ?>" required>
                <i class="fas fa-user icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-lock icon"></i>
            </div>

            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>

            <button type="submit" class="login-button">Login</button>

            <div class="social-login"></div>

            <p class="signup-text">Don't have account? <a href="signup.php" class="signup-link">Sign Up</a></p>
        </form>
    </div>

    <script>
        // Script untuk menghilangkan pesan notifikasi secara otomatis
        document.addEventListener('DOMContentLoaded', function() {
            const messageElements = document.querySelectorAll('.message');
            messageElements.forEach(function(msg) {
                setTimeout(() => {
                    msg.classList.add('hide'); // Tambahkan class 'hide' untuk animasi fade out
                    msg.addEventListener('transitionend', function() {
                        msg.remove(); // Hapus elemen dari DOM setelah animasi selesai
                    });
                }, 3000); // Pesan akan hilang setelah 3 detik
            });
        });
    </script>
</body>
</html>