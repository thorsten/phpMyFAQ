    <h2>{msgInstantResponse}</h2>

    <form action="{writeSendAdress}" method="get">
        <fieldset>
            <legend>{msgSearchWord}</legend>

            <input class="inputfield" id="searchfield" type="text" name="search" size="50" value="{searchString}" />
            <input class="submit" type="submit" name="submit" value="{msgSearch}" />
            <input type="hidden" name="action" value="search" />

        </fieldset>
    </form>

    <div id="instantresponse">
    {printInstantResponse}
    </div>