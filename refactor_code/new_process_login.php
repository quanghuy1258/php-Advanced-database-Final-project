<?php 
include("connect.php");
if(isset($_POST['email'],$_POST['pass']))
{
	$kq=$bh->checkLogin($_POST['email'],$_POST['pass']);
	if($kq>0)
	{
		$d=$bh->fetch($kq);
		$_SESSION['id']=$d['idUser'];
		$_SESSION['hoten']=$d['HoTen'];
		header("location:account.php");
	}
	else header("location:login.php");
}
if(isset($_GET['thoat']))
{
	unset($_SESSION['id']);
	unset($_SESSION['hoten']);
	header("location:index.php");
}

if(isset($_GET['additem']))
{
	$id=intval($_GET['additem']);
	
	if(isset($_GET['qty']))$soluong=$_GET['qty'];else $soluong=1;
	
	$kt=0;
	for($i=0;$i<count($_SESSION['giohang']);$i++)
	{
		if($id==$_SESSION['giohang'][$i]['idDT'])
		{
			$_SESSION['giohang'][$i]['soluong']+=$soluong;
			$kt=1;
			break;
		}
		
	}
	
		
	if($kt==0)
	{
		$k=count($_SESSION['giohang']);
		
		$d=$bh->spbyId($id);
		
		$_SESSION['giohang'][$k]['idDT']=$d['idDT'];
		$_SESSION['giohang'][$k]['TenDT']=$d['TenDT'];
		$_SESSION['giohang'][$k]['Gia']=$d['Gia'];
		$_SESSION['giohang'][$k]['urlHinh']=$d['urlHinh'];
		$_SESSION['giohang'][$k]['soluong']=$soluong;
	}
	header("location:".$_SERVER['HTTP_REFERER']);
}

if(isset($_GET['delitem']))
{
	for($i=$_GET['delitem'];$i<count($_SESSION['giohang'])-1;$i++)
	{
		$j=$i+1;
		$_SESSION['giohang'][$i]=$_SESSION['giohang'][$j];
	}
	
	$k=count($_SESSION['giohang'])-1;
	unset($_SESSION['giohang'][$k]);
	header("location:".$_SERVER['HTTP_REFERER']);
}

if(isset($_POST['capnhat_gh']))
{
	for($i=0;$i<count($_SESSION['giohang']);$i++)
	{
		$_SESSION['giohang'][$i]['soluong']=$_POST['qty'.$i];
	}
	header("location:".$_SERVER['HTTP_REFERER']);
}

if(isset($_POST['dathang']))
{
	if(isset($_SESSION['id']))$id=$_SESSION['id']; else $id=0;
	$ngay=date("Y-m-d h:i:s",time());
	$sl="insert into donhang(idUser, ThoiDiemDatHang, TenNguoiNhan, DTNguoiNhan, DiaChi, TongTien, Email) values($id,'$ngay','{$_POST['hoten']}','{$_POST['sdt']}','{$_POST['diachi']}',{$_POST['dathang']},'{$_POST['email']}')";
	if($bh->query($sl))
	{
		$iddh=$bh->insert_id();
		for($i=0;$i<count($_SESSION['giohang']);$i++)
		{
			$sl="insert into donhangchitiet values(NULL,$iddh,{$_SESSION['giohang'][$i]['idDT']}, '{$_SESSION['giohang'][$i]['TenDT']}',{$_SESSION['giohang'][$i]['soluong']}, {$_SESSION['giohang'][$i]['Gia']})";
			$bh->query($sl);
			
		}
		unset($_SESSION['giohang']);
		echo "<script>alert('Đặt hàng thành công!');location.href='index.php';</script>";
		
	}
}
?>
