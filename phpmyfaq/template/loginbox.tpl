	<form action="{writeLoginPath}" method="post">
    <fieldset class="login">
        <legend class="login">{login}</legend>

        <label class="left" for="faqusername">{username}</label>
        <input class="admin" type="text" name="faqusername" id="faqusername" size="20" /><br />
        
        <label class="left" for="faqpassword">{password}</label>
        <input class="admin" type="password" size="20" name="faqpassword" id="faqpassword" /><br />
        
        <input class="submit" type="submit" value="{login}" />

    </fieldset>
	</form>