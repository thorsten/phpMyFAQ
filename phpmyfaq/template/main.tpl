    <!-- begin news -->
    <div id="news">
    <h2><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" />{writeNewsHeader}</h2>
    {writeNews}
    <p align="center">{writeNumberOfArticles}</p>
    </div>
    
    <div id="topten">
    <table class="topten">
    <tr class="topten">
        <th><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" />{writeTopTenHeader}</th>
    </tr>
    {writeTopTenRow}
    </table>
    </div>
    
    <div id="fivenewest">
    <table class="fivenewest">
    <tr>
        <th colspan="3"><img src="images/rss.png" width="28" height="16" alt="RSS" title="RSS" />{writeNewestHeader}</th>
    </tr>
    {writeNewestRow}
    </table>
    </div>
    <!-- end news -->