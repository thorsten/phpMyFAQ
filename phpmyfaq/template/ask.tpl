<h2>{msgQuestion}</h2>
	<p>{msgNewQuestion}</p>
	<form action="{writeSendAdress}" method="post" style="display: inline">
	
    <div class="row"><span class="label">{msgNewContentName}</span>
    <input class="inputfield" type="text" name="username" size="50" /></div>
	
    <div class="row"><span class="label">{msgNewContentMail}</span>
    <input class="inputfield" type="text" name="usermail" size="50" /></div>
	
    <div class="row"><span class="label">{msgAskCategory}</span>
    <select name="rubrik" size="1">
    {printCategoryOptions}
    </select></div>
	
    <div class="row"><span class="label">{msgAskYourQuestion}</span>
    <textarea class="inputarea" cols="50" rows="10" name="content"></textarea></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" /></div>
	
    </form>