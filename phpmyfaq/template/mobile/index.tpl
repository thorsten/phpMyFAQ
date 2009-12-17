<!DOCTYPE html>
<html lang="{metaLanguage}">
<head>
    <title>{title}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="user-scalable=no, width=device-width" />
    <style type="text/css" media="only screen and (max-device-width: 480px)">@import url(template/mobile/{stylesheet}.css);</style>
    <script type="text/javascript" src="inc/js/jquery.min.js"></script>
    <script type="text/javascript">
    /*<![CDATA[*/ //<!--
    if (window.innerWidth && window.innerWidth <= 480) {
        $(document).ready(function(){
            $('#header ul').addClass('hide');
            $('#header').append('<div class="leftButton" onclick="toggleMenu()">Menu</div>');
        });
        function toggleMenu() { 
            $('#header ul').toggleClass('hide');
            $('#header .leftButton').toggleClass('pressed');
        }
    }
    // --> /*]]>*/
    </script>
</head>
<body>
<section id="wrapper">
    <header id="header">
        <!-- <div class="leftButton" onclick="toggleMenu()">Menu</div> -->
        <h1><a href="{faqHome}">phpMyFAQ</a></h1>
        <nav>
            <ul>
                <li>{msgSearch}</li>
                <li>{showInstantResponse}</li>
                <li>{msgQuestion}</li>
            </ul>
        </nav>
    </header>
    <article>
        <section id="categories">
            <nav>
                <ul>
                    <li class="home">{backToHome}</li>
                    <li>{allCategories}</li>
                    {showCategories}
                    <li>{showSitemap}</li>
                </ul>
            </nav>
        </section>
        <section id="content">
        
            {writeContent}
        
        </section>
    </article>
    <footer>{copyright}</footer>
</section>
</body>
</html>