<h2>{msgSend2Friend}</h2>
    <form action="{writeSendAdress}" method="post">
    <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />
	
    <div class="row"><span class="label">{msgS2FName}</span>
    <input class="inputfield" type="text" name="name" size="25" /></div>
	
    <div class="row"><span class="label">{msgS2FEMail}</span>
    <input class="inputfield" type="text" name="mailfrom" size="25" /></div>
	
    <div class="row"><span class="label"></span>
    {msgS2FFriends}</div>
	
    <div class="row"><span class="label">1{msgS2FEMails}</span>
    <input class="inputfield" type="text" name="mailto[0]" size="25" /></div>
	
    <div class="row"><span class="label">2{msgS2FEMails}</span>
    <input class="inputfield" type="text" name="mailto[1]" size="25" /></div>
	
    <div class="row"><span class="label">3{msgS2FEMails}</span>
    <input class="inputfield" type="text" name="mailto[2]" size="25" /></div>
	
    <div class="row"><span class="label">4{msgS2FEMails}</span>
    <input class="inputfield" type="text" name="mailto[3]" size="25" /></div>
	
    <div class="row"><span class="label">5{msgS2FEMails}</span>
    <input class="inputfield" type="text" name="mailto[4]" size="25" /></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    {msgS2FText}</div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <em>{send2friend_text}</em></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    {msgS2FText2}</div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <em>{send2friendLink}</em></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    {msgS2FMessage}</div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <textarea class="inputarea" name="zusatz" cols="45" rows="5"></textarea></div>
	
    <div class="row"><span class="label">&nbsp;</span>
    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" /></div>

    </form>
