    <div id="register">
        <h2>Register new User</h2>

        To register please enter your name [realname] your nick [loginname] and a valid email address!
        <br />
        After successful registration you will receive an anwser soon after Administration has
        authorized your registration.
        <br />
        <br />

        <div class="error">
        {regErrors}
        </div>    
    
        <form method="post" action="index.php?action=register">
        <fieldset><legend>{msgUserData}</legend>
        <div class="required">
            <label for="lastname">{lastname} {name_errorRegistration}</label>
            <input type="text" name="lastname" id="lastname" value="{lastname_value}" required="true">
        </div><br />
        <div class="required">
            <label for="loginname">{loginname} {login_errorRegistration}</label>
            <input type="text" name="loginname" id="loginname" value="{loginname_value}" required="true">
        </div><br />
        <div class="required">
            <label for="email">{email} {email_errorRegistration}</label>
            <input type="email" name="email" id="email" value="{email_value}" required="true">
        </div>
        </fieldset>

        {captchaFieldset}

        <div style="text-align:center;">
            <input class="submit" type="submit" name="submit" value="{submitRegister}" />
        </div>
        </form>
    </div>