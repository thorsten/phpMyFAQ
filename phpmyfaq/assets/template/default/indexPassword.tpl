<!doctype html>
<!--[if lt IE 7 ]> <html lang="{metaLanguage}" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="{metaLanguage}" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="{metaLanguage}" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="{metaLanguage}" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="{metaLanguage}" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>{title}</title>
    <base href="{baseHref}" />

    <meta name="description" content="{metaDescription}">
    <meta name="author" content="{metaPublisher}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ {phpmyfaqversion}">
    <meta name="robots" content="INDEX, FOLLOW">
    <meta name="revisit-after" content="7 days">

    <!-- Share on Facebook -->
    <meta property="og:title" content="{title}" />
    <meta property="og:description" content="{metaDescription}" />
    <meta property="og:image" content="" />

    <link rel="stylesheet" href="assets/template/{tplSetName}/css/{stylesheet}.min.css?v=1">
    <link rel="shortcut icon" href="assets/template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="assets/template/{tplSetName}/apple-touch-icon.png">
    <link rel="canonical" href="{currentPageUrl}">

    <script src="assets/js/libs/modernizr.min.js"></script>
    <script src="assets/js/phpmyfaq.min.js"></script>

    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="{baseHref}feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="{baseHref}feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="{baseHref}feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="{baseHref}feed/openquestions/rss.php">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">
</head>
<body dir="{dir}">

<!--[if lt IE 8 ]>
<div class="internet-explorer-error">
    Did you know that your Internet Explorer is out of date?<br/>
    Please use Internet Explorer 8+, Mozilla Firefox 4+, Google Chrome, Apple Safari 5+ or Opera 11+
</div>
<![endif]-->

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="brand" title="{header}" href="{faqHome}">{header}</a>
        </div>
    </div>
</div>

<section id="content">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span3" id="leftContent">
            </div>
            <div class="span6" id="mainContent">
                <section>

                    <header>
                        <h2>{headerChangePassword}</h2>
                    </header>

                    <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">

                        <div class="control-group">
                            <label class="control-label">{msgUsername}</label>
                            <div class="controls">
                                <input type="text" name="username" required="required" autofocus="autofocus" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label">{msgEmail}</label>
                            <div class="controls">
                                <input type="email" name="email" required="required" />
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit" id="changepassword">
                                {msgSubmit}
                            </button>
                        </div>
                    </form>

                    <div id="loader"></div>
                    <div id="changepasswords"></div>

                    <script type="text/javascript" >
                        $(function() {
                            $('#changepassword').click(function() {
                                saveFormValues('changepassword', 'changepassword');
                            });
                            $('form#formValues').submit(function() { return false; });
                        });
                    </script>

                </section>
            </div>
            <div class="span3" id="rightContent">
            </div>
        </div>
    </div>
</section>

<footer id="footer" class="container-fluid">
    <div class="row-fluid">
        <div class="span12">
            <form action="{writeLangAdress}" method="post" class="pull-right" accept-charset="utf-8">
                {switchLanguages}
                <input type="hidden" name="action" value="" />
            </form>
        </div>
    </div>
    <div class="row">
        <p class="copyright pull-right">
            {copyright}
        </p>
    </div>
</footer>

{debugMessages}

</body>
</html>