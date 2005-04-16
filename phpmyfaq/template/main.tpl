    <!-- begin news -->
    <div id="news">
    <h2><a href="feed/news/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeNewsHeader}</h2>
    {writeNews}
    <p align="center">{writeNumberOfArticles}</p>
    </div>
    
    <div id="topten">
    <h3><a href="feed/topten/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeTopTenHeader}</h3>
    {writeTopTenRow}
    </div>
    
    <div id="fivenewest">
    <h3><a href="feed/topten/rss.php"><a href="feed/latest/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeNewestHeader}</h3>
    {writeNewestRow}
    </div>
    <!-- end news -->