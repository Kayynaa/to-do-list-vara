<?php
session_start();

// Redirect ke home.php jika sudah login
if (isset($_SESSION['id'])) {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGN UP</title>
    <link rel="stylesheet" type="text/css" href="signup_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="signup-container">
        <div class="header-info">
            <h1>Join Us!</h1>
            <p>Create your To-Do List account</p>
        </div>
        <h2 class="signup-title">Sign Up</h2>

        <?php
        // Menampilkan pesan error atau sukses dari redirect signup-check.php
        if (isset($_GET['error'])) { ?>
            <p class="message error"><?= htmlspecialchars($_GET['error']); ?></p>
        <?php } ?>
        <?php if (isset($_GET['success'])) { ?>
            <p class="message success-message"><?= htmlspecialchars($_GET['success']); ?></p>
        <?php } ?>

        <form action="signup-check.php" method="post" class="signup-form">
            <div class="input-group">
                <input type="text" name="name" placeholder="Name"
                       value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>" required>
                <i class="fas fa-user icon"></i>
            </div>

            <div class="input-group">
                <input type="text" name="uname" placeholder="Username"
                       value="<?= isset($_GET['uname']) ? htmlspecialchars($_GET['uname']) : ''; ?>" required>
                <i class="fas fa-user icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-lock icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="re_password" placeholder="Re-enter Password" required>
                <i class="fas fa-lock icon"></i>
            </div>

            <button type="submit" class="signup-button">Sign Up</button>
            <p class="login-text">Already have an account? <a href="index.php" class="login-link">Login here</a></p>
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