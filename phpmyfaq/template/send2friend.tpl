<h2>{msgSend2Friend}</h2>
    <form action="{writeSendAdress}" method="post">
    <fieldset>
    <legend>{msgSend2Friend}</legend>
    
    <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />
	
    <label for="">{msgS2FName}</label>
    <input class="inputfield" type="text" name="name" value="{defaultContentName}" size="25" /><br />
	
    <label for="">{msgS2FEMail}</label>
    <input class="inputfield" type="text" name="mailfrom" value="{defaultContentMail}" size="25" /><br />
	
    <div class="row">{msgS2FFriends}</div>
	
    <label for="">1{msgS2FEMails}</label>
    <input class="inputfield" type="text" name="mailto[0]" size="25" /><br />
	
    <label for="">2{msgS2FEMails}</label>
    <input class="inputfield" type="text" name="mailto[1]" size="25" /><br />
	
    <label for="">3{msgS2FEMails}</label>
    <input class="inputfield" type="text" name="mailto[2]" size="25" /><br />
	
    <label for="">4{msgS2FEMails}</label>
    <input class="inputfield" type="text" name="mailto[3]" size="25" /><br />
	
    <label for="">5{msgS2FEMails}</label>
    <input class="inputfield" type="text" name="mailto[4]" size="25" /><br />
	
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
	
    <textarea class="inputarea" name="zusatz" cols="45" rows="5"></textarea><br />
	
    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
    
    </fieldset>
    </form>
