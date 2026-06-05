<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    public function generate(): Response
    {
        $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars I/1/0/O
        $code   = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        session(['captcha_code' => $code]);

        $w = 160; $h = 48;
        $img = imagecreatetruecolor($w, $h);

        // Background
        $bg = imagecolorallocate($img, 240, 244, 248);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        // Noise lines
        for ($i = 0; $i < 6; $i++) {
            $lc = imagecolorallocate($img, random_int(160, 210), random_int(160, 210), random_int(160, 210));
            imageline($img, random_int(0,$w), random_int(0,$h), random_int(0,$w), random_int(0,$h), $lc);
        }

        // Dots
        for ($i = 0; $i < 60; $i++) {
            $dc = imagecolorallocate($img, random_int(150, 220), random_int(150, 220), random_int(150, 220));
            imagesetpixel($img, random_int(0,$w), random_int(0,$h), $dc);
        }

        // Text
        $colors = [
            imagecolorallocate($img, 30, 60, 140),
            imagecolorallocate($img, 140, 30, 30),
            imagecolorallocate($img, 30, 110, 30),
            imagecolorallocate($img, 100, 30, 140),
        ];

        $font = 5; // built-in font size 5
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);
        $startX = ($w - strlen($code) * ($charW + 4)) / 2;

        for ($i = 0; $i < strlen($code); $i++) {
            $x = (int)($startX + $i * ($charW + 4));
            $y = (int)(($h - $charH) / 2) + random_int(-4, 4);
            imagechar($img, $font, $x, $y, $code[$i], $colors[$i % count($colors)]);
        }

        // Border
        $border = imagecolorallocate($img, 180, 190, 210);
        imagerectangle($img, 0, 0, $w - 1, $h - 1, $border);

        ob_start();
        imagepng($img);
        $imgData = ob_get_clean();
        imagedestroy($img);

        return response($imgData, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'no-cache']);
    }
}
