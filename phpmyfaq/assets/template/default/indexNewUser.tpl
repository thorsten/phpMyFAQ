<!doctype html>
<!--[if IE 9 ]> <html lang="{metaLanguage}" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="{metaLanguage}" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>{title}</title>
    <base href="{baseHref}">

    <meta name="description" content="{metaDescription}">
    <meta name="keywords" content="{metaKeywords}">
    <meta name="author" content="{metaPublisher}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ {phpmyfaqversion}">
    <meta name="robots" content="{metaRobots}">
    <meta name="revisit-after" content="7 days">

    <!-- Share on Facebook -->
    <meta property="og:title" content="{title}">
    <meta property="og:description" content="{metaDescription}">
    <meta property="og:image" content="">

    <link rel="stylesheet" href="{baseHref}assets/template/{tplSetName}/css/{stylesheet}.min.css?v=1">
    <link rel="shortcut icon" href="{baseHref}assets/template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="{baseHref}assets/template/{tplSetName}/apple-touch-icon.png">
    <link rel="canonical" href="{currentPageUrl}">

    <script src="{baseHref}assets/js/modernizr.min.js"></script>
    <script src="{baseHref}assets/js/phpmyfaq.min.js"></script>

    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="{baseHref}feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="{baseHref}feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="{baseHref}feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="{baseHref}feed/openquestions/rss.php">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">

    <script>
        if (self === top) {
            document.documentElement.style.display = 'block';
        } else {
            top.location = self.location;
        }
    </script>
    <style> html{display:none;} </style>
</head>
<body dir="{dir}">

<header>
    <div class="pmf-wrapper pmf-masthead">
        <div class="container">
            <a id="logo" title="{header}" href="{faqHome}">
                <img src="{baseHref}assets/template/{tplSetName}/img/logo.png" alt="phpMyFAQ">
            </a>

            <div id="mobile-nav-toggle" class="pull-right">
                <a href="#" data-toggle="collapse" data-target=".pmf-nav .navbar-collapse">
                    <i aria-hidden="true" class="fa fa-bars"></i>
                </a>
            </div>

            <nav class="pull-right pmf-nav">
                <div class="collapse navbar-collapse">
                    <ul class="nav nav-pills navbar-nav">
                        <li>{msgLoginUser}</li>
                    </ul>
                </div>
            </nav>

        </div>
    </div>

    <div class="pmf-wrapper pmf-subheader">
        <div class="container">
            <div class="pmf-breadcrumb">
                [breadcrumb]
                {breadcrumbHeadline}
                [/breadcrumb]
            </div>
        </div>
    </div>
</header>

<div class="pmf-wrapper pmf-main">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="row">
                    <section>

                        {writeContent}

                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="pmf-wrapper pmf-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-md-offset-9">
                    <form action="{writeLangAdress}" method="post" class="pull-right" accept-charset="utf-8">
                        {switchLanguages}
                        <input type="hidden" name="action" value="" />
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="pmf-wrapper copyright">
        <div class="container">
            <div class="pull-right">
                {copyright}
            </div>
        </div>
    </div>

    [debugMode]
    <div class="container debug-mode">
        <h3>DEBUG INFORMATION</h3>
        <hr>
        <h4>EXCEPTIONS</h4>
        {debugExceptions}
        <hr>
        <h4>DATABASE QUERIES</h4>
        {debugQueries}
    </div>
    [/debugMode]

</footer>

</body>
</html>
