    <h2>{msgSearch}</h2>
    
	{printResult}
    
    <form action="{writeSendAdress}" method="post">
    <fieldset>
    <legend>{msgSearchWord}</legend>
	
    <input class="inputfield" type="text" name="suchbegriff" size="50" value="{searchString}" />
    <input class="submit" type="submit" name="submit" value="{msgSearch}" /><br />
        
    <label>{selectCategories}</label>
    <select name="searchcategory" size="1">
    <option value="%" selected="selected">{allCategories}</option>
        {printCategoryOptions}
    </select>
    
    {msgFirefoxPluginTitle}
    {msgMSIEPluginTitle}
    
    </fieldset>
	</form>