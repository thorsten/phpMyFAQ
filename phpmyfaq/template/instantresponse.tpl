    <h2>{msgInstantResponse}</h2>

    <script type="text/javascript" src="inc/js/prototype.js"></script>

    <form action="?action=instantresponse" method="post" onkeydown="new Ajax.Updater('instantresponse', 'ajaxresponse.php', {asynchronous:true, parameters:Form.serialize(this)}); return false;">
        <fieldset>
            <legend>{msgSearchWord}</legend>

            <input class="inputfield" id="search" type="text" name="search" size="50" value="{searchString}" />
            <input class="submit" type="submit" name="submit" value="{msgInstantResponse}" />

        </fieldset>
    </form>

    <div id="instantresponse">
    {printInstantResponse}
    </div>