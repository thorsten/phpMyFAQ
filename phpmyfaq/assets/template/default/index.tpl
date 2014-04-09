<!doctype html>
<!--[if IE 9 ]> <html lang="{metaLanguage}" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="{metaLanguage}" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>{title}</title>
    <base href="{baseHref}" />

    <meta name="description" content="{metaDescription}">
    <meta name="keywords" content="{metaKeywords}">
    <meta name="author" content="{metaPublisher}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ {phpmyfaqversion}">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="7 days">

    <!-- Share on Facebook -->
    <meta property="og:title" content="{title}" />
    <meta property="og:description" content="{metaDescription}" />
    <meta property="og:image" content="" />

    <link rel="stylesheet" href="{baseHref}assets/template/{tplSetName}/css/{stylesheet}.min.css?v=1">
    <link rel="shortcut icon" href="{baseHref}assets/template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="{baseHref}assets/template/{tplSetName}/apple-touch-icon.png">
    <link rel="canonical" href="{currentPageUrl}">

    <script src="{baseHref}assets/js/libs/modernizr.min.js"></script>
    <script src="{baseHref}assets/js/phpmyfaq.min.js"></script>

    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="{baseHref}feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="{baseHref}feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="{baseHref}feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="{baseHref}feed/openquestions/rss.php">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">
</head>
<body dir="{dir}">

<nav class="navbar navbar-default hidden-print" role="navigation">
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

        <div class="collapse navbar-collapse" id="pmf-navbar-collapse">
            <ul class="nav navbar-nav">
                <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">{headerCategories}
                    <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li>{allCategories}</li>
                        <li class="divider"></li>
                        [categoryListSection]
                        {showCategories}
                        [/categoryListSection]
                    </ul>
                </li>
                <li class="{activeQuickfind}">{showInstantResponse}</li>
                <li class="{activeAddContent}">{msgAddContent}</li>
                <li class="{activeAddQuestion}">{msgQuestion}</li>
                <li class="{activeOpenQuestions}">{msgOpenQuestions}</li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                [notLoggedIn]
                <li class="{activeRegister}">{msgRegisterUser}</li>
                <li class="divider-vertical"></li>
                <li class="{activeLogin}">{msgLoginUser}</li>
                [/notLoggedIn]
                [userloggedIn]
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <span title="{msgFullName}">{msgLoginName}</span><b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li>{msgUserControlDropDown}</li>
                        <li>{msgUserControl}</li>
                        <li>{msgLogoutUser}</li>
                    </ul>
                </li>
                [/userloggedIn]
            </ul>
        </div>
    </div>
</nav>

<section id="content" class="container">
    <div class="row">
        <div class="col-md-8" id="mainContent">
            [globalSearchBox]
            <section class="well hidden-print" id="searchBox">
                <form id="search" action="{writeSendAdress}" method="get" class="form-search" accept-charset="utf-8">
                    <div class="input-append">
                        <input type="hidden" name="searchcategory" value="{categoryId}" />
                        <input type="hidden" name="action" value="search" />
                        <input type="text" name="search" id="searchfield" placeholder="{searchBox} ..."
                               class="form-control">
                        <button class="btn btn-primary" type="submit" name="submit">
                            {searchBox}
                        </button>
                    </div>
                </form>
                <small>{msgSearch}</small>
            </section>
            [/globalSearchBox]
            [globalSuggestBox]
            <section class="well hidden-print" id="searchBox">
                <form id="instantform" action="?action=instantresponse" method="post" class="form-search" accept-charset="utf-8">
                    <input type="hidden" name="ajaxlanguage" id="ajaxlanguage" value="{ajaxlanguage}" />
                    <input type="search" name="search" id="instantfield" class="form-control" value=""
                           placeholder="{msgDescriptionInstantResponse}" />
                </form>
                <small>{msgSearch}</small>
            </section>
            [/globalSuggestBox]

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