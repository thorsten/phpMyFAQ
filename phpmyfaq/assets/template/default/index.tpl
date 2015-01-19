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
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    <style> html{display:none;} </style>
</head>
<body dir="{dir}">

<div class="navbar navbar-default hidden-print" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#pmf-navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" title="{header}" href="{faqHome}">{header}</a>
        </div>

        <div class="navbar-collapse collapse" id="pmf-navbar-collapse">

            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <span title="{msgFullName}"><b class="fa fa-bars"></b> {msgLoginName}</span>
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li>{msgSearch}</li>
                        <li>{allCategories}</li>
                        <li>{msgAddContent}</li>
                        <li>{msgQuestion}</li>
                        <li>{msgOpenQuestions}</li>
                        <li class="divider"></li>
                        [notLoggedIn]
                        <li>{msgRegisterUser}</li>
                        <li>{msgLoginUser}</li>
                        [/notLoggedIn]
                        [userloggedIn]
                        <li>{msgUserControlDropDown}</li>
                        <li>{msgUserControl}</li>
                        <li>{msgLogoutUser}</li>
                        [/userloggedIn]
                    </ul>
                </li>
            </ul>

            <form class="navbar-form" role="search" id="search" action="{writeSendAdress}" method="get" accept-charset="utf-8">
                <div class="form-group">
                    <div class="input-group">
                        <input type="hidden" name="searchcategory" value="{categoryId}">
                        <input type="hidden" name="action" value="search">
                        <input type="text" class="form-control typeahead" name="search" id="searchfield"
                               autocomplete="off" autofocus placeholder="{searchBox} ...">
                    </div>
                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<section id="content" class="container">
    <div class="row">
        <div class="col-md-8" id="mainContent">

            {writeContent}

        </div>
        <div class="col-md-4 hidden-print" id="rightContent">

            {rightBox}

            [stickyFaqs]
            <section class="well">
                <header>
                    <h3>{stickyRecordsHeader}</h3>
                </header>
                <ul>
                    {stickyRecordsList}
                </ul>
            </section>
            [/stickyFaqs]
        </div>
    </div>
</section>

<footer id="footer" class="hidden-print">
    <div class="container">
        <div class="row">
            <div class="col-md-9">
                <ul class="footer-menu">
                    <li>{userOnline}</li>
                    <li>{showSitemap}</li>
                    <li>{msgContact}</li>
                    <li>{msgGlossary}</li>
                </ul>
            </div>
            <div class="col-md-3">
                <form action="{writeLangAdress}" method="post" class="pull-right" accept-charset="utf-8">
                    {switchLanguages}
                    <input type="hidden" name="action" value="" />
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p class="copyright pull-right">
                    {copyright}
                </p>
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

<script>
    $('.topten').tooltip();
    $('.latest-entries').tooltip();
    $('.sticky-faqs').tooltip();
</script>

</body>
</html>