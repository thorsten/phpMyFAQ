<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{metaLanguage}" lang="{metaLanguage}">
<head>
    <title>{title}</title>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset={metaCharset}" />
    <meta name="title" content="{title}" />
    <meta name="description" content="{metaDescription}" />
    <meta name="keywords" content="{metaKeywords}" />
    <meta name="author" content="{metaPublisher}" />
    <meta name="publisher" content="{metaPublisher}" />
    <meta name="copyright" content="(c) 2001 - 2005 phpMyFAQ Team" />
    <meta name="Content-Language" content="{metaCharset}" />
    <meta name="robots" content="INDEX, FOLLOW" />
    <meta name="revisit-after" content="7 days" />
    <meta name="MSSmartTagsPreventParsing" content="true" />
    <style type="text/css" media="screen">
    /*<![CDATA[*/
    <!--
    @import url(template/style.css);
    @import url(template/colors.css);
    -->
    /*]]>*/
    </style>
    <style type="text/css" media="print">
    /*<![CDATA[*/
    <!--
    @import "template/print.css";
    -->
    /*]]>*/
    </style>
    <link rel="shortcut icon" href="template/favicon.ico" />
    <link rel="alternate" title="News RSS Feed" type="application/rss+xml" href="feed/news/rss.php" />
    <link rel="alternate" title="TopTen RSS Feed" type="application/rss+xml" href="feed/topten/rss.php" />
    <link rel="alternate" title="Latest FAQ Records RSS Feed" type="application/rss+xml" href="feed/latest/rss.php" />
</head>
<body dir="{dir}">

<div id="wrapper1">
    <div id="wrapper2">
    
    <!-- start headers -->
    <div class="header" id="header">
        <h1><a title="{header}" href="{faqHome}">{header}</a></h1>
        <ul>
            <li>{msgContact}</li>
            <li>{msgHelp}</li>
            <li>{msgOpenQuestions}</li>
            <li>{msgQuestion}</li>
            <li>{msgAddContent}</li>
            <li>{msgSearch}</li>
        </ul>
    </div>
    <!-- end headers -->

    <!-- start columns -->
    <div class="columns">
    
        <!-- start left sidebar -->
        <div class="leftcolumn sidebar" id="sidebar-left">
            <div class="leftpadding">
                
                <h2 class="invisible">Navigation</h2>
                
                <!-- start categories -->
                <div class="content">
                    <div id="categories">
                    <ul>
                        <li class="home">{backToHome}</li>
                        <li>{allCategories}</li>
                        {showCategories}
                        <li>{showSitemap}</li>
                    </ul>
                    </div>
                </div>
                <!-- end categories -->
        
                <!-- start search box -->
                <div class="content">
                    <div id="search">
                    <form action="{writeSendAdress}" method="post">
                    <label for="suchbegriff">{searchBox}</label>
                    <input alt="search..." class="inputfield" type="text" name="suchbegriff" id="suchbegriff" size="18" /><br />
                    <input type="submit" name="submit" value="Go" class="submit" />
                    </form>
                    </div>
                </div>
                <!-- end search box -->
    
                <!-- start language selection box -->
                <div class="content">
                    <div id="langform">
                    <form action="{writeLangAdress}" method="post">
                    <label for="language">{languageBox}</label>
                    {switchLanguages}<br />
                    <input type="submit" name="submit" value="Go" class="submit" />
                    </form>
                    </div>
                </div>
                <!-- end -->
    
                <div class="content">
                    <div id="useronline">
                    {userOnline}
                    </div>
                </div>
            </div>
        </div>
        <!-- end left sidebar -->
    
        <!-- start right sidebar -->
        <div class="rightcolumn sidebar" id="sidebar-right">
            <div class="rightpadding">
            
                <div class="content">
                    <div id="topten">
                    <h3>{writeTopTenHeader}</h3>
                    {writeTopTenRow}
                    </div>
                </div>
                
                <div class="content">
                    <div id="latest">
                    <h3>{writeNewestHeader}</h3>
                    {writeNewestRow}
                    </div>
                </div>
                
            </div>
        </div>
        <!-- end right sidebar -->
    
        <!-- start main content -->
        <div class="centercolumn">
            <div class="centerpadding">
                <div class="main-content" id="main">
                {writeContent}
                </div>
            </div>
        </div>
        <!-- end main content -->

    </div>
    <!-- end columns -->
    
    <div class="clearing"></div>
		
    <!-- start footer -->
    <div id="footer" class="footer">
        <!-- please do not remove the following line -->
        <p id="copyrightnote">{copyright}</p>
    </div>
    <!-- end footer -->

    </div>
</div>    

</body>
</html>
