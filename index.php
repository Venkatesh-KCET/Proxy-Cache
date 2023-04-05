<?php

$start = microtime(true);

$domain = "https://proxy.playnotes.live/";

$no_proxy_domain = array(
                        "ogp.me",
                        "www.w3.org",
                        "www.gstatic.com",
                        "www.youtube.com",
                        "securepubads.g.doubleclick.net",
                        "www.googletagmanager.com",
                        "www.googletagservices.com",
                        "cdnjs.cloudflare.com",
                        "schema.org",
                        "in.linkedin.com",
                        "twitter.com",
                        "www.youtube.com",
                        "s.w.org",
                        "connect.facebook.net",
                        "www.google-analytics.com",
                        "api.connecto.io",
                        "plus.google.com/+cardekho",
                        "static.cloudflareinsights.com"
                        );

$url = "https://".$_GET['url'];

$url_parse = parse_url($url);
$path = $filename = explode("/", $url_parse['path']);
unset($path[count($filename)-1]);

$path = "Download/".$url_parse['host'].implode("/", $path);
$filename = $filename[count($filename)-1];

if(pathinfo($filename, PATHINFO_EXTENSION) == "") {
	$filename = $filename.".html";
}

if(trim($filename) == ".html") {
	$filename = "index".trim($filename);
}

if (!file_exists($path)) {
	mkdir($path, 0777, true);
}

$cache_file = $path."/".$filename;
if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 5 ))) {
   // Cache file is less than five minutes old. 
   // Don't bother refreshing, just use the file as-is.
   $file = file_get_contents($cache_file);
   print $file;
   exit;
}

$org_domain = explode( "/", $_SERVER['REQUEST_URI'])[1];
$user_headers = array();
foreach (getallheaders() as $name => $value) {
	if (!preg_match('/^(?:X-|Cf-|Cdn-|via|server|report-to)/i', $name)) {
    	$value = str_replace("proxy.playnotes.live", $org_domain, $value);
        $user_headers[$name] = $value;
    }
}

unset($user_headers["Accept"]);
unset($user_headers["Sec-Fetch-Site"]);
unset($user_headers["Sec-Fetch-User"]);
unset($user_headers["Upgrade-Insecure-Requests"]);
unset($user_headers["User-Agent"]);
unset($user_headers["Accept-Encoding"]);
unset($user_headers["Accept-Language"]);
unset($user_headers["Sec-Ch-Ua"]);
unset($user_headers["Sec-Ch-Ua-Mobile"]);
unset($user_headers["Sec-Ch-Ua-Platform"]);
unset($user_headers["Sec-Fetch-Dest"]);
unset($user_headers["Sec-Fetch-Mode"]);

$user_headers["accept"] = "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7";
$user_headers["accept-language"] = "en-US,en;q=0.9,ta;q=0.8";
$user_headers["sec-ch-ua"] = '"Google Chrome";v="111", "Not(A:Brand";v="8", "Chromium";v="111"';
$user_headers["sec-ch-ua-mobile"] = "?0";
$user_headers["sec-ch-ua-platform"] = '"Linux"';
$user_headers["sec-fetch-dest"] = 'document';
$user_headers["sec-fetch-mode"] = 'navigate';
$user_headers["sec-fetch-site"] = 'same-origin';
$user_headers["sec-fetch-user"] = '?1';
$user_headers["upgrade-insecure-requests"] = '1';
$user_headers["user-agent"] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36';
$user_headers["accept-encoding"] = 'gzip';

unset($user_headers["Host"]);
if(isset($user_headers["Host"])) {
    $user_headers["Host"] = $org_domain;
}



#print_r($user_headers);
#exit;

$ch = curl_init($url);
	
curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
}
 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $user_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

list($header, $contents) = preg_split('/([\r\n][\r\n])\\1/', curl_exec($ch), 2);
 
$status = curl_getinfo($ch);
 
curl_close($ch);

// Split header text into an array.
$header_text = preg_split('/[\r\n]+/', $header);

$change = false;
// Propagate headers to response.
foreach ($header_text as $headerChange) {
	#if (preg_match('/^(?:Content-Type|Content-Language|Set-Cookie):/i', $headerChange)) {
    	$headerChange = str_replace("https://", $domain, $headerChange);
    	$headerChange = str_replace("http://", $domain, $headerChange);
    	header($headerChange);
    #}
	if (!preg_match('/^(?:content-type: text)/i', $headerChange)) {
    	$change = true;
	}
	if (!preg_match('/^(?:content-type: image)/i', $headerChange)) {
    	$change = false;
	}
}

if($change) {
    $contents = str_replace("https://", $domain, $contents);
    $contents = str_replace("http://", $domain, $contents);
    
    $contents = str_replace('href="/', 'href="/'.$url_parse['host']."/", $contents);
    $contents = str_replace("href='/", "href='/".$url_parse['host']."/", $contents);
    $contents = str_replace('src="/', 'src="/'.$url_parse['host']."/", $contents);
    $contents = str_replace("src='/", "src='/".$url_parse['host']."/", $contents);
    
    foreach($no_proxy_domain as $no_proxy) {
        if("$no_proxy" == "www.w3.org" || "$no_proxy" == "ogp.me") {
            $contents = str_replace($domain.$no_proxy, "http://".$no_proxy, $contents);
        } else {
            $contents = str_replace($domain.$no_proxy, "https://".$no_proxy, $contents);
        }
    }
    
}
$contents = str_replace("www.cardekho.com", "nest.playnotes.live", $contents);
print $contents;

file_put_contents($path."/".$filename, $contents);
