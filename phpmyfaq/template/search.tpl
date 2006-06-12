    <h2>{msgSearch}</h2>

	{printResult}

    <form action="{writeSendAdress}" method="get">
    <fieldset>
    <legend>{msgSearchWord}</legend>

    <input class="inputfield" type="text" name="search" size="50" value="{searchString}" />
    <input class="submit" type="submit" name="submit" value="{msgSearch}" />
    <input type="hidden" name="action" value="search" /><br />

    <label>{selectCategories}</label>
    <select name="searchcategory" size="1">
    <option value="%" selected="selected">{allCategories}</option>
        {printCategoryOptions}
    </select>

    {msgFirefoxPluginTitle}
    {msgMSIEPluginTitle}

    </fieldset>
	</form>

