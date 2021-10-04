<?php

// SSH connection details
$host = 'exampledomain.com'; // SSH hostname or ip
$port = 22; // SSH port, usually 22
$username = 'your_ssh_user'; // SSH username
$password = 'your_ssh_password'; // SSH password
$permissions = 0644; // File permissions set after SCP transfer is complete, 0644 is fine for files

// Other settings
$local = '/path/to/local/folder/'; // This could be a single file also. Like: /path/to/local/file.txt
$remote = '/path/to/remote/folder/'; // This could be a single file also. Like: /path/to/remote/file.txt
$refresh_time = 5; // Refresh time in seconds
