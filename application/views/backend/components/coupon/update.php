<?php echo form_open_multipart('admin/coupon/update/'.$row['id']); ?>
<div class="content-wrapper">
	<form action="<?php echo base_url() ?>admin/coupon/update.html" enctype="multipart/form-data" method="POST" accept-charset="utf-8">
		<section class="content-header">
			<h1><i class="glyphicon glyphicon-text-background"></i> Cập nhật mã giảm giá</h1>
			<div class="breadcrumb">
				<button type = "submit" class="btn btn-primary btn-sm">
					<span class="glyphicon glyphicon-floppy-save"></span>
					Lưu[Cập nhật]
				</button>
				<a class="btn btn-primary btn-sm" href="admin/coupon" role="button">
					<span class="glyphicon glyphicon-remove do_nos"></span> Thoát
				</a>
			</div>
		</section>
		<!-- Main content -->
		<section class="content">
			<div class="row">
				<div class="col-md-12">
					<div class="box" id="view">
						<div class="box-body">
							<div class="col-md-6">
								<div class="form-group">
									<label>Mã giảm giá</label>
									<input type="text" class="form-control" name="code" style="width:100%" placeholder="Mã giảm giá" value="<?php echo $row['code'] ?>">
									<div class="error" id="password_error"><?php echo form_error('code')?></div>
								</div>
								<div class="form-group">
									<label>Số tiền giảm giá</label>
									<input type="number" class="form-control" name="discount" style="width:100%" placeholder="Số tiền giảm giá" value="<?php echo $row['discount'] ?>">
									<div class="error" id="password_error"><?php echo form_error('discount')?></div>
								</div>
								<div class="form-group">
									<label>Tổng số lần nhập</label>
									<input type="number" class="form-control" name="limit_number" style="width:100%" placeholder="Số lần giới hạn nhập" value="<?php echo $row['limit_number'] ?>">
									<div class="error" id="password_error"><?php echo form_error('limit_number')?></div>
								</div>
								<div class="form-group">
									<label>Số lần đã nhập</label>
									<input type="number" class="form-control" name="number_used" style="width:100%" value="<?php echo $row['number_used'] ?>" disabled>
									<div class="error" id="password_error"><?php echo form_error('number_used')?></div>
								</div>
								<div class="form-group">
									<?php $number_rest = $row['limit_number'] - $row['number_used']
									?>
									<label>Số lần còn lại</label>
									<input type="text" class="form-control" style="width:100%" placeholder="Số lần giới hạn nhập" value="<?php echo  $number_rest ?>" disabled>
								</div>
								
							</div>
							<div class="col-md-6">
							<div class="form-group">
                                    <label>Ngày bắt đầu</label>
                                    <input type="date" class="form-control" name="start_date" style="width:100%" required>
                                </div>
                                <div class="form-group">
                                    <label>Ngày kết thúc khuyến mãi</label>
                                    <input type="date" class="form-control" name="end_date" style="width:100%" required>
                                </div>
								<div class="form-group">
									<label>Mô tả ngắn</label>
									<textarea name="description" class="form-control" value=""><?php echo $row['description'] ?></textarea>
								</div>
								

							</div>
						</div>
					</div><!-- /.box -->
				</div>
				<!-- /.col -->
			</div>
			<!-- /.row -->
		</section>
	</form>
	<!-- /.coupon -->
</div><!-- /.coupon-wrapper -->