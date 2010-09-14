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
    <meta name="copyright" content="(c) 2001-2010 phpMyFAQ Team">
    <meta name="publisher" content="{metaPublisher}">
    <meta name="robots" content="INDEX, FOLLOW">
    <meta name="revisit-after" content="7 days">
    <meta name="MSSmartTagsPreventParsing" content="true">
    
    <link rel="stylesheet" href="template/{tplSetName}/css/{stylesheet}.css?v=1">
    <link rel="stylesheet" media="handheld" href="template/{tplSetName}/css/handheld.css?v=1">
    <link rel="stylesheet" media="print" href="template/{tplSetName}/css/print.css?v=1">

    <script src="inc/js/modernizr.min.js"></script>
    
    <link rel="shortcut icon" href="template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="template/{tplSetName}/apple-touch-icon.png">
    
    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="feed/openquestions/rss.php">
    <link rel="microsummary" href="microsummary.php?action={action}">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">
</head>
<body dir="{dir}">

<div id="container">
    <header id="header">
        
        <div id="loginBox">
            <div id="loginSelection">
                <a href="#" onclick="javascript:loginForm();">{msgLoginUser}</a> | {msgRegisterUser}
            </div>
            <div id="loginForm">
                {loginBox}
            </div>
        </div>
        
        <h1><a title="{header}" href="{faqHome}">{header}</a></h1>
        
        <nav>
        <ul>
            <li>{msgSearch}</li>
            <li>{showInstantResponse}</li>
            <li>{msgAddContent}</li>
            <li>{msgQuestion}</li>
            <li>{msgOpenQuestions}</li>
            <li>{msgContact}</li>
        </ul>
        </nav>
    </header>

    <section id="maincolumns">
        <aside id="leftcolumn">
            <div id="categories">
                <nav>
                    <ul>
                        <li class="home">{backToHome}</li>
                        <li>{allCategories}</li>
                        {showCategories}
                        <li>{showSitemap}</li>
                    </ul>
                </nav>
            </div>
            <div id="useronline">
                {userOnline}
            </div>
        </aside>
        
        <section id="maincontent">
            [globalSearchBox]
            <aside id="searchBox">
            <form id="search" action="{writeSendAdress}" method="get">
                <input type="text" name="search" id="searchfield" size="30">
                <input type="hidden" name="searchcategory" value="{categoryId}">
                <input type="hidden" name="action" value="search">
                <input type="submit" name="submit" value="{searchBox}">
            </form>
            </aside>
            [/globalSearchBox]
            [globalSuggestBox]
            <aside id="searchBox">
            <form id="instantform" action="?action=instantresponse" method="post">
                <input type="hidden" name="ajaxlanguage" id="ajaxlanguage" value="{ajaxlanguage}">
                <input type="text" name="search" id="instantfield" value="">
            </form>
            </aside>
            [/globalSuggestBox]
            
            {writeContent}
        </section>
        
        <aside id="rightcolumn">
            
            {rightBox}
            
            <div id="stickyrecords">
            <h3>{stickyRecordsHeader}</h3>
            <ul>
                [stickyRecordsList]
                <li><a href="{stickyRecordsUrl}">{stickyRecordsTitle}</a></li>
                [/stickyRecordsList]
            </ul>
            </div>
            
        </aside>
    
    </section>
    
    <div class="clearfix"></div>
    <footer id="footer">
        <form action="{writeLangAdress}" method="post">
        <p id="copyrightnote">
            {copyright} | {switchLanguages} <input type="hidden" name="action" value="" />
        </p>
        </form>
    </footer>
    
    {debugMessages}

</div>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script>!window.jQuery && document.write('<script src="inc/js/jquery.min.js"><\/script>')</script>
<script src="inc/js/functions.js"></script>

</body>
</html>