<?php
/**
 * Elixi build script
 **/
set_time_limit(600);
ob_implicit_flush();
$dbugflag = isset($_REQUEST['dbugflag']) ? true : false;
/**
 * Defines are grabbed from a local config, but can be hardcoded here as a last resort
 */

/** If true, delete self and other artifacts,
 * also expect index.html to be root,
 * and 3rdParty/Motocol dirs to be under
 * Otherwise, expect 3rdParty, Motocol, & Project dirs to be under root
 * (It only does the otherwise right now)  (Either way, require config from up one level)
 **/
//$prod = false;

//For creation of limited user, if necessary (It doesn't do this yet)
//define('DB_HOST', 'localhost');
//define('DB_USER', 'root');
//define('DB_PASS', '');


/***********************************************************************************************************************
 * The important bit
 **********************************************************************************************************************/
try {
    //Load 3rd party libraries
    party('jquery', '1.9.1', $prod);
    party('jquery-migrate', '1.1.1', $prod);
    party('jqm', '1.3.2', $prod);
    //Load @rchetype
    loadArchetype('Dev');
    //Load Config extensions
    //Load Database(s)
    loadDatabase('mysql', 'rchetype_elixi', '../sql/elixi.sql');

    echo "<a href='../html/index.php'>Make Successful! (Probably)</a>";
}
catch (Exception $e) {
    echo $e->getMessage();
}
/***********************************************************************************************************************
 * End Config
 **********************************************************************************************************************/

/**
 * Creates local db config if it doesn't exist (and settings are not hard coded)
 */
function configDefined($def) {
    @include_once('../../config.php');
    if(!defined($def)) {
        if(!file_exists('../../config.php')) {
            $msg = 'Local config file missing from <b>../config.php</b>, creating...';
            file_put_contents('../../config.php', "<?php\n" . '$prod = false;' . "\ndefine('MYSQL_HOST', 'localhost');\n" .
                "define('MYSQL_USER', 'root');\ndefine('MYSQL_PASS', '');");
            $msg .= '<b>File created.</b>  Please update the definitions and <a href="assemble.php">try again</a>.';
        }
        else {
            $msg = 'Config file <b>../config.php</b> missing definition for ' . $def;
        }
        throw new Exception($msg);
    }
}

/**
 * Creates local db JSON if it doesn't exist (for @rchetype Dev)
 */
function createConfigJSON() {
    file_put_contents('../../localdb.json', '{
    "myTA" : {
        "Database": {
            "Auth": {
                "host": "localhost",
                "user": "root",
                "pass": ""
            }
        }
    }
}');
    echo 'Local config file missing from <b>../localdb.json</b>, creating...<b>File created.</b>  Please update the definitions.</br>';
}

/**
 * Later, make this piecemeal
 *
 * @param $ver
 * @throws Exception Not implemented
 */
function loadArchetype($ver = 'dev') {
    if(strtolower($ver) == 'dev') {
        echo 'Using Dev @rchetype<br/>';
        $tests = array('../../archetype/archetype-MVC.js', '../../archetype/webservice/archetype-REST.php');
        foreach($tests as $filename) {
            $t = file_get_contents($filename);
            if(!$t) {
                throw new Exception('Expected dev archetype at: ' . $filename);
            }
        }
        if(!file_exists('../../localdb.json')) {
            createConfigJSON();
        }
        return;
    }
    throw new Exception("Not Implemented");
}

/**
 *
 * @param string $type Currently, just mysql
 * @param string $dbname
 * @param string $filename
 * @throws Exception
 */
function loadDatabase($type, $dbname, $filename = '') {
    global $dbugflag;
    if(!$filename) {
        $filename = '../sql/' . $dbname . '.sql';
    }
    echo 'Loading ' . $type . ' database:' . $dbname . '<br/>';
    if(strtolower($type) === 'mysql') {
        configDefined('MYSQL_HOST');
        configDefined('MYSQL_USER');
        configDefined('MYSQL_PASS');
        $dbname = strtolower($dbname);
        $sql = file_get_contents($filename);
        $statements = explode("\n", $sql);
        if(function_exists('mysqli_connect')) {
            if($dbugflag) {
                echo 'Using MySQLi';
            }
            $mysqli = true;
            $pdo = false;
            $db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
            if($db->connect_error) {
                throw new Exception('Connection error, please check make settings - ' . $db->connect_error);
            }
            $db->query("CREATE DATABASE IF NOT EXISTS `$dbname`;");
            if($db->error) {
                throw new Exception("Could not create database: " . $db->error);
            }
            $db->select_db($dbname);
            if($db->error) {
                throw new Exception("Could not select database $dbname: " . $db->error);
            }
        }
        else if(class_exists('PDO')) {
            $pdo = true;
            $mysqli = false;
            $db = new PDO("mysql:host:3396=" . MYSQL_HOST . ";dbname=mysql", MYSQL_USER, MYSQL_PASS);
            $db->exec("CREATE DATABASE IF NOT EXISTS `$dbname`;");
            if(intval($db->errorCode())) {
                throw new Exception(var_export($db->errorInfo()));
            }
            $db->exec("USE `$dbname`;");
            if(intval($db->errorCode())) {
                throw new Exception(var_export($db->errorInfo()));
            }
        }
        else {
            if($dbugflag) {
                echo 'Using MySQL';
            }
            $pdo = $mysqli = false;
            $db = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, false, 65536);//Allows multi-query
            if(mysql_error($db)) {
                throw new Exception('Connection error, please check make settings - ' . mysql_error($db));
            }
            mysql_query("CREATE DATABASE IF NOT EXISTS `$dbname`;", $db);
            if(mysql_error($db)) {
                throw new Exception("Could not create database: " . mysql_error($db));
            }
            mysql_select_db($dbname, $db);
            if(mysql_error($db)) {
                throw new Exception("Could not select database $dbname: " . mysql_error($db));
            }
        }
        $next = '';
        $delimited = false;
        foreach($statements as $statement) {
            $s = trim($statement);
            if(trim($s) || !trim($next)) {
                if(substr($s, 0, 2) == "--") {
                    /*Skip Comments*/
                    continue;
                }
                if(strtoupper(substr($s, 0, 9)) == "DELIMITER") {
                    if($delimited) {
                        $next = substr($next, 0, -1-strlen($delimited)); //This seems wrong...
                        if($dbugflag) {
                            echo '<p>Delimiter:' . $delimited . '</p>';
                            echo '<p>' . $next . '</p>';
                        }
                        doQuery($next, $db, $mysqli, $pdo);
                        $next = '';
                        $delimited = false;
                    }
                    else {
                        $delimited = substr($s, 10);
                    }
                    continue;
                }
                $next .= trim($s) . "\n";
            }
            if(substr(trim($next), -1) == ';' && !$delimited) {
                if($dbugflag) {
                    echo '<p>' . $next . '</p>';
                }
                doQuery($next, $db, $mysqli, $pdo);
                $next = '';
            }
        }
    }
}

/**
 * @param $next
 * @param $db
 * @param $isMySQLi
 * @param $isPDO
 * @throws Exception
 */
function doQuery($next, $db, $isMySQLi, $isPDO)
{
    if ($isMySQLi) {
        $db->multi_query($next);
        while ($db->next_result()) {
            echo 1; // flush multi_queries
        }
        if ($db->error) {
            throw new Exception("SQL Error! " . $db->error);
        }
    } else if ($isPDO) {
        $db->exec($next);
        if (intval($db->errorCode())) {
            throw new Exception(var_export($db->errorInfo()));
        }
    } else {
        mysql_query($next);
        if (mysql_error($db)) {
            throw new Exception("SQL Error! " . mysql_error($db));
        }
    }
}


/**
 * Fetches a 3rd party library
 *
 * @param string $lib
 * @param string $ver
 * @param bool $min
 */
function party($lib, $ver, $min = false) {
    echo 'Fetching ' . $lib . '<br/>';
    $dir = ($min ? '' : '../') . '3rdParty/';
    $lib = strtolower($lib);
    $min = $min ? 'true' : 'false';
    $files = explode('|', file_get_contents("http://rchetype.co/archetype/build/get.php?library=$lib&version=$ver&minified=$min"));
    foreach($files as $f) {
        $file = file_get_contents("http://rchetype.co/3rdParty/$f");
        $libDir = substr($f, 0, strrpos($f, '/'));
        if(!is_dir($dir . $libDir)) {
            mkdir($dir . $libDir, 0777, true);
        }
        file_put_contents($dir . $f, $file);
    }
}