<h2>{msgCommentHeader}</h2>
	<form action="{writeSendAdress}" method="post">
	<input type="hidden" name="id" value="{ID}" />
	<input type="hidden" name="lang" value="{LANG}" />
	
    <div class="row"><span class="label">&nbsp;</span>
    <strong><em>{writeThema}</em></strong></div>
	
    <div class="row"><span class="label">{msgNewContentName}</span>
    <input class="inputfield" type="text" name="user" size="20" /></div>
	
    <div class="row"><span class="label">{msgNewContentMail}</span>
    <input class="inputfield" type="text" name="mail" size="20" /></div>
	
    <div class="row"><span class="label">{msgYourComment}</span>
    <textarea class="inputarea" cols="30" rows="10" name="comment"></textarea></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" /></div>

	</form>
	<p class="little">{copyright_eintrag}</p>