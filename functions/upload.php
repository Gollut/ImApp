<?php
require '../vendor/autoload.php';
require 'utility.php';

use Aws\S3\S3Client;

$link    = $_POST["upload_link"];
$success = 0;
if ($_FILES) {
    $file = $_FILES["upload_file"];
    $ext  = pathinfo($file["name"], PATHINFO_EXTENSION);
    if (exif_imagetype($file["tmp_name"])) {
        $keyname = generate_name();
        if (upload_on_s3($file["tmp_name"], $keyname . '.' . $ext, $ext)) {
            $success = 1;
        }

        $data = get_img_data($file["tmp_name"], $keyname, $ext);
        save_to_db($keyname, $data["format"], $data["width"], $data["height"]);
    } else {
        $message = "File is not an image";
    }
} elseif ($link != "") {
    if (img_exists($link)) {
        $ext     = pathinfo($link, PATHINFO_EXTENSION);
        $keyname = generate_name();
        if (upload_on_s3($link, $link, $ext)) {
            $success = 1;
            $data    = get_img_data($link, $keyname, $ext);
            save_to_db($keyname, $data["format"], $data["width"], $data["height"]);
        } else {
            $message = "Loading error";
        }
    } else {
        $message = "Loading error";
    }
} else {
    $message = "No file/link";
}
if ($success != 0) {
    $data["success"] = $success;
} else {
    $data = ["success" => $success, "message" => $message];
}

print_r($data);

function upload_on_s3($file, $keyname, $ext)
{
    require 's3_credentials.php';

    $s3 = new S3Client([
        'version'     => 'latest',
        'region'      => 'eu-central-1',
        'credentials' => $credentials,
    ]);
    $bucket = 'codex-uploader';
    $result = $s3->putObject(array(
        'Bucket'      => $bucket,
        'Key'         => $keyname,
        'SourceFile'  => $file,
        'ContentType' => 'image/' . $ext,
        'ACL'         => 'public-read',
    ));
    //$upload = $s3->upload($bucket, $keyname, $file, 'public-read');
    return true;
}

function get_img_data($filename, $aws_id, $ext)
{
    $size = getimagesize($filename);
    $data = [
        "id"     => $aws_id,
        "width"  => $size[0],
        "height" => $size[1],
        "format" => $ext,
    ];
    return $data;
}
