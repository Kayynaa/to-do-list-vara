<?php
session_start();
include "db_conn.php"; // Pastikan file ini ada dan berisi koneksi database yang benar

// Cek apakah pengguna sudah login
if (!isset($_SESSION['id']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// --- Logika Penanganan Aksi (Tambah, Checklist/Unchecklist, Hapus, Edit) ---

// Tambah Task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $task = $_POST['task'];
    $deadline = $_POST['deadline'];

    if (empty($task) || empty($deadline)) {
        $_SESSION['message'] = "Task dan Deadline tidak boleh kosong.";
        $_SESSION['message_type'] = "error";
    } else {
        $query = "INSERT INTO tabel_todos (user_id, task, deadline, status) VALUES (?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $task, $deadline);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Task berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menambahkan task: " . mysqli_stmt_error($stmt);
                $_SESSION['message_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement tambah task: " . mysqli_error($conn);
            $_SESSION['message_type'] = "error";
        }
    }
    header("Location: home.php");
    exit();
}

// Checklist/Unchecklist Task (TOGGLE)
if (isset($_GET['toggle_status'])) {
    $task_id = $_GET['toggle_status'];
    $current_status = '';

    $query_select = "SELECT status FROM tabel_todos WHERE id = ? AND user_id = ?";
    $stmt_select = mysqli_prepare($conn, $query_select);
    if ($stmt_select) {
        mysqli_stmt_bind_param($stmt_select, "ii", $task_id, $user_id);
        mysqli_stmt_execute($stmt_select);
        mysqli_stmt_bind_result($stmt_select, $current_status);
        mysqli_stmt_fetch($stmt_select);
        mysqli_stmt_close($stmt_select);

        $new_status = ($current_status === 'completed') ? 'pending' : 'completed';

        $query_update = "UPDATE tabel_todos SET status = ? WHERE id = ? AND user_id = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "sii", $new_status, $task_id, $user_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['message'] = "Status task berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal memperbarui status task: " . mysqli_stmt_error($stmt_update);
                $_SESSION['message_type'] = "error";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement update status: " . mysqli_error($conn);
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Gagal mengambil status task: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
    }
    header("Location: home.php");
    exit();
}

// Hapus Task
if (isset($_GET['hapus'])) {
    $task_id = $_GET['hapus'];
    $query = "DELETE FROM tabel_todos WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Task berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menghapus task: " . mysqli_stmt_error($stmt);
            $_SESSION['message_type'] = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['message'] = "Gagal menyiapkan statement hapus task: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
    }
    header("Location: home.php");
    exit();
}

// Edit Task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_task_submit'])) {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $deadline = $_POST['deadline'];

    if (empty($task_name) || empty($deadline)) {
        $_SESSION['message'] = "Nama task dan deadline tidak boleh kosong.";
        $_SESSION['message_type'] = "error";
    } else {
        $query = "UPDATE tabel_todos SET task = ?, deadline = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssii", $task_name, $deadline, $task_id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Task berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal memperbarui task: " . mysqli_stmt_error($stmt);
                $_SESSION['message_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement edit task: " . mysqli_error($conn);
            $_SESSION['message_type'] = "error";
        }
    }
    header("Location: home.php");
    exit();
}

// --- Logika Pengambilan Data Task ---

$search = isset($_GET['search']) ? $_GET['search'] : "";
$sql_params = [$user_id];
$param_types = "i"; // user_id selalu integer

$sql = "SELECT id, task, deadline, status, created_line FROM tabel_todos WHERE user_id = ?";

if ($search != "") {
    $sql .= " AND task LIKE ?";
    $search_param = '%' . $search . '%';
    $sql_params[] = $search_param;
    $param_types .= "s"; // search parameter adalah string
}
$sql .= " ORDER BY deadline ASC, created_line ASC"; // Urutkan berdasarkan deadline, lalu waktu pembuatan

$stmt = mysqli_prepare($conn, $sql);

// Inisialisasi $result dengan nilai default null
$result = null;

if ($stmt) {
    // Pastikan kita punya parameter untuk di-bind sebelum memanggil mysqli_stmt_bind_param
    if (count($sql_params) > 0) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$sql_params);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $_SESSION['message'] = "Gagal mengeksekusi query task: " . mysqli_stmt_error($stmt);
        $_SESSION['message_type'] = "error";
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['message'] = "Gagal menyiapkan query task: " . mysqli_error($conn);
    $_SESSION['message_type'] = "error";
}

// mysqli_close($conn); // Baris ini sengaja dikomentari untuk menghindari masalah kosong
?>
<!DOCTYPE html>
<html>
<head>
    <title>HOME</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            background-image: url('bg.jpg'); /* Pastikan path ini benar */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; /* Agar background tidak ikut scroll */
            min-height: 100vh; /* Memastikan background mengisi seluruh tinggi viewport */
            display: flex;
            justify-content: center; /* Untuk centering kotak utama */
            align-items: center; /* Untuk centering kotak utama */
            padding: 20px; /* Padding di sekitar kotak utama */
            position: relative;
            overflow-y: auto; /* Memungkinkan scroll jika konten task banyak */
            overflow-x: hidden;
        }

        /* OVERLAY PUTIH SANGAT TRANSPARAN AGAR BACKGROUND BUNGA LEBIH TERLIHAT */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.05); /* KUNCI: Sangat transparan (5%) */
            backdrop-filter: blur(3px); /* Efek blur halus */
            z-index: 0; /* Pastikan di bawah konten utama */
        }

        /* PENTING: Pastikan div utama Nana untuk konten To-Do List memiliki class "container" */
        .container {
            background-color: rgba(255, 255, 255, 0.7); /* KUNCI: Transparansi untuk kotak putih (70%) */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            width: 95%;
            max-width: 800px;
            z-index: 1; /* Pastikan di atas overlay */
            position: relative;
            margin: 20px auto; /* Memberi jarak atas bawah dan tengah otomatis */
        }

        /* Styling untuk notifikasi (tidak berubah dari sebelumnya) */
        .message {
            opacity: 1;
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        .message.hide {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
        }
        body.modal-open {
            overflow: hidden;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="style.css?v=<?php echo time(); ?>">

</head>
<body>
    <div class="container">
        <?php
        if (isset($_SESSION['signup_success_message'])) {
            echo '<div id="signupNotificationMessage" class="message success">' . htmlspecialchars($_SESSION['signup_success_message']) . '</div>';
            unset($_SESSION['signup_success_message']);
        }
        ?>

        <div class="main-header">
            <h1>Hai, <?= htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <a href="logout.php" class="logout-link">Logout</a>
        </div>

        <h2 class="main-title">To-do list</h2>

        <div class="date-display">
            <span class="today-text">Today,</span>
            <span class="current-date"><?= date('d F Y'); ?></span>
        </div>

        <div class="tab-navigation">
            <span class="tab-item active">All tasks</span>
        </div>

        <div class="search-bar">
            <form method="get" action="home.php">
                <input type="text" name="search" placeholder="Cari task" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" style="display: none;"></button>
            </form>
        </div>

        <?php
        // Tampilkan pesan dari session jika ada (untuk task actions)
        if (isset($_SESSION['message'])) {
            echo '<div id="taskNotificationMessage" class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="task-list-wrapper">
            <ul class="task-list">
                <?php
                // Cek apakah $result valid dan ada baris data
                if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
                    $current_date_obj = new DateTimeImmutable('today');

                    while ($row = mysqli_fetch_assoc($result)) {
                        $task_deadline_obj = new DateTimeImmutable($row['deadline']);
                        $status_text = '';
                        $status_class_modifier = '';

                        if ($row['status'] === 'completed') {
                            $status_text = 'SELESAI';
                            $status_class_modifier = 'completed';
                        } else {
                            if ($task_deadline_obj < $current_date_obj) {
                                $status_text = 'TERLAMBAT';
                                $status_class_modifier = 'late';
                            } else {
                                $status_text = 'BELUM DIKERJAKAN';
                                $status_class_modifier = 'pending';
                            }
                        }

                        $task_item_class = 'task-item ' . $status_class_modifier;
                ?>
                <li class="<?= $task_item_class ?>">
                    <div class="status-circle" data-task-id="<?= htmlspecialchars($row['id']) ?>">
                        <?php if ($row['status'] === 'completed') { ?>
                            <i class="fas fa-check"></i>
                        <?php } ?>
                    </div>

                    <div class="task-details">
                        <span class="task-name"><?= htmlspecialchars($row['task']) ?></span>
                        <span class="task-deadline">Deadline: <?= date('d M Y', strtotime($row['deadline'])) ?></span>
                    </div>

                    <div class="task-status-actions">
                        <span class="task-status"><?= $status_text ?></span>
                        <div class="task-actions">
                            <?php if ($row['status'] !== 'completed') { ?>
                                <a href="#" class="edit-task-btn"
                                    data-task-id="<?= htmlspecialchars($row['id']) ?>"
                                    data-task-name="<?= htmlspecialchars($row['task']) ?>"
                                    data-task-deadline="<?= htmlspecialchars($row['deadline']) ?>">Edit</a>
                            <?php } ?>
                            <a href="home.php?hapus=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Yakin hapus task ini?')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                </li>
                <?php
                    }
                } else {
                    echo '<li class="task-item no-tasks"><div class="task-details"><span class="task-name">Tidak ada task ditemukan.</span></div></li>';
                }
                ?>
            </ul>
        </div>
        <div class="fab" id="fabAddTask">
            +
        </div>

    </div>

    <div class="modal-overlay" id="addTaskModal">
        <div class="add-task-modal">
            <button class="modal-close-btn" id="closeAddTaskModalBtn">&times;</button>
            <h2>Add task</h2>
            <form method="post" action="home.php">
                <input type="text" name="task" placeholder="Name" required>
                <input type="date" name="deadline" required>
                <button type="submit" name="tambah">Add task</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editTaskModal">
        <div class="edit-task-modal">
            <button class="modal-close-btn" id="closeEditTaskModalBtn">&times;</button>
            <h2>Edit Task</h2>
            <form method="post" action="home.php">
                <input type="hidden" name="task_id" id="editTaskId">
                <label for="editTaskName">Task Name</label>
                <input type="text" name="task_name" id="editTaskName" required>
                <label for="editTaskDeadline">Deadline</label>
                <input type="date" name="deadline" id="editTaskDeadline" required>
                <button type="submit" name="edit_task_submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        const fabAddTask = document.getElementById('fabAddTask');
        const addTaskModal = document.getElementById('addTaskModal');
        const closeAddTaskModalBtn = document.getElementById('closeAddTaskModalBtn');

        fabAddTask.addEventListener('click', () => {
            addTaskModal.classList.add('show');
            document.body.classList.add('modal-open');
            document.querySelector('.add-task-modal form').reset();
        });

        closeAddTaskModalBtn.addEventListener('click', () => {
            addTaskModal.classList.remove('show');
            document.body.classList.remove('modal-open');
        });

        addTaskModal.addEventListener('click', (e) => {
            if (e.target === addTaskModal) {
                addTaskModal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        });

        const editTaskModal = document.getElementById('editTaskModal');
        const closeEditTaskModalBtn = document.getElementById('closeEditTaskModalBtn');
        const editTaskIdInput = document.getElementById('editTaskId');
        const editTaskNameInput = document.getElementById('editTaskName');
        const editTaskDeadlineInput = document.getElementById('editTaskDeadline');

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-task-btn')) {
                e.preventDefault();
                const button = e.target;
                const taskId = button.dataset.taskId;
                const taskName = button.dataset.taskName;
                const taskDeadline = button.dataset.taskDeadline;

                editTaskIdInput.value = taskId;
                editTaskNameInput.value = taskName;
                editTaskDeadlineInput.value = taskDeadline;

                editTaskModal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        });

        closeEditTaskModalBtn.addEventListener('click', () => {
            editTaskModal.classList.remove('show');
            document.body.classList.remove('modal-open');
        });

        editTaskModal.addEventListener('click', (e) => {
            if (e.target === editTaskModal) {
                editTaskModal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('.status-circle')) {
                const circle = e.target.closest('.status-circle');
                const taskId = circle.dataset.taskId;
                if (taskId) {
                    window.location.href = `home.php?toggle_status=${taskId}`;
                }
            }
        });

        // UBAH DURASI TIMEOUT UNTUK NOTIFIKASI
        const taskNotificationMessage = document.getElementById('taskNotificationMessage');
        if (taskNotificationMessage) {
            setTimeout(() => {
                taskNotificationMessage.classList.add('hide');
                taskNotificationMessage.addEventListener('transitionend', () => {
                    taskNotificationMessage.remove();
                });
            }, 3000); // Durasi 3 detik
        }

        const signupNotificationMessage = document.getElementById('signupNotificationMessage');
        if (signupNotificationMessage) {
            setTimeout(() => {
                signupNotificationMessage.classList.add('hide');
                signupNotificationMessage.addEventListener('transitionend', () => {
                    signupNotificationMessage.remove();
                });
            }, 3000); // Durasi 3 detik
        }
    </script>
</body>
</html>