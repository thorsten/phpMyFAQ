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

            <form class="form-horizontal" action="{writeLoginPath}" method="post">
                <input type="hidden" name="faqloginaction" value="{faqloginaction}"/>

                <div class="control-group">
                    <label for="faqusername">{username}</label>
                    <div class="controls">
                        <input type="text" name="faqusername" id="faqusername" required="required" autofocus="autofocus">
                    </div>
                </div>

                <div class="control-group">
                    <label for="faqpassword">{password}</label>
                    <div class="controls">
                        <input type="password" name="faqpassword" id="faqpassword" required="required">
                    </div>
                </div>

                <div class="form-actions">
                    <input class="btn-primary btn-large" type="submit" value="{login}">
                </div>
            </form>

        </section>