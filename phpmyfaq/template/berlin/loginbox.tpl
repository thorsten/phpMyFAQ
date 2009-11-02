	<form action="{writeLoginPath}" method="post">
    <label for="faqusername">{username}</label>
    <input class="inputfield" type="text" name="faqusername" id="faqusername" size="20" /><br />

    <label for="faqpassword">{password}</label>
    <input class="inputfield" type="password" size="20" name="faqpassword" id="faqpassword" /><br />

    <input class="submit" type="submit" value="{login}" />
	</form>
	{msgLoginFailed}
	{msgRegisterUser}