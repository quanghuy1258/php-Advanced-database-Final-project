<?php
include "mongodb.php";
include "products.php";
$mongoManager = createMongoManagerInstance();

$mysql = mysqli_connect("localhost", "phpmyadmin", "cntt19@fit2019", "banhang");
mysqli_query($mysql,"SET NAMES 'utf8'");
$query = mysqli_query($mysql, "select * from dienthoai");
while ($result=mysqli_fetch_assoc($query)) {
  Products::newProduct($mongoManager, $result["idDT"], $result["TenDT"]);
  $product = new Products($mongoManager, $result["idDT"]);
  $product->fetchDetail();
  $properties = ["idLoai" => "id loai",
                 "idCL" => "id chung loai",
                 "TenDT" => "ten dien thoai",
                 "MoTa" => "mo ta",
                 "Gia" => "gia",
                 "urlHinh" => "url hinh",
                 "baiviet" => "bai viet",
                 "SoLanXem" => "so lan xem",
                 "AnHien" => "an hien"];
  foreach ($properties as $key => $value)
    $product->addProperty([], $key, $value, $result[$key]);
}
?>
