<!doctype html>
<!--[if lt IE 7 ]> <html lang="{metaLanguage}" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="{metaLanguage}" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="{metaLanguage}" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="{metaLanguage}" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="{metaLanguage}" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    
    <title>{title}</title>
    <base href="{baseHref}" />
    
    <meta name="description" content="{metaDescription}">
    <meta name="author" content="{metaPublisher}">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ {phpmyfaqversion}">
    <meta name="copyright" content="(c) 2001-2013 phpMyFAQ Team">
    <meta name="publisher" content="{metaPublisher}">
    <meta name="robots" content="INDEX, FOLLOW">
    <meta name="revisit-after" content="7 days">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <!-- Share on Facebook -->
    <meta property="og:title" content="{title}" />
    <meta property="og:description" content="{metaDescription}" />
    <meta property="og:image" content="" />

    <link rel="stylesheet" href="assets/template/{tplSetName}/css/{stylesheet}.css?v=1">
    <link rel="shortcut icon" href="assets/template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="assets/template/{tplSetName}/apple-touch-icon.png">
    <link rel="canonical" href="{currentPageUrl}">

    <script src="assets/js/libs/modernizr.min.js"></script>
    <script src="assets/js/libs/jquery.min.js"></script>
    <script src="assets/js/phpmyfaq.js"></script>

    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="feed/openquestions/rss.php">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">
</head>
<body dir="{dir}">

<!--[if lt IE 8 ]>
<div class="internet-explorer-error">
    Do you know that your Internet Explorer is out of date?<br/>
    Please use Internet Explorer 8+, Mozilla Firefox 4+, Google Chrome, Apple Safari 5+ or Opera 11+
</div>
 <![endif]-->

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="brand" title="{header}" href="{faqHome}">{header}</a>
            <nav class="nav-collapse">
                <ul class="nav">
                    <li class="{activeQuickfind}">{showInstantResponse}</li>
                    <li class="{activeAddContent}">{msgAddContent}</li>
                    <li class="{activeAddQuestion}">{msgQuestion}</li>
                    <li class="{activeOpenQuestions}">{msgOpenQuestions}</li>
                </ul>
                <ul class="nav pull-right">
                    [notLoggedIn]
                    <li class="{activeRegister}">{msgRegisterUser}</li>
                    <li class="divider-vertical"></li>
                    <li class="{activeLogin}">{msgLoginUser}</li>
                    [/notLoggedIn]
                    [userloggedIn]
                    <li class="{activeUserControl}">{msgUserControl}</li>
                    <li class="divider-vertical"></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <span title="{msgFullName}">{msgLoginName}</span><b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li>{msgUserControlDropDown}</li>
                            <li>{msgLogoutUser}</li>
                        </ul>
                    </li>
                    [/userloggedIn]
                </ul>
            </nav>
        </div>
    </div>
</div>

<section id="content" class="container-fluid">
    <div class="row-fluid">
        <div class="span3" id="leftContent">
            <div class="well categories">
                <ul class="nav nav-list">
                    <li class="home">{backToHome}</li>
                    <li>{allCategories}</li>
                    <li class="divider-horizontal"></li>
                    {showCategories}
                </ul>
                <div class="users-online">
                    <small>{userOnline}</small>
                </div>
            </div>
        </div>
        <div class="span6" id="mainContent">
            [globalSearchBox]
            <section class="well" id="searchBox">
                <form id="search" action="{writeSendAdress}" method="get" class="form-search">
                    <div class="input-append">
                        <input type="hidden" name="searchcategory" value="{categoryId}" />
                        <input type="hidden" name="action" value="search" />
                        <input type="search" name="search" id="searchfield" size="30" placeholder="{searchBox} ..."
                               class="input-xlarge search-query" />
                        <button class="btn btn-primary" type="submit" name="submit">
                            {searchBox}
                        </button>
                    </div>
                </form>
                <small>{msgSearch}</small>
            </section>
            [/globalSearchBox]
            [globalSuggestBox]
            <section class="well" id="searchBox">
                <form id="instantform" action="?action=instantresponse" method="post" class="form-search">
                    <input type="hidden" name="ajaxlanguage" id="ajaxlanguage" value="{ajaxlanguage}" />
                    <input type="search" name="search" id="instantfield" class="input-xxlarge search-query" value=""
                           placeholder="{msgDescriptionInstantResponse}" />
                </form>
                <small>{msgSearch}</small>
            </section>
            [/globalSuggestBox]
            {writeContent}
        </div>
        <div class="span3" id="rightContent">
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

<footer id="footer" class="container-fluid">
    <div class="row-fluid">
        <div class="span6">
            <ul class="footer-menu">
                <li>{showSitemap}</li>
                <li>{msgContact}</li>
                <li>{msgGlossary}</li>
            </ul>
        </div>
        <div class="span6">
            <form action="{writeLangAdress}" method="post" class="pull-right">
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

</body>
</html>