# Using PhpMyFAQ with docker

## Dockerfile

The Dockerfile provided in this repo only build an environment to run any
release it's for devellopement purpose. It does not contain any code as the
phpmyfaq folder is meant to be mount as the `/var/www/html` folder in the
container.

To build a production release please use the git2docker.sh script or use images
provided on [docker.io](https://hub.docker.com/phpmyfaq).

## docker-compose.yml

For devellopment pupose you can start a full stack to run your current PhpMyFAQ
source code from your local repo.

    docker-compose up

The command above starts 5 containers as following.

_Specific images started once to prepare the project:_
- **composer**: update composer dependencies
- **yarn**: update yarn dependencies

_Running using named volumes:_
- **mariadb**: image with xtrabackup support
- **elasticsearch**: oss image (it means it does not have XPack installed)
- **phpmyadmin**: a php tool to have a look on your database.

_Running apache web server with php support:_
- **phpmyfaq**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

Then services will be available at following adresses:

- PhpMyFAQ: (http://localhost:8080)
- PhpMyAdmin: (http://localhost:8000)


## Quote from ElasticSearch documentation

The vm.max_map_count kernel setting needs to be set to at least 262144 for production use. Depending on your platform:

### Linux

The vm.max_map_count setting should be set permanently in _/etc/sysctl.conf_:

    $ grep vm.max_map_count /etc/sysctl.conf
    vm.max_map_count=262144

To apply the setting on a live system type: `sysctl -w vm.max_map_count=262144`

### macOS with Docker for Mac

The vm.max_map_count setting must be set within the xhyve virtual machine:

    $ screen ~/Library/Containers/com.docker.docker/Data/com.docker.driver.amd64-linux/tty

Log in with root and no password. Then configure the sysctl setting as you would for Linux:

    sysctl -w vm.max_map_count=262144

### Windows and macOS with Docker Toolbox

The vm.max_map_count setting must be set via docker-machine:

    docker-machine ssh
    sudo sysctl -w vm.max_map_count=262144
