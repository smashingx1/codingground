<?php

if((isset($argv) && $argv[1] == 'dev') || (isset($_REQUEST) && isset($_REQUEST['publish']) && $_REQUEST['publish'] == 'dev')) {
    set_time_limit(0);
    git('reset --hard', true);
    $test = git('pull', true);
    git('checkout dev', true);
    /* SeLinux will not execute php files with apache that have group write permissions */
    exec('chmod -R 755 .');

    chdir('../../archetype');
    git('config user.email "gitbot@motocol.com"');
    git('reset --hard origin/dev', true);
    git('pull', true);
    git('checkout dev', true);
    /* SeLinux will not execute php files with apache that have group write permissions */
    exec('chmod -R 755 .');

    chdir('../Elixi/build');
    require_once('html.php');
    buildIndex($chunks, array('web' => true));
    if(count($test) > 2) {
        require_once('assemble.php');
    }
}

/**
 * A wrapper for git that loads the appropriate ssh key.
 * Syntax is 'git $cmd'
 * Almost all commands require a repo, clone being the exception (Don't pass $repo with clone commands!)
 *
 * @param string $cmd The git command to be executed
 * @param string $repo The repository to work in
 * @return Array The output from git, one line per element
 */
function git($cmd, $dbug = false) {
    $cmd = 'ssh-agent bash -c \'ssh-add /home/rchetype/.ssh/jgitkey; git ' . $cmd . "'" . ($dbug ? " 2>&1" : "");
    if($dbug) {
        echo $cmd . "<br/>";
    }
    exec($cmd, $output);
    if($dbug) {
        for($i = 0; $i < count($output); $i++) {
            echo($i + 1 . ". " . $output[$i] . "<br />");
        }
    }
    return $output;
}