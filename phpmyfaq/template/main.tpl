    <!-- begin news -->
    <div id="news">
    <h2><a href="feed/news/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeNewsHeader}</h2>
    {writeNews}
    <p align="center">{writeNumberOfArticles}</p>
    </div>
    
    <div id="topten">
    <table class="topten">
    <tr class="topten">
        <th><a href="feed/topten/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeTopTenHeader}</th>
    </tr>
    {writeTopTenRow}
    </table>
    </div>
    
    <div id="fivenewest">
    <table class="fivenewest">
    <tr>
        <th colspan="3"><a href="feed/latest/rss.php"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" /></a>{writeNewestHeader}</th>
    </tr>
    {writeNewestRow}
    </table>
    </div>
    <!-- end news -->