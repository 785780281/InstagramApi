<?php
/**
 * Plugin Name: Get List Of #natural From Instagram
 */

/**
 * Access Token User in instagram
 * Please Set Your Access Token and Replace it.
 */
define('INSTAGRAM_ACCESS_TOKEN', 'IGQVJWeUpOQ0dONGFoblVtX0dsUm5NdWg5VWlsRzdKdzF3SDU4NFhMNXBFUlkwX0JqQ3JHX3ZAXR24tZAngzdUFaZA2RXQmVEdVBVQ2NYU2gtUV96R2p0QTVyMmZArQ21Cd0VZAVHlMNE4xY19iM0ppSmIwYwZDZD');

/**
 * Get API from instagram with Curl
 *
 * @param $api_url
 * @return mixed
 */
function instagram_api_curl_connect($api_url)
{
    $connection_c = curl_init(); // initializing
    curl_setopt($connection_c, CURLOPT_URL, $api_url); // API URL to connect
    curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1); // return the result, do not print
    curl_setopt($connection_c, CURLOPT_TIMEOUT, 20);
    $json_return = curl_exec($connection_c); // connect and get json data
    curl_close($connection_c); // close connection
    return json_decode($json_return); // decode and return
}

/**
 * Get Last Post From instagram with natural tag
 */
function get_instagram_posts_from_tag()
{
    $tag = 'natural';
    $return = instagram_api_curl_connect('https://api.instagram.com/v1/tags/' . $tag . '/media/recent?access_token=' . INSTAGRAM_ACCESS_TOKEN);
    $list = array();
    foreach ($return->data as $post) {
        $list[] = array(
            'link' => $post->images->standard_resolution->url,
            'thumbnail' => $post->images->thumbnail->url,
            'username' => $post->user->username,
            'like_count' => $post->likes->count,
            'comment_count' => $post->comments->count,
            'type' => $post->type //"image" or "video"
        );
    }

    return $list;
}

/**
 * Convert array instagram to csv File
 * @param $array
 * @param string $filename
 */
function csv_force_download($array, $filename = 'export.csv')
{
    $filepath = $_SERVER["DOCUMENT_ROOT"] . $filename . '.csv';
    $fp = fopen($filepath, 'w+');

    $i = 0;
    foreach ($array as $fields) {
        if ($i == 0) {
            fputcsv($fp, array_keys($fields));
        }
        fputcsv($fp, array_values($fields));
        $i++;
    }
    header('Content-Type: application/octet-stream; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Content-Length: ' . filesize($filepath));
    echo file_get_contents($filepath);
    exit;
}

/**
 * Action Download Csv instagram
 */
add_action('wp', 'download_csv_instagram_api');
function download_csv_instagram_api()
{
    if (isset($_GET['force_download_instagram_api'])) {

        // Get List instagram image
        $post_list = get_instagram_posts_from_tag();

        // Save to Csv
        csv_force_download($post_list);

        // Exit Process
        exit;
    }
}

/**
 * Add shortCode in WordPress for show
 */
add_shortcode('instagram-post', 'show_instagram_posts_shortcode');
function show_instagram_posts_shortcode($atts)
{
    $post_list = get_instagram_posts_from_tag();

    $text = '';
    foreach ($post_list as $post) {
        $text .= '<a href="' . $post['link'] . '">';
        $text .= '<img src="' . $post['thumbnail'] . '">';
        $text .= '</a>';
        $text .= 'Number Like:' . $post['like_count'];
        $text .= '<hr>';
    }

    // Add Download CSV
    $text .= '<p><a href="' . home_url() . '/?force_download_instagram_api=yes' . '">Download CSV</a></p>';

    // Return Data
    return $text;
}