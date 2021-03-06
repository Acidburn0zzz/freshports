<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/freshports.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/databaselogin.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/getvalues.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/../rewrite/functions.php');


$script = $_SERVER['SCRIPT_URL'];
$query  = $_SERVER['QUERY_STRING'];

$url = parse_url($query);

parse_str($query, $url_parts);

$Debug = isset($url_parts['Debug']);
#$Debug = 1;
if ($Debug) {
#    phpinfo();
    echo '<pre>';

    echo 'script = ';
    var_dump($script);

    echo 'query = ';
    var_dump($query);

    echo 'url = ';
    var_dump($url);

    echo 'url parts = ';
    var_dump($url_parts);
    echo '</pre>';
}

define('SCRIPT_BADGES', '/--/badges/');
define('SCRIPT_API',    '/--/api/');

$items = explode('/', $script);

switch($script) {
    case SCRIPT_BADGES:
        require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/badges.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/ports.php');
        
        $category_port = pg_escape_string($url_parts['port']);
        
        list($category, $port) = explode('/', $category_port);
        $myPort = new Port($db);
        $result = $myPort->Fetch($category, $port);
        $port_badge = new port_badge($db, $myPort);
        if (!empty($result)) {
            $badge = $port_badge->url();
            header("Location: $badge", true, 303);
            exit;
        } else {
            $img = $port_badge->not_found();
            header("Location: $img", true, 303);
            exit;
        }

        break;

    case SCRIPT_API:
        echo 'api';
        break;

    default:
        $result = freshports_Parse404URI($_SERVER['REQUEST_URI'], $db);

        # if you get here, we could not find anything in the database, so let's run 
        # the 404 code.
        #
        # XXX move missing.php out of DOCUMENT_ROOT
        #echo "\$result='$result'";

        if ($result != '') {
	    require($_SERVER['DOCUMENT_ROOT'] . '/../rewrite/missing.php');
        }
}
