<h2>{msgNewContentHeader}</h2>
	<p>{msgNewContentAddon}</p>
	<form action="{writeSendAdress}" method="post" style="display: inline">
	
    <div class="row"><span class="label">{msgNewContentName}</span>
    <input class="inputfield" type="text" name="username" size="50" /></div>
	
    <div class="row"><span class="label">{msgNewContentMail}</span>
    <input class="inputfield" type="text" name="usermail" size="50" /></div>
	
    <div class="row"><span class="label">{msgNewContentCategory}</span>
    <select name="rubrik" size="1">
    {printCategoryOptions}
    </select></div>
	
    <div class="row"><span class="label">{msgNewContentTheme}</span>
    <textarea class="inputarea" cols="50" rows="3" name="thema">{printQuestion}</textarea></div>
	
    <div class="row"><span class="label">{msgNewContentArticle}</span>
    <textarea class="inputarea" cols="50" rows="10" name="content"></textarea></div>
	
    <div class="row"><span class="label">{msgNewContentKeywords}</span>
    <input class="inputfield" type="text" name="keywords" size="50" /></div>
    
    <div class="row"><span class="label">{msgNewContentLink}</span>
    <input class="inputfield" type="text" name="contentlink" size="50" value="http://" /></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" /></div>
	
    </form>
    <p>{copyright_eintrag}</p>
