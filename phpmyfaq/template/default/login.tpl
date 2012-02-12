        <section>

            <header>
                <h2>{loginHeader}</h2>
            </header>

            [useSslForLogins]
            <p>
                <a href="{secureloginurl}">{securelogintext}</a>
            </p>
            [/useSslForLogins]

            {loginMessage}

            <form action="{writeLoginPath}" method="post">
                <input type="hidden" name="faqloginaction" value="{faqloginaction}"/>

                <p>
                    <label for="faqusername">{username}</label>
                    <input type="text" name="faqusername" id="faqusername" size="16" required="required"
                           autofocus="autofocus">
                </p>
                <p>
                    <label for="faqpassword">{password}</label>
                    <input type="password" size="16" name="faqpassword" id="faqpassword" required="required">
                </p>
                <p>
                    <input class="submit" type="submit" value="{login}">
                </p>

            </form>

        </section>