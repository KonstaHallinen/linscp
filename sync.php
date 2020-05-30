<?php
/**
 * A simple PHP script that transfers local files to remote server using `ssh2_scp_send`.
 * See readme.md for examples and more information.
 */

// Function for outputting a single line message
function br($msg) {
    echo $msg . PHP_EOL;
} 

// Config
$config = !empty(getopt("c:")) ? getopt("c:")['c'] : false;
if($config) {
    // Path to config
    $config_file = getcwd() . '/config/' . $config . '.php';

    // Get config file
    if(require($config_file)) {
        br('Configuration file loaded: ' . $config_file);
    }
    else br("Can't find configuration file: " . $config_file);
} 
else br('Missing config parameter -c.');

// Check that all params are valid
if(!$local) {
    br('Local path or file not defined. Check your config file.');
    die();
}
else {
    br('Local: ' . $local);
}

if(!$remote) {
    br('Remote path or file not defined. Check your config file.');
    die();
}
else {
    br('Remote: ' . $remote);
}

if(!$refresh_time) {
    br('Refresh time not set. Using 10s. You can define this in your config file.');
    $refresh_time = 10;
}

$watch_local_folder = false;
if(substr($local, -1) == '/') {
    $watch_local_folder = true;
}

$watch_remote_folder = false;
if(substr($remote, -1) == '/') {
    $watch_remote_folder = true;
}

if($watch_local_folder && !$watch_remote_folder) {
    br("You can't copy a whole folder into a single file. Check your config file.");
    die();
}

if(!$watch_local_folder && $watch_remote_folder) {
    br("You need to define remote file name also if transferring only one file.");
    die();
}

// Try to connect
br('Connecting to remote server.');
if($connection = ssh2_connect($host, $port)) {

    br('Hostname and port are accessible.');

    if(ssh2_auth_password($connection, $username, $password)) {
        br('User authorised.');
    } else br('SSH authorisation failed.');
} else br("Can't connect to " . $host . ' using port ' . $port);

// Safety timer
br('Settings OK. You have 5 second to cancel (ctrl + c). Starting in...');
for($i = 0; $i < 5; $i++) {
    br(5 - $i);
    sleep(1);
}

// Track whole folder
if($watch_local_folder) {
    br('Keeping remote directory up to date. Refresh rate: ' . $refresh_time . '. Stop with ctrl + c');

    // Get initial file modification times times
    $mod_times = array();
    $files = array_diff(scandir($local), array('..', '.'));
    foreach ($files as $file) {
        if(!is_dir($local . $file)) {
            $mod_times[$file] = filemtime($local . $file);       
        }
    }

    // Run until user ends the operation
    while(true) {
        $files = array_diff(scandir($local), array('..', '.'));

        foreach ($files as $file) {

            // Get new mod time
            $filemtime = filemtime($local . $file);

            // If item is not a directory and mod time has changed
            if(!is_dir($local . $file) && $filemtime > $mod_times[$file]) {
                echo date('H:i:s') . ' - Change detected: ' . $local . $file . '. Uploading...';
                
                // Upload file
                $result = ssh2_scp_send($connection, $local . $file, $remote . $file, $permissions);
                // Update mod time to array
                $mod_times[$file] = $filemtime;

                if($result) {
                    br('Done.');
                }
                else {
                    br('Upload failed. Try to run the script again.');
                    die();
                }
            }
            else {
                // TODO update subdirectories
            }

        }
        sleep($refresh_time);
    }
}
// Single file transfer
else {
    br('Keeping remote file up to date. Refresh rate: ' . $refresh_time . '. Stop with ctrl + c');

    // Run until user ends the operations
    while(true) {
        $result = ssh2_scp_send($connection, $local, $remote, $permissions);
        if($result) {
            br(date('H:i:s - ') . 'File updated.');
        }
        else {
            br('Upload failed. Try to run the script again.');
            die();
        }
        sleep($refresh_time);
    }
}
