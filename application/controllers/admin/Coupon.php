<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Coupon extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('backend/Mcoupon');
		$this->load->model('backend/Morders');
        //$this->load->model('backend/Muser');
		if(!$this->session->userdata('sessionadmin'))
		{
			redirect('admin/user/login','refresh');
		}
		$this->data['user']=$this->session->userdata('sessionadmin');
		$this->data['com']='coupon';
	}

	public function index()
	{
		$d=getdate();
        $today=$d['year']."-".$d['mon']."-".$d['mday'];
		// Tự động lấy lên danh sách mã giảm giá đã tạo tự động, kt ngày hết hạn mã đó, xóa đi những mã hết hạn
		$list_coupon_aotu_check = $this->Mcoupon->coupon_auto_all();
		foreach ($list_coupon_aotu_check as $row) {
			if(strtotime($row['expiration_date']) <= strtotime($today) || $row['number_used'] ==1){
				$this->Mcoupon->coupon_delete($row['id']);
			}
		}
		//
		$this->load->library('phantrang');
		$limit=10;
		$current=$this->phantrang->PageCurrent();
		$first=$this->phantrang->PageFirst($limit, $current);
		$total=$this->Mcoupon->coupon_count();
		$this->data['strphantrang']=$this->phantrang->PagePer($total, $current, $limit, $url='admin/coupon');
		$this->data['list']=$this->Mcoupon->coupon_all($limit,$first);
		$this->data['view']='index';
		$this->data['title']='Danh sách mã giảm giá';
		$this->load->view('backend/layout', $this->data);
	}

	public function insert()
{
    $user_role = $this->session->userdata('sessionadmin');
    if ($user_role['role'] == 2) {
        redirect('admin/E403/index', 'refresh');
    }

    $d = getdate();
    $today = $d['year'] . "-" . str_pad($d['mon'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($d['mday'], 2, '0', STR_PAD_LEFT) . " " . str_pad($d['hours'], 2, '0', STR_PAD_LEFT) . ":" . str_pad($d['minutes'], 2, '0', STR_PAD_LEFT) . ":" . str_pad($d['seconds'], 2, '0', STR_PAD_LEFT);

    $this->load->library('form_validation');
    $this->load->library('session');

    // Các quy tắc xác thực cho form
    $this->form_validation->set_rules('discount', 'Số tiền giảm giá', 'required');
    $this->form_validation->set_rules('limit_number', 'Số lần giới hạn nhập', 'required');
    $this->form_validation->set_rules('code', 'Tên mã giảm giá', 'required|is_unique[db_discount.code]|min_length[5]|max_length[10]');
    $this->form_validation->set_rules('start_date', 'Ngày bắt đầu', 'required');
    $this->form_validation->set_rules('end_date', 'Ngày kết thúc khuyến mãi', 'required');

    if ($this->form_validation->run() == TRUE) 
    {
        // Lấy ngày bắt đầu và ngày kết thúc từ POST data
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Kiểm tra trạng thái dựa trên ngày hiện tại, ngày bắt đầu và ngày kết thúc
        if ($today >= $start_date && $today <= $end_date) {
            $status = 1; // Có hiệu lực
        } else {
            $status = 0; // Không có hiệu lực
        }

        $mydata = array(
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'limit_number' => $_POST['limit_number'],
            'payment_limit' => $_POST['payment_limit'],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'description' => $_POST['description'],
            'created' => $today,
            'orders' => 1,
            'trash' => 1,
            'status' => $status // Đặt trạng thái dựa trên kiểm tra
        );

        // Chèn dữ liệu vào cơ sở dữ liệu
        $this->Mcoupon->coupon_insert($mydata);
        $this->session->set_flashdata('success', 'Thêm mã giảm giá thành công');
        redirect('admin/coupon', 'refresh');
    } 
    else 
    {
        $this->data['view'] = 'insert';
        $this->data['title'] = 'Thêm Mã giảm giá mới';
        $this->load->view('backend/layout', $this->data);
    }
}


public function update($id)
{
    $user_role = $this->session->userdata('sessionadmin');
    if ($user_role['role'] == 2) {
        redirect('admin/E403/index', 'refresh');
    }

    $this->data['row'] = $this->Mcoupon->coupon_detail($id);
    $this->load->library('form_validation');
    $this->load->library('session');

    // Các quy tắc xác thực cho form
    $this->form_validation->set_rules('discount', 'Số tiền giảm giá', 'required');
    $this->form_validation->set_rules('code', 'Tên mã giảm giá', 'required|min_length[6]|max_length[10]');
    $this->form_validation->set_rules('start_date', 'Ngày bắt đầu', 'required');
    $this->form_validation->set_rules('end_date', 'Ngày kết thúc khuyến mãi', 'required');

    if ($this->form_validation->run() == TRUE) 
    {
        // Lấy ngày bắt đầu và ngày kết thúc từ POST data
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Lấy ngày hiện tại
        $d = getdate();
        $today = $d['year'] . "-" . str_pad($d['mon'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($d['mday'], 2, '0', STR_PAD_LEFT) . " " . str_pad($d['hours'], 2, '0', STR_PAD_LEFT) . ":" . str_pad($d['minutes'], 2, '0', STR_PAD_LEFT) . ":" . str_pad($d['seconds'], 2, '0', STR_PAD_LEFT);

        // Kiểm tra trạng thái dựa trên ngày hiện tại, ngày bắt đầu và ngày kết thúc
        if ($today >= $start_date && $today <= $end_date) {
            $status = 1; // Có hiệu lực
        } else {
            $status = 0; // Không có hiệu lực
        }

        $mydata = array(
            'code' => $_POST['code'],
            'discount' => $_POST['discount'],
            'limit_number' => $_POST['limit_number'],
            'payment_limit' => $_POST['payment_limit'],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'description' => $_POST['description'],
            'trash' => 1,
            'status' => $status // Đặt trạng thái dựa trên kiểm tra
        );

        $this->Mcoupon->coupon_update($mydata, $id);
        $this->session->set_flashdata('success', 'Cập nhật mã giảm giá thành công');
        redirect('admin/coupon/', 'refresh');
    } 

    $this->data['view'] = 'update';
    $this->data['title'] = 'Cập nhật mã giảm giá';
    $this->load->view('backend/layout', $this->data);
}



	public function status($id)
	{
		$row=$this->Mcoupon->coupon_detail($id);
		$status=($row['status']==1)?0:1;
		$mydata= array('status' => $status);
		$this->Mcoupon->coupon_update($mydata, $id);
		$this->session->set_flashdata('success', 'Cập nhật trạng thái thành công');
		redirect('admin/coupon/','refresh');
	}

	public function trash($id)
	{
		$mydata= array('trash' => 0);
		$this->Mcoupon->coupon_update($mydata, $id);
		$this->session->set_flashdata('success', 'Xóa mã giảm giá vào thùng rác thành công');
		redirect('admin/coupon','refresh');
	}

	public function recyclebin()
	{
		$this->load->library('phantrang');
		$limit=10;
		$current=$this->phantrang->PageCurrent();
		$first=$this->phantrang->PageFirst($limit, $current);
		$total=$this->Mcoupon->coupon_trash_count();
		$this->data['strphantrang']=$this->phantrang->PagePer($total, $current, $limit, $url='admin/coupon/recyclebin');
		$this->data['list']=$this->Mcoupon->coupon_trash($limit, $first);
		$this->data['view']='recyclebin';
		$this->data['title']='Thùng rác mã giảm giá';
		$this->load->view('backend/layout', $this->data);
	}

	public function restore($id)
	{
		$this->Mcoupon->coupon_restore($id);
		$this->session->set_flashdata('success', 'Khôi phục mã giảm giá thành công');
		redirect('admin/coupon/recyclebin','refresh');
	}
	public function delete($id)
	{
		$this->Mcoupon->coupon_delete($id);
		$this->session->set_flashdata('success', 'Xóa mã giảm giá thành công');
		redirect('admin/coupon/recyclebin','refresh');
	}

}