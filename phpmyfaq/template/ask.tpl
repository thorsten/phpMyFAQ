<h2>{msgQuestion}</h2>
	<p>{msgNewQuestion}</p>
	<form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgQuestion}</legend>
	
    <label for="username">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="username" id="username" value="{defaultContentName}" size="50" /><br />
	
    <label for="usermail">{msgNewContentMail}</label>
    <input class="inputfield" type="text" name="usermail" id="usermail" value="{defaultContentMail}" size="50" /><br />
	
    <label for="rubrik">{msgAskCategory}</label>
    <select name="rubrik" id="rubrik">
    {printCategoryOptions}
    </select><br />
	
    <label for="content">{msgAskYourQuestion}</label>
    <textarea class="inputarea" cols="50" rows="10" name="content" id="content"></textarea><br />
	
    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
	
    </fieldset>
    </form>