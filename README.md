# konscp
A simple PHP script that transfers local files to remote server using `ssh2_scp_send`. Basically a stripped Linux version of WinSCP's "keep remote directory up to date".

## Requirements
PHP 4.3.0 or newer with ssh2 extension 0.9.0 or newer.

## Usage
1. Create a config file `config/my_config_file.php` with your SSH credientials. See `config/config_sample.php` for example.
2. Open konscp folder in terminal and run the `sync.php` with a `-c` option as your your config file name. Like this: `php sync.php -c my_config_file`
3. That's it, your files are now being wathed. Any new files made in the local directory will be also uploaded if the script is running.
