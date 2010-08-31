<!doctype html>
<html lang="{metaLanguage}" class="no-js">
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
    <script src="inc/js/functions.js"></script>
    <script src="inc/js/jquery.min.js"></script>
    
    <link rel="shortcut icon" href="template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="template/{tplSetName}/apple-touch-icon.png">
    
    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="feed/news/rss.php">
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="feed/topten/rss.php">
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="feed/latest/rss.php">
    <link rel="alternate" title="Open Questions RSS Feed" type="application/rss+xml" href="feed/openquestions/rss.php">
    <link rel="microsummary" href="microsummary.php?action={action}">
    <link rel="search" type="application/opensearchdescription+xml" title="{metaTitle}" href="{opensearch}">
</head>
<body dir="{dir}" onload="javascript:focusOnSearchField();">

<div id="container">
    <header>
        <h1><a title="{header}" href="{faqHome}">{header}</a></h1>
        <nav>
        <ul>
            <li>{msgContact}</li>
            <li>{msgHelp}</li>
            <li>{msgOpenQuestions}</li>
            <li>{msgQuestion}</li>
            <li>{msgAddContent}</li>
            <li>{showInstantResponse}</li>
            <li>{msgSearch}</li>
        </ul>
        <nav>
    </header>

    <section>
        <div class="leftcolumn">
            <h2 class="invisible">Navigation</h2>
            <div class="content">
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
            </div>

            <div class="content">
                <div id="langform">
                <form action="{writeLangAdress}" method="post">
                <label for="language">{languageBox}</label>
                {switchLanguages}
                <input type="hidden" name="action" value="" />
                </form>
                </div>
            </div>

            <div class="content">
                <div id="loginform">
                {loginBox}
                </div>
            </div>

            <div class="content">
                <div id="useronline">
                {userOnline}
                </div>
            </div>
        </div>
        
        <div class="rightcolumn">
        {rightBox}
                
            <div class="content">
                <div id="stickyrecords">
                <h3>{stickyRecordsHeader}</h3>
                <ul>
                    [stickyRecordsList]
                    <li><a href="{stickyRecordsUrl}">{stickyRecordsTitle}</a></li>
                    [/stickyRecordsList]
                </ul>
                </div>
           </div>
        </div>
        
        <div class="main-content">
        [globalSearchBox]
        <form id="search" action="{writeSendAdress}" method="get">
             <input type="text" name="search" id="searchfield" size="30" />
             <input type="submit" name="submit" value="{searchBox}" />
             <input type="hidden" name="searchcategory" value="{categoryId}" />
             <input type="hidden" name="action" value="search" />
         </form>
        [/globalSearchBox]
        [globalSuggestBox]
        <form id="instantform" action="?action=instantresponse" method="post">
        <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
        <input class="inputfield" id="instantfield" type="text" name="search" value="" />
        </form>
        [/globalSuggestBox]
        
        {writeContent}
    
        </div>
    </div>
    
    <div class="clearing"></div>
    <footer>
        <p id="copyrightnote">{copyright}</p>
    </footer>
    
    {debugMessages}

</div>

</body>
</html>