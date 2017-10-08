<?php

function generate_name($length = 20)
{
    $characters       = '0123456789abcdefghijklmnopqrstuvwxyz-';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function img_exists($url)
{
    $headers = get_headers($url);
    if (stripos($headers[0], "200 OK") && exif_imagetype($url)) {
        return true;

    } else {
        return false;
    }
}

function connect_to_db()
{
    $servername = "localhost";
    $username = "root";
    $password = Null;
    $dbname = "imapp";
    return new mysqli($servername, $username, $password, $dbname);    
}

function save_to_db($id, $format, $width, $height)
{
    $conn = connect_to_db();
    $sql = "INSERT INTO img_log (id, format, width, height)
VALUES ('$id', '$format', '$width', '$height')";
    $conn->query($sql);
    $conn->close();
}

function crop_image($image, $startX, $startY, $width, $height)
{
    $img = new Imagick();
    $img->readImageFile($image);
    $img->cropImage($width, $height, $startX, $startY);
    return $img;
}

function resize_image($image, $width, $height, $filterType)
{
    $img = new Imagick();
    $img->readImageFile($image);
    $img->resizeImage($width, $height, $filterType, 1);

    return $img;
}