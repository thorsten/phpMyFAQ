<h2>{msgSend2Friend}</h2>
    <form action="{writeSendAdress}" method="post">
    <fieldset>
    <legend>{msgSend2Friend}</legend>

    <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />

    <label for="name">{msgS2FName}</label>
    <input type="text" name="name" value="{defaultContentName}" size="50" /><br />

    <label for="mailfrom">{msgS2FEMail}</label>
    <input type="email" name="mailfrom" value="{defaultContentMail}" size="50" /><br />

    <div class="row">{msgS2FFriends}</div>

    <label for="mailto[0]">1{msgS2FEMails}</label>
    <input type="email" name="mailto[0]" size="50" /><br />

    <label for="mailto[1]">2{msgS2FEMails}</label>
    <input type="email" name="mailto[1]" size="50" /><br />

    <label for="mailto[2]">3{msgS2FEMails}</label>
    <input type="email" name="mailto[2]" size="50" /><br />

    <label for="mailto[3]">4{msgS2FEMails}</label>
    <input type="email" name="mailto[3]" size="50" /><br />

    <label for="mailto[4]">5{msgS2FEMails}</label>
    <input type="email" name="mailto[4]" size="50" /><br />

    <p>{msgS2FText}</p>
    <p><em>{send2friend_text}</em></p>
    <p>{msgS2FText2}</p>
    <p><em>{send2friendLink}</em></p>

	<label for="zusatz">{msgS2FMessage}</label>
	<textarea name="zusatz" cols="37" rows="5"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
    </div>
    </form>
