<?php
// CORS headers - must be first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content type
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 获取表单数据
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$identity = isset($_POST['identity']) ? trim($_POST['identity']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$to_email = isset($_POST['to_email']) ? trim($_POST['to_email']) : 'info@chihuimandarin.com';

// 记录接收到的数据用于调试
error_log("Contact form submission received:");
error_log("Name: " . $name);
error_log("Email: " . $email);
error_log("Phone: " . $phone);
error_log("Identity: " . $identity);
error_log("Course: " . $course);
error_log("Message: " . $message);
error_log("To Email: " . $to_email);

// 验证必填字段
if (empty($name) || empty($email) || empty($phone) || empty($identity)) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields are missing']);
    exit;
}

// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// 准备邮件内容
$subject = "New Contact Form Submission from " . $name;

$email_body = "
<html>
<head>
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #07284b; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #07284b; }
        .value { margin-top: 5px; }
        .footer { background-color: #9f2122; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>CHIHUI Mandarin - New Contact Form Submission</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div class='value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Phone:</div>
                <div class='value'>" . htmlspecialchars($phone) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Identity:</div>
                <div class='value'>" . htmlspecialchars($identity) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Course Interest:</div>
                <div class='value'>" . htmlspecialchars($course) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the CHIHUI Mandarin website contact form.</p>
            <p>Reply to: " . htmlspecialchars($email) . "</p>
        </div>
    </div>
</body>
</html>
";

// 邮件头设置
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: ' . $name . ' <' . $email . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
);

// 配置sendmail路径（Apache 2.4 + Stellar18）
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i');

// 发送邮件
$mail_sent = mail($to_email, $subject, $email_body, implode("\r\n", $headers));

if ($mail_sent) {
    // 记录成功日志
    error_log("Email sent successfully to: " . $to_email . " from: " . $email);
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    // 记录错误日志
    $error_msg = "Failed to send email to: " . $to_email . " from: " . $email;
    error_log($error_msg);
    
    // 获取更详细的错误信息
    $last_error = error_get_last();
    if ($last_error) {
        error_log("Last PHP error: " . $last_error['message']);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to send email',
        'debug' => [
            'mail_function' => function_exists('mail') ? 'Available' : 'Not available',
            'sendmail_path' => ini_get('sendmail_path'),
            'smtp_host' => ini_get('SMTP'),
            'last_error' => $last_error ? $last_error['message'] : 'No error details'
        ]
    ]);
}
?>
