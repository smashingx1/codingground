<?php
ini_set('display_errors', isset($_REQUEST['dbugflag']));
require_once(__DIR__ . '/pagelist.php');

if(isset($argv)) {
    foreach($argv as $arg) {
        $build[$arg] = true;
    }
}
if(isset($_REQUEST)) {
    foreach($_REQUEST as $k => $v) {
        $build[$k] = true;
    }
}
if(isset($build)) {
    buildIndex($chunks, $build);
}

function buildIndex($chunks, $build) {
    $dir = getcwd();
    chdir(__DIR__);
    $head = file_get_contents('../html/partials/templates/head.html') . "\n\n\n";
    $arch = file_get_contents('../html/partials/archetype.html') . "\n\n\n";
    $comment = '<!-' . str_repeat('- ', 57) . '-->' . "\n";
    foreach($chunks as $chunk) {
        $contents = file_get_contents("../html/partials/$chunk.html");
        while(false !== ($pos = strpos($contents, '<?php'))) {
            $end = strpos($contents, '?>', $pos) + 2;
            $php = substr($contents, $pos, $end - $pos);
            $include = substr($php, strpos($php, "'") + 1);
            $include = substr($include, 0, strpos($include, "'"));
            $contents = substr($contents, 0, $pos) . file_get_contents('../html/' . $include) . substr($contents, $end);
        }
        $output .= $comment;
        $output .= '<!-- ' . ucwords(str_replace('-', ' ', $chunk)) . ' Page -->' . "\n";
        $output .= $comment;
        $output .= $contents;
        $output .= "\n\n\n\n";
    }
    $output = trim($output) . "\n</body>\n</html>";
    if($build['cordova']) {
        $cHead = str_replace('../../', '', $head);
        $cArch = str_replace('../../', 'http://rchetype.co/', $arch);
        mkdir('../elixi_cordova/www');
        file_put_contents('../elixi_cordova/www/index.html', trim($cHead . $cArch . $output));
        recurse_copy('../html/img', '../elixi_cordova/www/img');
        recurse_copy('../html/css', '../elixi_cordova/www/css');
        recurse_copy('../html/js', '../elixi_cordova/www/js');
    }
    if($build['web']) {
        $wHead = str_replace('../../', '', $head);
        $wArch = str_replace('../../', '', $arch);
        $prodDir = '../app.elixi.com/';
        if(!is_dir($prodDir)) {
            mkdir($prodDir);
        }
        file_put_contents($prodDir . 'index.html', trim($wHead . $wArch . $output));
        recurse_copy('../html/img', $prodDir . 'img');
        recurse_copy('../html/css', $prodDir . 'css');
        recurse_copy('../html/js', $prodDir . 'js');
        recurse_copy('../../3rdParty', $prodDir . '3rdParty');
    }
    chdir($dir);
    return $head . $arch . $output;
}

function recurse_copy($src, $dst) {
    $dir = opendir($src);
    mkdir($dst);
    while(false !== ($file = readdir($dir))) {
        if(($file != '.') && ($file != '..')) {
            if(is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}