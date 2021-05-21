<?php
include "mongodb/mongodb.php";
include "mongodb/users.php";
include "mongodb/orders.php";
include "mongodb/products.php";
class banhang{
	
	private $link;
	private $mongoManager;
	
	public function connect($host,$user,$pass,$db)
	{
		$this->link=mysqli_connect($host,$user,$pass,$db) or die("Khong the ket noi");
		mysqli_query($this->link,"SET NAMES 'utf8'");
		
		$this->mongoManager= createMongoManagerInstance();
	}
	public function query($sl)
	{
		$result = ["type" => "sql", "result" => mysqli_query($this->link,$sl)];
		return $result;
	}
	
	public function fetch($kq)
	{
		if ($kq["type"] == "sql")
			return mysqli_fetch_assoc($kq["result"]);
		if ($kq["type"] == "mongo")
			return $kq["result"];
	}
	public function insert_id()
	{
		return mysqli_insert_id($this->link);
	}
	
	public function total($kq)
	{
		if ($kq["type"] == "sql")
			return(mysqli_num_rows($kq["result"]));
	}
	public function format_num($num)
	{
		return number_format($num,0,",",".");
	}
	
	public function getCL()
	{
		$kq=$this->query("select * from chungloai where AnHien=1 order by ThuTu");
		return $kq;
	}
	public function getLoaibyCL($idCL)
	{
		$kq=$this->query("select * from loaisp where idCL=$idCL and AnHien=1 order by ThuTu");
		return $kq;
	}
	public function spXemNhieu($so)
	{
		$kq=$this->query("select idDT, TenDT, Gia, urlHinh from dienthoai where AnHien=1 order by SoLanXem DESC limit 0,$so");
		return $kq;
	}
	
	public function spMoi($so)
	{
		$kq=$this->query("select idDT, TenDT, Gia, urlHinh from dienthoai where AnHien=1 order by idDT DESC limit 0,$so");
		return $kq;
	}
	public function spbyId($id)
	{
		$kq=$this->query("select * from dienthoai where idDT=$id and AnHien=1");
		$d=$this->fetch($kq);
		return $d;
	}
	
	public function hinhbySp($id)
	{
		$kq=$this->query("select * from hinh where idDT=$id and anhien=1 order by stt");
		return $kq;
	}
	
	public function spbyLoai($id)
	{
		$kq=$this->query("select idDT, TenDT, Gia, urlHinh from dienthoai where idLoai=$id and anhien=1 order by idDT DESC");
		return $kq;
	}
	
	public function loaibyId($id)
	{
		$kq=$this->query("select * from loaisp where idLoai=$id and AnHien=1");
		$d=$this->fetch($kq);
		return $d;
	}
	
	public function checkLogin($email,$pass)
	{
		$pass=md5($pass);
		$kq=$this->query("select * from users where Email='$email' and Password='$pass' and active=0");
		return $kq;
	}
	
	public function getAccount($id)
	{
		$user = new Users($this->mongoManager, $id);
		$user->fetchInformation();
		$account = [];
		foreach (array_keys($user->getInformation()["information"]) as $property)
			$account[$property] = $user->getInformation()["information"][$property]["value"];
		return $account;
	}
	public function getDhbyUser($id)
	{
		$kq=$this->query("select * from donhang where idUser=$id order by idDH");
		return $kq;
	}
	
	public function getChiTietDH($id)
	{
		$kq=$this->query("select * from donhang where idDH=$id");
		return $kq;
	}
		
	
}
?>
