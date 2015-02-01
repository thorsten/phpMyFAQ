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
    <meta name="robots" content="noindex,nofollow">
    <meta name="revisit-after" content="7 days">

    <link rel="stylesheet" href="{baseHref}assets/template/{tplSetName}/css/{stylesheet}.min.css?v=1">
    <link rel="shortcut icon" href="{baseHref}assets/template/{tplSetName}/favicon.ico">
    <link rel="apple-touch-icon" href="{baseHref}assets/template/{tplSetName}/apple-touch-icon.png">
    <link rel="canonical" href="{currentPageUrl}">

    <script src="{baseHref}assets/js/modernizr.min.js"></script>
    <script src="{baseHref}assets/js/phpmyfaq.min.js"></script>

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
                        [notLoggedIn]
                        <li>{msgLoginUser}</li>
                        [/notLoggedIn]
                        [userloggedIn]
                        <li>{msgUserControl}</li>
                        <li>{msgLogoutUser}</li>
                        [/userloggedIn]
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<section id="content">
    <div class="container">
        <div class="row">
            <div class="jumbotron text-center">
                <h3>We are performing scheduled maintenance operations. Please visit us later.</h3>
            </div>
        </div>
    </div>
</section>

<footer id="footer" class="hidden-print">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p class="copyright pull-right">
                    {copyright}
                </p>
            </div>
        </div>
    </div>
</footer>

</body>
</html>