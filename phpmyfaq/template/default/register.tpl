<section>
            <header>
                <h2>Register new User</h2>
            </header>

            <p>To register please enter your name [realname] your nick [loginname] and a valid email address!</p>
            <p>After successful registration you will receive an anwser soon after Administration has
        authorized your registration.</p>

            <div class="error">{regErrors}</div>
    
            <form method="post" action="index.php?action=register">
                <p>
                    <label for="lastname">{lastname} {name_errorRegistration}</label>
                    <input type="text" name="lastname" id="lastname" value="{lastname_value}" required="required" />
                </p>
                <p>
                    <label for="loginname">{loginname} {login_errorRegistration}</label>
                    <input type="text" name="loginname" id="loginname" value="{loginname_value}" required="required" />
                </p>
                <p>
                    <label for="email">{email} {email_errorRegistration}</label>
                    <input type="email" name="email" id="email" value="{email_value}" required="required" />
                </p>

                <p>
                    {captchaFieldset}
                </p>

                <p>
                    <input class="submit" type="submit" name="submit" value="{submitRegister}" />
                </p>
            </form>

        </section>