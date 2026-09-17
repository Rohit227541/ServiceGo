<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$plan = trim($_POST['plan'] ?? 'General Enquiry');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Name, phone and email are required']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid email']);
    exit;
}

$to = 'Rohitkodexive50@gmail.com';
// For testing you can add CC: $headers .= 'Cc: rohit@example.com' . "\r\n";
$subject = 'New ServiceGo Lead - ' . $plan;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$date = date('d-m-Y H:i:s');

$body = "New Lead from ServiceGo Website\n";
$body .= "--------------------------------\n";
$body .= "Name: $name\n";
$body .= "Phone: $phone\n";
$body .= "Email: $email\n";
$body .= "Plan: $plan\n";
$body .= "Message: $message\n";
$body .= "--------------------------------\n";
$body .= "Date: $date\n";
$body .= "IP: $ip\n";
$body .= "User Agent: $ua\n";

$headers = "From: ServiceGo Website <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'kodexive.com') . ">\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Try to send email
$sent = @mail($to, $subject, $body, $headers);

// Also log to CSV for backup even if mail fails
$logDir = __DIR__ . '/leads';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$csvFile = $logDir . '/leads.csv';
$isNew = !file_exists($csvFile);
$fp = @fopen($csvFile, 'a');
if ($fp) {
    if ($isNew) { fputcsv($fp, ['date','name','phone','email','plan','message','ip','user_agent']); }
    fputcsv($fp, [$date,$name,$phone,$email,$plan,$message,$ip,$ua]);
    fclose($fp);
}
// Also save as JSON log
$jsonFile = $logDir . '/leads.json';
$existing = [];
if (file_exists($jsonFile)) { $existing = json_decode(file_get_contents($jsonFile), true) ?: []; }
$existing[] = ['date'=>$date,'name'=>$name,'phone'=>$phone,'email'=>$email,'plan'=>$plan,'message'=>$message,'ip'=>$ip,'ua'=>$ua];
@file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($sent) {
    echo json_encode(['ok'=>true]);
} else {
    // Even if mail() fails (common on localhost/XAMPP), we still return ok because logged
    // In production configure SMTP (e.g., use PHPMailer with SMTP)
    echo json_encode(['ok'=>true,'warning'=>'mail() not configured, but lead saved to leads/leads.csv','mail_sent'=>false]);
}
