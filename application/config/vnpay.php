<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['vnp_TmnCode'] = 'AEOMFE0I'; // Mã website của bạn tại VNPAY
$config['vnp_HashSecret'] = 'IBYUDBQOUSNLGUUJMTGPYWSSCNHRWXHQ'; // Chuỗi bí mật
$config['vnp_Url'] = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'; // URL thanh toán VNPay
$config['vnp_Returnurl'] = 'http://localhost/DanhVinhShop/thank-you'; // URL trả về sau khi thanh toán
