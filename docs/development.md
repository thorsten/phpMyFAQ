# 6. Developer documentation

## 6.1 Customizing phpMyFAQ

phpMyFAQ users have even more customization opportunities. The key feature is the user selectable template sets, there
is a templates/default directory where the default layouts get shipped.

In phpMyFAQ code and layout are separated. The layout is based on several template files, that you can modify to suit
your own needs. The most important files for phpMyFAQ's default layout can be found in the directory
_assets/themes/default/_. All original templates are valid HTML5 based on Bootstrap v5.3

### 6.1.1 Creating a custom layout

Follow these steps to create a custom template set:

- copy the directory assets/themes/default to assets/themes/example
- adjust template files in assets/themes/example to fit your needs
- activate "example" within Admin->Config->Main

**Note:** There is a magic variable _{{ tplSetName }}_ containing the name of the actual layout available in each
template file.

### 6.1.2 DEBUG mode

If you want to see possible errors or the log of the SQL queries, you can enable the hidden DEBUG mode. To do so, please
set the following code in src/Bootstrap.php:

`const DEBUG = true;`

## 6.2 HTML Structure

The default layout of phpMyFAQ is saved in the **assets/themes/default/templates/index.html** file. This is a normal
HTML5 file including some variables in double curly brackets like Twig or Handlebars, serving as placeholders for
content.

Example:

`<span class="useronline">{{ userOnline }}</span>`

The template engine of the FAQ converts the placeholder _{{ userOnline }}_ to the actual number of visitors online.

You can change the template as you wish, but you may want to keep the original template in case something goes wrong.

## 6.3 REST APIs

phpMyFAQ offers interfaces to access phpMyFAQ installations with other clients like the iPhone App. phpMyFAQ includes a
REST API and offers APIs for various services like fetching the phpMyFAQ version or doing a search against the
phpMyFAQ installation.

The API documentation can be found in our [GitHub repository](https://github.com/thorsten/phpMyFAQ/blob/main/API.md).

## 6.4 phpMyFAQ development

Since phpMyFAQ is an Open Source project, we encourage developers to contribute patches and code for us to include in
the main package of phpMyFAQ.
However, there are a few rules and limitations when doing so and this page lists them.

1. Contributed code will be licensed under the MPL 2.0 license.
2. Copyright notices will be changed to phpMyFAQ Team. But contributors will get credit for their work!
3. All third party code will be reviewed, tested and possible modified before being released.

These basic rules makes it possible for us to earn a living of the phpMyFAQ project, but it also ensures that the code
remains Open Source and under the MPL 2.0 license.
All contributions will be added to the changelog and on the phpMyFAQ website.

### 6.4.1 How to contribute?

Contributing to phpMyFAQ is quite easy: just fork the [project on GitHub](https://github.com/thorsten/phpMyFAQ),
work on your copy and send pull requests.

### 6.4.2 Setup a local phpMyFAQ development environment

Before working on phpMyFAQ, set up a local environment with the following software:

- Git
- PHP 8.2+
- PHPUnit 10.x
- Composer
- Node.js v18+
- Yarn v3
- Docker

### 6.4.3 Configure your Git installation

Set up your user information with your real name and a working e-mail address:

    $ git config --global user.name "Your Name"
    $ git config --global user.email you@example.com
    $ git config core.autocrlf # if you're on Windows

### 6.4.4 How to get the phpMyFAQ source code?

Clone your forked phpMyFAQ repository locally:

    $ git clone git@github.com:USERNAME/phpMyFAQ.git

Add the upstream repository as remote:

    $ cd phpMyFAQ
    $ git remote add upstream git://github.com/thorsten/phpMyFAQ.git

Please check our [coding standards](https://www.phpmyfaq.de/docs/standards) before sending patches or pull requests.
Every PR on Github will check the coding standards and tests as well.

### 6.4.5 Run Docker Compose

The Dockerfile provided in the phpMyFAQ repository only builds an environment
to run any release for development purpose.
It does not contain any code as the phpmyfaq folder is meant to be mount as the /var/www/html folder in the container.

For development purposes you can start a full stack to run your current phpMyFAQ source code from your local repository.

    $ docker-compose up

The command above starts 9 containers for multi database development as following.

_Specific images started once to prepare the project:_

- **composer**: update composer dependencies
- **yarn**: update yarn dependencies

_Running using named volumes:_

- **mariadb**: image with MariaDB database with xtrabackup support
- **phpmyadmin**: a PHP tool to have a look on your MariaDB database.
- **postgres**: image with PostgreSQL database
- **pgadmin**: a PHP tool to have a look on your PostgreSQL database.
- **sqlserver**: image with Microsoft SQL Server for Linux
- **elasticsearch**: Open Source Software image (it means it does not have XPack installed)

_Running apache web server with PHP 8.2 support:_

- **phpmyfaq**: mounts the `phpmyfaq` folder in place of `/var/www/html`.

Then services will be available at the following addresses:

- phpMyFAQ: (http://localhost:8080 or https://localhost:443)
- phpMyAdmin: (http://localhost:8000)
- pgAdmin: (http://localhost:8008)

### 6.4.6 Fetch 3rd party libraries and install phpMyFAQ

After cloning your forked repository, you have to fetch the 3rd party libraries used in phpMyFAQ:

    $ cd phpMyFAQ
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ yarn install
    $ yarn build

Then just start a normal, local phpMyFAQ installation.

If you change some JavaScript code, you have to re-build the .js files into one with the following yarn task:

    $ yarn build

During development, you can use the watch mode:

    $ yarn build:watch

To run the PHPUnit based tests, you can use the following command:

    $ composer test

To run the Jest based tests, you can use the following command:

    $ yarn test

### 6.4.7 Rebase your Patch

Before submitting your patch, please update your local branch:

    $ git checkout 3.2
    $ git fetch upstream
    $ git merge upstream/3.2
    $ git checkout YOUR_BRANCH_NAME
    $ git rebase 3.2

### 6.4.8 Make a Pull Request

You can now make a pull request on the phpMyFAQ Github repository.
