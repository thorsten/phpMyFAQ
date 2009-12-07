    <form action="{writeSendAdress}" method="get">
            <input class="inputfield" id="searchfield" type="text" name="search" size="50" value="{searchString}" />
            <input class="submit" type="submit" name="submit" value="{msgSearch}" />
            <input type="hidden" name="action" value="search" />
    </form>
        <a href="index.php?action=artikel&cat=286889&id=144&artlang=de" style="font-size: 10px;">Wie kann ich im FAQ-System Suchbegriffe eingeben?</a>
        <hr/>
        
    {printResult}
