<?php
session_start();

// Create a blank image
$image = imagecreatetruecolor(200, 50);

// Set background color
$bgColor = imagecolorallocate($image, 255, 255, 255);
imagefilledrectangle($image, 0, 0, 200, 50, $bgColor);

// Generate random text
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
$captchaText = '';
for ($i = 0; $i < 6; $i++) {
    $captchaText .= $chars[rand(0, strlen($chars) - 1)];
}

// Store the CAPTCHA in session
$_SESSION['captcha_code'] = $captchaText;

// Add text to image
$textColor = imagecolorallocate($image, 0, 0, 0);
$fonts = [
    __DIR__ . '/arial.ttf',
    __DIR__ . '/times.ttf',
    // Add more fonts if available
];

for ($i = 0; $i < strlen($captchaText); $i++) {
    $font = isset($fonts[$i % count($fonts)]) ? $fonts[$i % count($fonts)] : null;
    $angle = rand(-10, 10);
    $x = 20 + ($i * 30);
    $y = 35;
    $color = imagecolorallocate($image, rand(0, 150), rand(0, 150), rand(0, 150));
    
    imagettftext($image, 20, $angle, $x, $y, $color, $font, $captchaText[$i]);
}

// Add some noise
for ($i = 0; $i < 50; $i++) {
    $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imagesetpixel($image, rand(0, 200), rand(0, 50), $color);
}

// Output the image
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);