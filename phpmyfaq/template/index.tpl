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
    <meta name="robots" content="INDEX, NOFOLLOW" />
    <meta name="revisit-after" content="7 days" />
    <meta name="MSSmartTagsPreventParsing" content="true" />
    <style type="text/css" media="screen">@import "template/style.css";</style>
    <style type="text/css" media="print">@import "template/print.css";</style>
	<script src="inc/functions.js" type="text/javascript"></script>
    <link rel="shortcut icon" href="template/favicon.ico" />
</head>
<body dir="{dir}">

<div id="phpmyfaq">
    <!-- start header -->
    <div id="header">
        <h1><a title="{header}" href="{faqHome}">{header}</a></h1>
        <div id="horizontalnav">
        <ul>
            <li>{msgSearch}</li>
            <li>{msgAddContent}</li>
            <li>{msgQuestion}</li>
            <li>{msgOpenQuestions}</li>
            <li>{msgHelp}</li>
            <li>{msgContact}</li>
        </ul>
        </div>
    </div>
    <!-- end header -->
    
    <!-- start categories -->
    <div id="categories">
        <div id="mainnav">
        <ul>
            <li>{backToHome}</li>
            <li>{allCategories}</li>
            {showCategories}
        </ul>
        </div>
        <div id="search">
        <form action="{writeSendAdress}" method="post">
        <fieldset>
        <legend>{searchBox}</legend>
        <input alt="search..." class="inputfield" type="text" name="suchbegriff" size="18" />
        <input type="submit" name="submit" value="Go" class="submit" />
        </fieldset>
        </form>
        </div>
        <div id="langform">
        <form action="{writeLangAdress}" method="post">
        <fieldset>
        <legend>{languageBox}</legend>
        {switchLanguages}<input type="submit" name="submit" value="Go" class="submit" />
        </fieldset>
        </form>
        </div>
        <div id="useronline">
        {userOnline}
        </div>
    </div>
    <!-- end categories -->
    
    <!-- begin content -->
    <div id="content">
    {writeContent}
    <!-- please do not remove the following line -->
    <p id="copyrightnote">{copyright}</p>
    </div>
    <!-- end content -->
</div>

</body>
</html>
