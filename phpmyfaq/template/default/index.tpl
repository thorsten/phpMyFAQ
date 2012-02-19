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
    <meta name="copyright" content="(c) 2001-2012 phpMyFAQ Team">
    <meta name="publisher" content="{metaPublisher}">
    <meta name="robots" content="INDEX, FOLLOW">
    <meta name="revisit-after" content="7 days">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <!-- Share on Facebook -->
    <meta property="og:title" content="{title}" />
    <meta property="og:description" content="{metaDescription}" />
    <meta property="og:image" content="" />

    <link rel="stylesheet" href="template/{tplSetName}/css/{stylesheet}.css?v=1">

    <script src="js/libs/modernizr.min.js"></script>
    <script src="js/libs/jquery.min.js"></script>
    <script src="js/functions.js"></script>
    <script src="js/phpmyfaq.js"></script>

    <link rel="shortcut icon" href="template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="template/{tplSetName}/apple-touch-icon.png">
    
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
                    <li class="divider"></li>
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

<section id="main">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span3">
                <div class="well categories">
                    <ul class="nav nav-list">
                        <li class="home">{backToHome}</li>
                        <li>{allCategories}</li>
                        <li class="divider-horizontal"></li>
                        {showCategories}
                    </ul>
                </div>
                {userOnline}
            </div>

            <div class="span6 main-content">
                [globalSearchBox]
                <section class="search-box">
                    <form id="search" action="{writeSendAdress}" method="get">
                        <input type="hidden" name="searchcategory" value="{categoryId}" />
                        <input type="hidden" name="action" value="search" />
                        <input type="search" name="search" id="searchfield" size="30" placeholder="{searchBox} ..." />
                        <input type="submit" name="submit" value="{searchBox}" />
                    </form>
                    {msgSearch}
                </section>
                [/globalSearchBox]
                [globalSuggestBox]
                <section class="search-box">
                    <form id="instantform" action="?action=instantresponse" method="post">
                        <input type="hidden" name="ajaxlanguage" id="ajaxlanguage" value="{ajaxlanguage}" />
                        <input type="search" name="search" id="instantfield" value=""
                               placeholder="{msgDescriptionInstantResponse}" onfocus="autoSuggest(); return false;" />
                    </form>
                    {msgSearch}
                </section>
                [/globalSuggestBox]

                {writeContent}
            </div>

            <div class="span3">
                {rightBox}
                <section class="well">
                    <header>
                        <h3>{stickyRecordsHeader}</h3>
                    </header>
                    <ul>
                        [stickyRecordsList]
                        <li><a href="{stickyRecordsUrl}">{stickyRecordsTitle}</a></li>
                        [/stickyRecordsList]
                    </ul>
                </section>
            </div>

        </div>
    </div>
</section>

<footer>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span6">
                <ul class="footer-menu">
                    <li>{showSitemap}</li>
                    <li>{msgContact}</li>
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
    </div>
</footer>

</body>
</html>