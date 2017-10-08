<?php
require '../vendor/autoload.php';
require 'utility.php';
use Aws\S3\S3Client;
$path_parts = explode('/', $_SERVER['REQUEST_URI']);
if (count($path_parts) >= 3) {
    $filters = $path_parts;
    array_splice($filters, 0, 2);
    array_splice($filters, 1, 1);
} else {
    $filters = False;
}
$id = $path_parts[1];
get_from_s3($id, $filters);

function get_from_s3($id, $filters = False)
{
    $s3 = new S3Client([
        'version'     => 'latest',
        'region'      => 'eu-central-1',
        'credentials' => [
            'key'    => 'AKIAJKGCFB3O3K4FD3NA',
            'secret' => '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',
        ],
    ]);
    //https://codex-uploader.s3.eu-central-1.amazonaws.com/X-EJxobUzAM.jpg
    $conn = connect_to_db();
    $sql = "SELECT format from imapp.img_log where id = '$id' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $format = $result->fetch_assoc()["format"];
        $bucket = 'codex-uploader';
        $key = $id.'.'.$format;
        $url = 'https://'.$bucket.'.s3.eu-central-1.amazonaws.com/'.$key;       
        $file = basename($url);
        $filters=False;
        if ($filters)
        {
            $image = file_get_contents($url);
            foreach ($filters as &$filter) {
                if (strpos($filter, 'crop') !== False)
                {
                    //crop300x300
                    $filter = substr($filter, 4);
                    print($file);
                    $width = explode('x', $filter)[0];
                    $height = explode('x', $filter)[1];
                    $startX=0;
                    $startY=0;
                    print($width);
                    $fp = crop_image($fp, $startX, $startY, $width, $height);
                }
                elseif (strpos($filter, 'resize') !== False)
                {
                    //resize1200x700
                    $filter = substr($filter, 0, 6);
                    $width = explode('x', filter)[0];
                    $height = explode('x', filter)[1];
                    $filterType = 1;
                    $fp = resize_image($fp, $width, $height);
                }
            }
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true); 
        curl_setopt($ch, CURLOPT_NOBODY, true); // make it a HEAD request
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        $data = curl_exec($ch);
        $mimeType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $path = parse_url($url, PHP_URL_PATH);
        $filename = substr($path, strrpos($path, '/') + 1);
        curl_close($ch);
        header('Content-Description: File Transfer');
        header('Content-Type: '.$mimeType);
        header('Content-Length: '.$size);
        //header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public');
        header('Cache-Control: max-age=31536000');
        header('Pragma: public');
        header('Connection: close');
        ob_clean();
        flush();
        readfile($url);
        $conn->close();
    }
    else{
        $conn->close();
        return ["status"=>0,"file"=>Null];
    }
}
