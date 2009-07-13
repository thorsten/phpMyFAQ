<h2>{msgQuestion}</h2>	

<form action="{writeSendAdress}" method="post">
<input type="hidden" name="username" value="{postUsername}" />
<input type="hidden" name="usermail" value="{postUsermail}" />
<input type="hidden" name="rubrik" value="{postRubrik}" />
<input type="hidden" name="content" value="{postContent}" />

<b>{msgAskYourQuestion}</b> {msgContent} &nbsp;&nbsp;&nbsp; 

<input class="submit" type="submit" name="submit" value="{msgQuestion}" />
</form>
    
{printResult}	