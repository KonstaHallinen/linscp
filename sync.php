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
    else {
        br("Can't find configuration file: " . $config_file);
        die();
    }
} 
else {
    br('Missing config parameter -c.');
    die();
}

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
    } else {
        br('SSH authorisation failed.');
        die();
    }
} else {
    br("Can't connect to " . $host . ' using port ' . $port);
    die();
}

// Safety timer
br('Settings OK. You have 5 second to cancel (ctrl + c).');
sleep(5);

// Track whole folder
if($watch_local_folder) {
    br('Keeping remote directory up to date. Refresh rate: ' . $refresh_time . '. Stop with ctrl + c');

    // Get initial file modification times
    $mod_times = array();
    $rdi = new RecursiveDirectoryIterator($local);
    foreach (new RecursiveIteratorIterator($rdi) as $filepath => $item) {
        if(substr($filepath, -1) !== '.') {
            $mod_times[$filepath] = array(
                'modtime' => filemtime($filepath),
                'remote' => str_replace($local, $remote, $filepath),
                'tree' => false,
            );
        }
    }

    // Run until user ends the operation
    while(true) {
        $rdi = new RecursiveDirectoryIterator($local);
        foreach (new RecursiveIteratorIterator($rdi) as $filepath => $item) {
            if(substr($filepath, -1) !== '.' && !array_key_exists($filepath, $mod_times)) {
                $mod_times[$filepath] = array(
                    'modtime' => filemtime($filepath),
                    'remote' => str_replace($local, $remote, $filepath)
                    'tree' => false,
                );
            }

            if(array_key_exists($filepath, $mod_times)) {
                $filemtime = filemtime($filepath);
                if($filemtime > $mod_times[$filepath]['modtime']) {
                    echo date('H:i:s') . ' - Change detected: ' . $filepath . '. Uploading...';
                    
                    // create directories
                    if(!$mod_times[$filepath]['tree']) {
                        $dir = preg_replace('#[^/]*$#', '', $mod_times[$filepath]['remote']);
                        $sftp = ssh2_sftp($connection);
                        ssh2_sftp_mkdir($sftp, $dir, 0777, true);
                        $mod_times[$filepath]['tree'] = true;
                    }                    

                    // Upload file
                    $result = ssh2_scp_send($connection, $filepath, $mod_times[$filepath]['remote'], $permissions);
                    // Update mod time to array
                    $mod_times[$filepath]['modtime'] = $filemtime;

                    if($result) {
                        br('Done.');
                    }
                    else {
                        br('Upload failed. Try to run the script again.');
                        die();
                    }
                }
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
