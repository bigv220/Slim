<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
ini_set('display_errors','On');
define("DB_PREFIX", "echeng_");
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$app->get(
    '/',
    function () {
        $template = "123";
        echo $template;
    }
);
defined('M_UPSEN') || define('M_UPSEN', TRUE);
        defined('UN_VIRTURE_URL') || define('UN_VIRTURE_URL', TRUE);//需要处理伪静态
// POST route
$app->post(
    '/list',
    'ListProperties'
);

$app->post(
    '/detail',
    'getProperty'
);

$app->post(
    '/listbyarea',
    'getPropertiesByArea'
);

$app->post(
    '/getarea',
    'getArea'
);
$app->post(
    '/getshangquan',
    'getShangquan'
);

$app->run();


/**
* To Get Property List
* aid: Key, ccid1: District, ccid12: type, ccid18: daishou
* Param: Price, Limit, etc.
**/
function ListProperties() {
    
    try {
        $db = getConnection();

        // Get Location
        $ccid1_arr = getColInfo(1, $db);
        $ccid2_arr = getColInfo(2, $db); 

        // Status
        $ccid18_arr = getColInfo(18, $db);

        // Kaifashang
        $arc27_arr = getArchiveInfo(27, $db);
        
        // Param
        global $app;
        $params = $app->request->post();

        
        if (isset($params['ccid1'])) {
            $w_str = " WHERE ccid1 = ".$params['ccid1'];
        } else {
            $w_str = "";
        }

        if (isset($params['ccid2'])) {
            $w_str .= " AND ccid2 = ".$params['ccid2'];
        } else {
            $w_str .= "";
        }

        if (isset($params['zhekouleixing'])) {
            $w_str .= " AND zhekouleixing = ".$params['zhekouleixing'];
        } else {
            $w_str .= "";
        }

        // ccid17
        // 164:0-5000 166:5000-7000 167:7000-10000 168:10000-15000 169:15000-0
        if (isset($params['ccid17'])) {
            $dj_arr = explode('-', $params['ccid17']);
            if ($dj_arr[1] != 0) {
                $w_str .= " AND dj between ".$dj_arr[0]." AND ".$dj_arr[1];    
            } else {
                $w_str .= " AND dj >".$dj_arr[0];    
            }
            
        } else {
            $w_str .= "";
        }

        // Orderby
        if (isset($params['orderby'])) {
            $order_str = " order by ";
            if ($params['orderby'] == 'dj') {
                $order_str .= " dj";
            } else if ($params['orderby'] == 'clicks') {
                $order_str .= " clicks";
            }
        }

        // Sort
        if (isset($params['sort'])) {
            $order_str .= $params['sort'];
        } else {
            $order_str = "";
        }

        // Limit
        if (isset($params['limit']) && isset($params['page'])) {
            $limit_str = " limit ".$params['limit']*$params['page']." ,". ($params['page'] + 1) * $params['limit'];
        } else {
            $limit_str = "limit 0, 10";
        }

        // Get List
        $query = "SELECT aid, subject, thumb, ccid1, ccid2, ccid18,pid6,dj, zhekou as junjia FROM ".DB_PREFIX."archives15 $w_str $order_str $limit_str";
        $pro = $db->query($query);
        $result = $pro->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key => &$value) {
            $value['district'] = isset($ccid1_arr[$value['ccid1']])?$ccid1_arr[$value['ccid1']]:"";
            $value['area'] = isset($ccid2_arr[$value['ccid2']])?$ccid2_arr[$value['ccid2']]:"";
            $value['status'] = isset($ccid18_arr[$value['ccid18']])?$ccid18_arr[$value['ccid18']]:"";
            $value['kaifashang'] = isset($arc27_arr[$value['pid6']])?$arc27_arr[$value['pid6']]:"";
            unset($value['ccid1']);
            unset($value['ccid2']);
            unset($value['ccid18']);
            unset($value['pid6']);
        }
        // ROW COUNT
        $row_count = count($result);
        $result = array('result'=> $result);        
        $result['row_count'] = $row_count;
        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

/**
* Get Property Detail
**/
function getProperty() {
    try {
        $db = getConnection();

        // Get Location
        $ccid1_arr = getColInfo(1, $db);
        $ccid2_arr = getColInfo(2, $db); 
        $ccid18_arr = getColInfo(18, $db);
        
        // Param
        global $app;
        $aid = $app->request->post('aid');

        $query = "SELECT aid, shoulouchudizhi,tel,content,address,loupanlogo as junjia FROM ".DB_PREFIX."archives_4 WHERE aid=$aid";
        $pro = $db->query($query);
        $res_detail = $pro->fetchAll(PDO::FETCH_ASSOC);

        // Get Detail List
        $query = "SELECT aid, subject, thumb, ccid1, ccid2, ccid18,pid6,dj as junjia,dt_0,dt_1 FROM ".DB_PREFIX."archives15 WHERE aid=$aid";
        $pro = $db->query($query);
        $result = $pro->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key => &$value) {
            $value['district'] = isset($ccid1_arr[$value['ccid1']])?$ccid1_arr[$value['ccid1']]:"";
            $value['area'] = isset($ccid2_arr[$value['ccid2']])?$ccid2_arr[$value['ccid2']]:"";
            $value['status'] = isset($ccid18_arr[$value['ccid18']])?$ccid18_arr[$value['ccid18']]:"";
            $value['kaifashang'] = isset($arc27_arr[$value['pid6']])?$arc27_arr[$value['pid6']]:"";
            unset($value['ccid1']);
            unset($value['ccid2']);
            unset($value['ccid18']);
            unset($value['pid6']);
        }

        $db = null;
        echo json_encode($result);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

/**
* Get the properties by area
* @param aid property
**/
function getPropertiesByArea() {
    try {
        $db = getConnection();
        // Param
        global $app;
        $params = $app->request->post();

  
        if (isset($params['ccid1'])) {
            $w_str = " WHERE ccid1 = ".$params['ccid1'];
        } else {
            $w_str = "";
        }

        if (isset($params['ccid2'])) {
            $w_str .= " AND ccid2 = ".$params['ccid2'];
        } else {
            $w_str .= "";
        }

        // Get List
        $query = "SELECT aid, subject, thumb,dj as junjia FROM ".DB_PREFIX."archives15 $w_str ";
        $pro = $db->query($query);
        $result = $pro->fetchAll(PDO::FETCH_ASSOC);
        
        // ROW COUNT
        $row_count = count($result);
        $result = array('result'=> $result);        
        $result['row_count'] = $row_count;
        $db = null;
        echo json_encode($result);
    } catch (Exception $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function getArea() {
    $db = getConnection();
    $info = getColInfo(1, $db);
    echo json_encode($info);
}

function getShangquan() {
    $db = getConnection();
    $info = getColInfo(2, $db);
    echo json_encode($info);   
}

function getArchiveInfo($num, $db) {
    // Get Detail Location
       $query = "SELECT aid, subject FROM ".DB_PREFIX."archives{$num}";
       $ccid2 = $db->query($query);
       $ccid2->setFetchMode(PDO::FETCH_ASSOC);
       $ccid2_arr = array();
       while ($row  = $ccid2->fetch()) {
           $ccid2_arr[$row['aid']] = $row['subject'];
       }
       return $ccid2_arr;
}

function getColInfo($num, $db) {
    // Get Detail Location
       $query = "SELECT ccid, title FROM ".DB_PREFIX."coclass{$num}";
       $ccid2 = $db->query($query);
       $ccid2->setFetchMode(PDO::FETCH_ASSOC);
       $ccid2_arr = array();
       while ($row  = $ccid2->fetch()) {
           $ccid2_arr[$row['ccid']] = $row['title'];
       }
       return $ccid2_arr;
}

function getConnection() {
    $dbname = "echengchina";
    $dbuser = "root";
    $dbpwd = "123456";

    $con = new PDO("mysql:host=localhost;dbname=$dbname", $dbuser, $dbpwd);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}