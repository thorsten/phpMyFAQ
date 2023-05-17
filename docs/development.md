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

phpMyFAQ is developed using PHP, JavaScript and SCSS. You find further information on our
[homepage](https://www.phpmyfaq.de/contribute).
