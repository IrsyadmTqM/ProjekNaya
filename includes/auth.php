<?php
// Login untuk kedua website. Nama cookie session dibedakan per role,
// jadi anggota dan bendahara bisa login bersamaan di browser yang sama.

function start_session(string $role): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name($role === 'bendahara' ? 'KAS_BENDAHARA' : 'KAS_ANGGOTA');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function attempt_login(string $role, string $username, string $password): bool
{
    $user = db_row('SELECT id, nama, password FROM users WHERE username = ? AND role = ? AND aktif = 1', [$username, $role]);
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['role'] = $role;
    return true;
}

function is_logged_in(string $role): bool
{
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === $role;
}

// Dipanggil di setiap halaman yang butuh login; role dicek di server,
// jadi anggota tidak bisa membuka halaman bendahara walau tahu URL-nya.
function require_login(string $role): array
{
    if (!is_logged_in($role)) {
        redirect('login.php');
    }
    return ['id' => $_SESSION['user_id'], 'nama' => $_SESSION['nama']];
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

// Halaman login.php di kedua website cukup memanggil fungsi ini
function handle_login_page(string $role): void
{
    start_session($role);
    if (is_logged_in($role)) {
        redirect('index.php');
    }

    $error = null;
    if (is_post()) {
        check_csrf();
        if (attempt_login($role, trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
            redirect('index.php');
        }
        $error = 'Username atau password salah.';
    }
    render_login($role, $error);
}
