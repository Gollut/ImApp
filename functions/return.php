<?php
require '../vendor/autoload.php';
require 'utility.php';

$path_parts = explode('/', $_SERVER['REQUEST_URI']);
if (count($path_parts) >= 3) {
    $filters = $path_parts;
    array_splice($filters, 0, 2);
    array_splice($filters, 1, 1);
} else {
    $filters = false;
}
$id = $path_parts[1];
get_from_s3($id, $filters);

function get_from_s3($id, $filters = false)
{
    $conn   = connect_to_db();
    $sql    = "SELECT format from imapp.img_log where id = '$id' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $format = $result->fetch_assoc()["format"];
        $bucket = 'codex-uploader';
        $key    = $id . '.' . $format;
        $url    = 'https://' . $bucket . '.s3.eu-central-1.amazonaws.com/' . $key;
        $file   = file_get_contents($url);
        $size   = strlen($file);
        $image  = imagecreatefromstring($file);
        if ($filters) {
            foreach ($filters as &$filter) {
                if (strpos($filter, 'crop') !== false) {
                    $filter = substr($filter, 4);
                    $width  = explode('x', $filter)[0];
                    $height = explode('x', $filter)[1];
                    $image  = crop_image($image, $width, $height);
                } elseif (strpos($filter, 'resize') !== false) {
                    $filter = substr($filter, 6);
                    $width  = explode('x', $filter)[0];
                    $height = explode('x', $filter)[1];
                    $image  = resize_image($image, $width, $height);
                }
            }
        }
        header('Content-Description: File Transfer');
        header('Content-Type: image/' . $format);
        header('Content-Transfer-Encoding: binary');
        header('Pragma: public');
        header('Cache-Control: max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Length: ' . $size);
        header('Content-Disposition: inline; filename=' . $key);
        show_image($image, $format);
        $conn->close();
    } else {
        $conn->close();
        return ["status" => 0, "file" => null];
    }
}
