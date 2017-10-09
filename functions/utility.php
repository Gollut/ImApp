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
    $username   = "root";
    $password   = null;
    $dbname     = "imapp";
    return new mysqli($servername, $username, $password, $dbname);
}

function save_to_db($id, $format, $width, $height)
{
    $conn = connect_to_db();
    $sql  = "INSERT INTO img_log (id, format, width, height)
VALUES ('$id', '$format', '$width', '$height')";
    $conn->query($sql);
    $conn->close();
}

function crop_image($image, $thumb_width, $thumb_height)
{
    $width  = imagesx($image);
    $height = imagesy($image);

    $original_aspect = $width / $height;
    $thumb_aspect    = $thumb_width / $thumb_height;

    if ($original_aspect >= $thumb_aspect) {
        // If the image is wider than the thumbnail
        $new_height = $thumb_height;
        $new_width  = $width / ($height / $thumb_height);
    } else {
        // If the thumbnail is wider than the image
        $new_width  = $thumb_width;
        $new_height = $height / ($width / $thumb_width);
    }

    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

    imagecopyresampled($thumb,
        $image,
        0 - ($new_width - $thumb_width) / 2,
        0 - ($new_height - $thumb_height) / 2,
        0, 0,
        $new_width, $new_height,
        $width, $height);
    return $thumb;
}

function resize_image($image, $new_width, $new_height)
{
    $image = imagescale($image, $new_width, $new_height);
    return $image;
}

function show_image($image, $format)
{
    if ($format == 'png') {
        return imagepng($image);
    } elseif ($format == 'jpg') {
        return imagejpeg($image);
    } elseif ($format == 'bmp') {
        return imagewbmp($image);
    } elseif ($format == 'gif') {
        return imagegif($image);
    }

}
