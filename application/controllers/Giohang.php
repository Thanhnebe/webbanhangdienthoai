<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Giohang extends CI_Controller {
	// Hàm khởi tạo
    function __construct() {
        parent::__construct();
        $this->load->model('frontend/Morder');
        $this->load->model('frontend/Mproduct');
        $this->load->model('frontend/Morderdetail');
        $this->load->model('frontend/Mcustomer');
        $this->load->model('frontend/Mcategory');
        $this->load->model('frontend/Mconfig');
        $this->load->model('frontend/Mdistrict');
        $this->load->model('frontend/Mprovince');
        $this->data['com']='giohang';

    }
    
    public function index(){



        $this->data['title']='DanhVinhShop - Giỏ hàng của bạn';
        $this->data['view']='index';
        $this->load->view('frontend/layout',$this->data);

    }
    function check_mail(){
        $email = $this->input->post('email');
        if($this->Mcustomer->customer_detail_email($email))
        {
            $this->form_validation->set_message(__FUNCTION__, 'Email đã đã là thành viên, Vui lòng đăng nhập hoặc nhập Email khác !');
            return FALSE;
        }
        return TRUE;
    }
   
    public function info_order(){
        $this->load->library('session');
        $this->load->helper('string');
        $this->load->library('email');
        $this->load->library('form_validation');
        $this->load->config('vnpay');
    
        $d=getdate();
        $today=$d['year']."/".$d['mon']."/".$d['mday']." ".$d['hours'].":".$d['minutes'].":".$d['seconds'];
    
        if(!$this->session->userdata('sessionKhachHang'))
        {
            $this->form_validation->set_rules('email', 'Địa chỉ email', 'required|is_unique[db_customer.email]');
        }
        $this->form_validation->set_rules('phone', 'Số điện thoại', 'required');
        $this->form_validation->set_rules('name', 'Họ và tên', 'required|min_length[3]');
        $this->form_validation->set_rules('address', 'Địa chỉ', 'required');
        $this->form_validation->set_rules('city', 'Tỉnh thành', 'required');
        $this->form_validation->set_rules('DistrictId', 'Quận huyện', 'required');
    
        $priceShip = $this->Mconfig->config_price_ship();
    
        if($this->form_validation->run() == TRUE){
            // Tính tiền đơn hàng
            $money = 0;
            if($this->session->userdata('cart')){
                $data = $this->session->userdata('cart');
                foreach ($data as $key => $value) {
                    $row = $this->Mproduct->product_detail_id($key);
                    $total = ($row['price_sale'] > 0) ? $row['price_sale'] * $value : $row['price'] * $value;
                    $money += $total;
                }
            }
    
            $idCustomer = null;
            if($this->session->userdata('sessionKhachHang')){
                $emailtemp = $this->session->userdata('email');
                $info = $this->session->userdata('sessionKhachHang');
                $idCustomer = $info['id'];
            }else{
                $emailtemp = $_POST['email'];
            }
    
            if(!$this->session->userdata('sessionKhachHang')){
                $datacustomer = array(
                    'fullname' => $_POST['name'],
                    'phone' => $_POST['phone'],
                    'email' => $emailtemp,
                    'created' => $today,
                    'status' => 1,
                    'trash' => 1
                );
                $this->Mcustomer->customer_insert($datacustomer);
                $row = $this->Mcustomer->customer_detail_email($_POST['email']);
                $this->session->set_userdata('info-customer', $row);
                $info = $this->session->userdata('info-customer');
                if($info['id']){
                    $idCustomer = $info['id'];
                    $this->session->set_userdata('id-info-customer', $idCustomer);
                }
            }
    
            // Kiểm tra mã giảm giá
            $coupon = ($this->session->userdata('coupon_price')) ? $this->session->userdata('coupon_price') : 0;
            if($this->session->userdata('coupon_price')){
                $idcoupon = $this->session->userdata('id_coupon_price');
                $amount_number_used = $this->Mconfig->get_amount_number_used($idcoupon);
                $mycoupon = array('number_used' => $amount_number_used + 1);
                $this->Mconfig->coupon_update($mycoupon, $idcoupon);
            }
    
            $provinceId = $_POST['city'];
            $districtId = $_POST['DistrictId'];
            $mydata = array(
                'orderCode' => random_string('alnum', 8),
                'customerid' => $idCustomer,
                'orderdate' => $today,
                'fullname' => $_POST['name'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'money' => $money + $priceShip - $coupon,
                'price_ship' => $priceShip,
                'coupon' => $coupon,
                'province' => $provinceId,
                'district' => $districtId,
                'trash' => 1,
                'status' => 0
            );
    
            // Insert to db_order
            $this->Morder->order_insert($mydata);
    
            // Xóa session coupon sau khi lưu đơn hàng
            $this->session->unset_userdata('id_coupon_price');
            $this->session->unset_userdata('coupon_price');
    
            // Insert to db_orderdetail
            $order_detail = $this->Morder->order_detail_customerid($idCustomer);
            $orderid = $order_detail['id'];
            if($this->session->userdata('cart')){
                $val = $this->session->userdata('cart');
                foreach ($val as $key => $value){
                    $row = $this->Mproduct->product_detail_id($key);
                    $price = ($row['price_sale'] > 0) ? $row['price_sale'] : $row['price'];
                    $data = array(
                        'orderid' => $orderid,
                        'productid' => $key,
                        'price' => $price,
                        'count' => $value,
                        'trash' => 1,
                        'status' => 1
                    );
                    $this->Morderdetail->orderdetail_insert($data);
                }
            }
            
            // Xử lý nếu chọn thanh toán VNPay
            if (isset($_POST['dathangvnpay'])) {
                $this->load->helper('url');
                $vnp_TmnCode = $this->config->item('vnp_TmnCode'); // Mã website tại VNPAY 
                $vnp_HashSecret = $this->config->item('vnp_HashSecret'); // Chuỗi bí mật
                $vnp_Url = $this->config->item('vnp_Url'); // URL thanh toán của VNPAY
                $vnp_Returnurl = $this->config->item('vnp_Returnurl'); // URL trả về sau khi thanh toán
    
                $vnp_TxnRef = $orderid; // Mã đơn hàng. Trong thực tế, bạn lấy mã này từ DB
                $vnp_OrderInfo = 'Thanh toán đơn hàng tại DanhVinhShop';
                $vnp_OrderType = 'billpayment';
                $vnp_Amount = ($money + $priceShip - $coupon) * 100;
                $vnp_Locale = 'vn';
                $vnp_BankCode = '';
                $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
                
                $inputData = array(
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => $vnp_TmnCode,
                    "vnp_Amount" => $vnp_Amount,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $vnp_IpAddr,
                    "vnp_Locale" => $vnp_Locale,
                    "vnp_OrderInfo" => $vnp_OrderInfo,
                    "vnp_OrderType" => $vnp_OrderType,
                    "vnp_ReturnUrl" => $vnp_Returnurl,
                    "vnp_TxnRef" => $vnp_TxnRef,
                );
    
                if ($vnp_BankCode != "") {
                    $inputData['vnp_BankCode'] = $vnp_BankCode;
                }
    
                ksort($inputData);
                $query = "";
                $i = 0;
                $hashdata = "";
                foreach ($inputData as $key => $value) {
                    if ($i == 1) {
                        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                    } else {
                        $hashdata .= urlencode($key) . "=" . urlencode($value);
                        $i = 1;
                    }
                    $query .= urlencode($key) . "=" . urlencode($value) . '&';
                }
    
                $vnp_Url = $vnp_Url . "?" . $query;
                if (isset($vnp_HashSecret)) {
                    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
                }
    
                redirect($vnp_Url);
            }
    
            $this->session->unset_userdata('cart');
            $this->session->unset_userdata('total');
            $this->session->unset_userdata('coupon');
    
            redirect('/thank-you', 'refresh');
        } else {
            $this->data['view']='info-order';
            $this->load->view('frontend/layout',$this->data);
        }
    }
    public function vnpay_return() {
        $inputData = $this->input->get();
        $vnp_HashSecret = $this->config->item('vnp_HashSecret');
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
    
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $hashData = '';
        foreach ($inputData as $key => $value) {
            $hashData .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $hashData = rtrim($hashData, '&');
    
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
    
        if ($secureHash == $vnp_SecureHash) {
            if ($inputData['vnp_ResponseCode'] == '00') {
                // Cập nhật trạng thái đơn hàng thành công
                $orderid = $inputData['vnp_TxnRef'];
                $mydata = array('status' => 1);
                $this->Morder->order_update($mydata, $orderid);
    
                $this->session->set_flashdata('message', 'Giao dịch thành công!');
                redirect('/thank-you');
            } else {
                $this->session->set_flashdata('message', 'Giao dịch không thành công!');
                redirect('/giohang');
            }
        } else {
            $this->session->set_flashdata('message', 'Chữ ký không hợp lệ!');
            redirect('/giohang');
        }
    }
    
    public function thankyou(){
        if($this->session->userdata('info-customer')||$this->session->userdata('sessionKhachHang')){
            if($this->session->userdata('sessionKhachHang')){
                $val = $this->session->userdata('sessionKhachHang');
            }else{
                $val = $this->session->userdata('info-customer');
            }
            $list = $this->Morder->order_detail_customerid($val['id']);
            $data = array(
                'order' => $list,
                'customer' => $val,
                'orderDetail' => $this->Morderdetail->orderdetail_order_join_product($list['id']),
                'province' => $this->Mprovince->province_name($list['province']),
                'district' => $this->Mdistrict->district_name($list['district']),
                'priceShip' => $this->Mconfig->config_price_ship(),
                'coupon' => $list['coupon'],

            );
            $this->data['customer']=$val;
            $this->data['get']=$list;
            $this->load->library('email');
            $this->load->library('parser');
            $this->email->clear();
            $config['protocol']    = 'smtp';
            $config['smtp_host']    = 'ssl://smtp.gmail.com';
            $config['smtp_port']    = '465';
            $config['smtp_timeout'] = '7';
            $config['smtp_user']    = 'hmai.my03@gmail.com';
            $config['smtp_pass']    = 'wyxqqggdgahvoyrd';
            // mk trên la mat khau dung dung cua gmail, có thể dùng gmail hoac mat khau. Tao mat khau ung dung de bao mat tai khoan
            $config['charset']    = 'utf-8';
            $config['newline']    = "\r\n";
            $config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';
            $config['validation'] = TRUE;   
            $this->email->initialize($config);
            $this->email->from('hmai.my03@gmail.com', 'DanhVinhShop');
            $list = array($val['email']);
            $this->email->to($list);
            $this->email->subject('DanhVinhShop');
            $body = $this->load->view('frontend/modules/email',$data,TRUE);
            $this->email->message($body); 
            $this->email->send();

            $datax = array('email' => '');
            $idx= $this->session->userdata('id-info-customer');
            $this->Mcustomer->customer_update($datax,$idx);
            $this->session->unset_userdata('id-info-customer','money_check_coupon');
        }   
        $this->data['title']='DanhVinhShop - Kết quả đơn hàng';
        $this->data['view']='thankyou';
        $this->load->view('frontend/layout',$this->data);
    }

    public function district(){
        $this->load->library('session');
        $id=$_POST['provinceid'];
        $list = $this->Mdistrict->district_provinceid($id);
        $html="<option value =''>--- Chọn quận huyện ---</option>";
        foreach ($list as $row) 
        {
            $html.='<option value = '.$row["id"].'>'.$row["name"].'</option>';
        }
        echo json_encode($html);
    }
    public function coupon(){
        $d=getdate();
        $today=$d['year']."-".$d['mon']."-".$d['mday'];
        $html='';
        if($this->session->userdata('coupon_price')){
         $html.='<p>Mỗi đơn hàng chỉ áp dụng 1 Mã giảm giá !!</p>';
     }else{
        if(empty($_POST['code']))
        {
            $html.='<p>Vui lòng nhập Mã giảm giá nếu có !!</p>';
        }
        else
        {
            // KIỂM TRA SỐ TIỀN TRONG GIỎ HÀNG
            $money=0;
            if($this->session->userdata('cart')){
                $data=$this->session->userdata('cart');
                foreach ($data as $key => $value) {
                    $row = $this->Mproduct->product_detail_id($key);
                    $total=0;
                    if($row['price_sale'] > 0){
                        $total=$row['price_sale']*$value;
                    }else{
                        $total=$row['price'] * $value;
                    }
                    $money+=$total;
                }
            }
            //
            // KIỂM TRA MÃ GIẢM GIÁ CÓ TỒN TẠI KO
            $coupon = $_POST['code'];
            $getcoupon = $this->Mconfig->get_config_coupon_discount($coupon);
            if(empty($getcoupon)) {
               $html.='<p>Mã giảm giá không tồn tại!</p>';
           }
           foreach ($getcoupon as $value) {
            if($value['code'] == $coupon)
            {
                if (strtotime($value['expiration_date']) <= strtotime($today)){
                    $html.='<p>Mã giảm giá '.$value['code'].' đã hết hạn sử dụng từ ngày '.$value['expiration_date'].' !</p>';
                }else if($value['limit_number'] -$value['number_used'] == 0){
                    $html.='<p>Mã giảm giá '.$value['code'].' đã hết số lần nhập !</p>';
                }else if($value['payment_limit'] >= $money ){
                    $html.='<p> Mã giảm giá này chỉ áp dụng cho đơn hàng từ '.number_format($value['payment_limit']).' đ trở lên !</p>';
                }else{
                    $html.='<script>document.location.reload(true);</script> <p>Mã giảm giá '.$value['code'].' đã được kích hoạt !</p>';
                    $this->session->set_userdata('coupon_price',$value['discount']);
                    $this->session->set_userdata('id_coupon_price',$value['id']);
                }
            }
        }
    }

}
echo json_encode($html);
}
public function removecoupon(){
    $html='<script>document.location.reload(true);</script>';
    $this->session->unset_userdata('coupon_price');
    $this->session->unset_userdata('id_coupon_price');
    echo json_encode($html);
}
}
// email trang thankyou bị sai
