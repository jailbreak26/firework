<?php
$whitelistPatterns = array(
  //getHostnamePattern("example.net")
);

$forceCORS = true;

ob_start("ob_gzhandler");

if (version_compare(PHP_VERSION, "5.4.7", "<")) {
    die("desiDerata requires PHP version 5.4.7 or later.");
}

if (!function_exists("curl_init")) die("desiDerata requires PHP's cURL extension. Please install/enable it on your server and try again.");


function getHostnamePattern($hostname) {
  $escapedHostname = str_replace(".", "\.", $hostname);
  return "@^https?://([a-z0-9-]+\.)*" . $escapedHostname . "@i";
}


function removeKeys(&$assoc, $keys2remove) {
  $keys = array_keys($assoc);
  $map = array();
  foreach ($keys as $key) {
     $map[strtolower($key)] = $key;
  }

  foreach ($keys2remove as $key) {
    $key = strtolower($key);
    if (isset($map[$key])) {
       unset($assoc[$map[$key]]);
    }
  }
}

if (!function_exists("getallheaders")) {
  //Adapted from http://www.php.net/manual/en/function.getallheaders.php#99814
  function getallheaders() {
    $result = array();
    foreach($_SERVER as $key => $value) {
      if (substr($key, 0, 5) == "HTTP_") {
        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
        $result[$key] = $value;
      }
    }
    return $result;
  }
}

$prefixPort = $_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : "";
$prefixHost = $_SERVER["HTTP_HOST"];
$prefixHost = strpos($prefixHost, ":") ? implode(":", explode(":", $_SERVER["HTTP_HOST"], -1)) : $prefixHost;

define("PROXY_PREFIX", "http" . (isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $prefixHost . $prefixPort . $_SERVER["SCRIPT_NAME"] . "/");


function makeRequest($url) {

  $user_agent = $_SERVER["HTTP_USER_AGENT"];
  if (empty($user_agent)) {
    $user_agent = "Mozilla/5.0 (compatible; desiDerata)";
  }
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

  $browserRequestHeaders = getallheaders();

  removeKeys($browserRequestHeaders, array(
    "Host",
    "Content-Length",
    "Accept-Encoding" 
  ));

  curl_setopt($ch, CURLOPT_ENCODING, "");
  $curlRequestHeaders = array();
  foreach ($browserRequestHeaders as $name => $value) {
    $curlRequestHeaders[] = $name . ": " . $value;
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $curlRequestHeaders);
  curl_setopt($ch, CURLOPT_REFERER, 'http://soccerschedule.online/catyes.php?id=espnusanew&width=620&height=490&stretching=');
  //Proxy any received GET/POST/PUT data.
  switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":
      curl_setopt($ch, CURLOPT_POST, true);
      $postData = Array();
      parse_str(file_get_contents("php://input"), $postData);
      if (isset($postData["desiDerataFormAction"])) {
        unset($postData["desiDerataFormAction"]);
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    break;
    case "PUT":
      curl_setopt($ch, CURLOPT_PUT, true);
      curl_setopt($ch, CURLOPT_INFILE, fopen("php://input", "r"));
    break;
  }

  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  curl_setopt($ch, CURLOPT_URL, $url);

  $response = curl_exec($ch);
  $responseInfo = curl_getinfo($ch);
  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  curl_close($ch);

  $responseHeaders = substr($response, 0, $headerSize);
  $responseBody = substr($response, $headerSize);

  return array("headers" => $responseHeaders, "body" => $responseBody, "responseInfo" => $responseInfo);
}


function rel2abs($rel, $base) {
  if (empty($rel)) $rel = ".";
  if (parse_url($rel, PHP_URL_SCHEME) != "" || strpos($rel, "//") === 0) return $rel; 
  if ($rel[0] == "#" || $rel[0] == "?") return $base.$rel; 
  extract(parse_url($base)); 
  $path = isset($path) ? preg_replace("#/[^/]*$#", "", $path) : "/"; 
  if ($rel[0] == "/") $path = ""; 
  $port = isset($port) && $port != 80 ? ":" . $port : "";
  $auth = "";
  if (isset($user)) {
    $auth = $user;
    if (isset($pass)) {
      $auth .= ":" . $pass;
    }
    $auth .= "@";
  }
  $abs = "$auth$host$port$path/$rel"; 
  for ($n = 1; $n > 0; $abs = preg_replace(array("#(/\.?/)#", "#/(?!\.\.)[^/]+/\.\./#"), "/", $abs, -1, $n)) {} //Replace '//' or '/./' or '/foo/../' with '/'
  return $scheme . "://" . $abs; 
}


function proxifyCSS($css, $baseURL) {
  $sourceLines = explode("\n", $css);
  $normalizedLines = [];
  foreach ($sourceLines as $line) {
    if (preg_match("/@import\s+url/i", $line)) {
      $normalizedLines[] = $line;
    } else {
      $normalizedLines[] = preg_replace_callback(
        "/(@import\s+)([^;\s]+)([\s;])/i",
        function($matches) use ($baseURL) {
          return $matches[1] . "url(" . $matches[2] . ")" . $matches[3];
        },
        $line);
    }
  }
  $normalizedCSS = implode("\n", $normalizedLines);
  return preg_replace_callback(
    "/url\((.*?)\)/i",
    function($matches) use ($baseURL) {
        $url = $matches[1];
        if (strpos($url, "'") === 0) {
          $url = trim($url, "'");
        }
        if (strpos($url, "\"") === 0) {
          $url = trim($url, "\"");
        }
        if (stripos($url, "data:") === 0) return "url(" . $url . ")"; 
        return "url(" . PROXY_PREFIX . rel2abs($url, $baseURL) . ")";
    },
    $normalizedCSS);
}


function proxifySrcset($srcset, $baseURL) {
  $sources = array_map("trim", explode(",", $srcset)); 
  $proxifiedSources = array_map(function($source) use ($baseURL) {
    $components = array_map("trim", str_split($source, strrpos($source, " "))); 
    $components[0] = PROXY_PREFIX . rel2abs(ltrim($components[0], "/"), $baseURL); 
    return implode($components, " "); 
  }, $sources);
  $proxifiedSrcset = implode(", ", $proxifiedSources); 
  return $proxifiedSrcset;
}

if (isset($_POST["desiDerataFormAction"])) {
  $url = $_POST["desiDerataFormAction"];
  unset($_POST["desiDerataFormAction"]);
} else {
  $queryParams = Array();
  parse_str($_SERVER["QUERY_STRING"], $queryParams);
  if (isset($queryParams["desiDerataFormAction"])) {
    $formAction = $queryParams["desiDerataFormAction"];
    unset($queryParams["desiDerataFormAction"]);
    $url = $formAction . "?" . http_build_query($queryParams);
  } else {
    $url = substr($_SERVER["REQUEST_URI"], strlen($_SERVER["SCRIPT_NAME"]) + 1);
  }
}
if (empty($url)) {
    die("<html>
<head>
<title>Success</title>
</head><body><h1 style='font-family:Tahoma,Arial;font-size:20px;font-weight:normal;margin-left:20px;'>Success</h1>
<div style='margin-top:20px;margin-left:20px;margin-right:20px;margin-bottom:20px;font-size:14px;font-family:Tahoma,Arial'>Put your heart,<br />
mind,<br />
and soul into even your smallest acts.<br />
This is the secret of success.<br /><br />


Swami Sivananda<br />
</div>
</body>
</html>");
} else if (strpos($url, ":/") !== strpos($url, "://")) {
    $pos = strpos($url, ":/");
    $url = substr_replace($url, "://", $pos, strlen(":/"));
}
$scheme = parse_url($url, PHP_URL_SCHEME);
if (empty($scheme)) {
  if (strpos($url, "//") === 0) {
    $url = "http:" . $url;
  }
} else if (!preg_match("/^https?$/i", $scheme)) {
    die('Error: Detected a "' . $scheme . '" URL. desiDerata exclusively supports http[s] URLs.');
}

$urlIsValid = count($whitelistPatterns) === 0;
foreach ($whitelistPatterns as $pattern) {
  if (preg_match($pattern, $url)) {
    $urlIsValid = true;
    break;
  }
}
if (!$urlIsValid) {
  die("Error: The requested URL was disallowed by the server administrator.");
}

$response = makeRequest($url);
$rawResponseHeaders = $response["headers"];
$responseBody = $response["body"];
$responseInfo = $response["responseInfo"];

$responseURL = $responseInfo["url"];
if ($responseURL !== $url) {
  header("Location: " . PROXY_PREFIX . $responseURL, true);
  exit(0);
}


$header_blacklist_pattern = "/^Content-Length|^Transfer-Encoding|^Content-Encoding.*gzip/i";

$responseHeaderBlocks = array_filter(explode("\r\n\r\n", $rawResponseHeaders));
$lastHeaderBlock = end($responseHeaderBlocks);
$headerLines = explode("\r\n", $lastHeaderBlock);
foreach ($headerLines as $header) {
  $header = trim($header);
  if (!preg_match($header_blacklist_pattern, $header)) {
    header($header);
  }
}

header("X-Robots-Tag: noindex, nofollow");

if ($forceCORS) {
  header("Access-Control-Allow-Origin: *", true);
  header("Access-Control-Allow-Credentials: true", true);

  if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
      header("Access-Control-Allow-Methods: GET, POST, OPTIONS", true);
    }
    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
      header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}", true);
    }
 
    exit(0);
  }

}

$contentType = "";
if (isset($responseInfo["content_type"])) $contentType = $responseInfo["content_type"];


if (stripos($contentType, "text/html") !== false) {

  
  $detectedEncoding = mb_detect_encoding($responseBody, "UTF-8, ISO-8859-1");
  if ($detectedEncoding) {
    $responseBody = mb_convert_encoding($responseBody, "HTML-ENTITIES", $detectedEncoding);
  }

  
  $doc = new DomDocument();
  @$doc->loadHTML($responseBody);
  $xpath = new DOMXPath($doc);

  
  foreach($xpath->query("//form") as $form) {
    $method = $form->getAttribute("method");
    $action = $form->getAttribute("action");
    $action = empty($action) ? $url : rel2abs($action, $url);
    $form->setAttribute("action", rtrim(PROXY_PREFIX, "?"));
    $actionInput = $doc->createDocumentFragment();
    $actionInput->appendXML('<input type="hidden" name="desiDerataFormAction" value="' . htmlspecialchars($action) . '" />');
    $form->appendChild($actionInput);
  }
  foreach ($xpath->query("//meta[@http-equiv]") as $element) {
    if (strcasecmp($element->getAttribute("http-equiv"), "refresh") === 0) {
      $content = $element->getAttribute("content");
      if (!empty($content)) {
        $splitContent = preg_split("/=/", $content);
        if (isset($splitContent[1])) {
          $element->setAttribute("content", $splitContent[0] . "=" . PROXY_PREFIX . rel2abs($splitContent[1], $url));
        }
      }
    }
  }
  
  foreach($xpath->query("//style") as $style) {
    $style->nodeValue = proxifyCSS($style->nodeValue, $url);
  }
  
  foreach ($xpath->query("//*[@style]") as $element) {
    $element->setAttribute("style", proxifyCSS($element->getAttribute("style"), $url));
  }
  
  foreach ($xpath->query("//img[@srcset]") as $element) {
    $element->setAttribute("srcset", proxifySrcset($element->getAttribute("srcset"), $url));
  }
  
  $proxifyAttributes = array("href", "src");
  foreach($proxifyAttributes as $attrName) {
    foreach($xpath->query("//*[@" . $attrName . "]") as $element) { 
      $attrContent = $element->getAttribute($attrName);
      if ($attrName == "href" && preg_match("/^(about|javascript|magnet|mailto):/i", $attrContent)) continue;
      $attrContent = rel2abs($attrContent, $url);
      $attrContent = PROXY_PREFIX . $attrContent;
      $element->setAttribute($attrName, $attrContent);
    }
  }

  $head = $xpath->query("//head")->item(0);
  $body = $xpath->query("//body")->item(0);
  $prependElem = $head != NULL ? $head : $body;

  if ($prependElem != NULL) {

    $scriptElem = $doc->createElement("script",
      '(function() {

        if (window.XMLHttpRequest) {

          function parseURI(url) {
            var m = String(url).replace(/^\s+|\s+$/g, "").match(/^([^:\/?#]+:)?(\/\/(?:[^:@]*(?::[^:@]*)?@)?(([^:\/?#]*)(?::(\d*))?))?([^?#]*)(\?[^#]*)?(#[\s\S]*)?/);
            // authority = "//" + user + ":" + pass "@" + hostname + ":" port
            return (m ? {
              href : m[0] || "",
              protocol : m[1] || "",
              authority: m[2] || "",
              host : m[3] || "",
              hostname : m[4] || "",
              port : m[5] || "",
              pathname : m[6] || "",
              search : m[7] || "",
              hash : m[8] || ""
            } : null);
          }

          function rel2abs(base, href) { // RFC 3986

            function removeDotSegments(input) {
              var output = [];
              input.replace(/^(\.\.?(\/|$))+/, "")
                .replace(/\/(\.(\/|$))+/g, "/")
                .replace(/\/\.\.$/, "/../")
                .replace(/\/?[^\/]*/g, function (p) {
                  if (p === "/..") {
                    output.pop();
                  } else {
                    output.push(p);
                  }
                });
              return output.join("").replace(/^\//, input.charAt(0) === "/" ? "/" : "");
            }

            href = parseURI(href || "");
            base = parseURI(base || "");

            return !href || !base ? null : (href.protocol || base.protocol) +
            (href.protocol || href.authority ? href.authority : base.authority) +
            removeDotSegments(href.protocol || href.authority || href.pathname.charAt(0) === "/" ? href.pathname : (href.pathname ? ((base.authority && !base.pathname ? "/" : "") + base.pathname.slice(0, base.pathname.lastIndexOf("/") + 1) + href.pathname) : base.pathname)) +
            (href.protocol || href.authority || href.pathname ? href.search : (href.search || base.search)) +
            href.hash;

          }

          var proxied = window.XMLHttpRequest.prototype.open;
          window.XMLHttpRequest.prototype.open = function() {
              if (arguments[1] !== null && arguments[1] !== undefined) {
                var url = arguments[1];
                url = rel2abs("' . $url . '", url);
                url = "' . PROXY_PREFIX . '" + url;
                arguments[1] = url;
              }
              return proxied.apply(this, [].slice.call(arguments));
          };

        }

      })();'
    );
    $scriptElem->setAttribute("type", "text/javascript");

    $prependElem->insertBefore($scriptElem, $prependElem->firstChild);

  }

  echo "<!-- Opened by desiDerata -->\n" . $doc->saveHTML();
} else if (stripos($contentType, "text/css") !== false) { 
  echo proxifyCSS($responseBody, $url);
} else { 
  header("Content-Length: " . strlen($responseBody));
  echo $responseBody;
}
