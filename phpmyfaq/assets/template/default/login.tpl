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

            <form class="form-horizontal" action="{writeLoginPath}" method="post" accept-charset="utf-8">
                <input type="hidden" name="faqloginaction" value="{faqloginaction}"/>

                <div class="control-group">
                    <label class="control-label" for="faqusername">{username}</label>
                    <div class="controls">
                        <input type="text" name="faqusername" id="faqusername" required="required" autofocus="autofocus">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="faqpassword">{password}</label>
                    <div class="controls">
                        <input type="password" name="faqpassword" id="faqpassword" required="required">
                        <p class="help-block">{sendPassword}</p>
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox">
                            <input type="checkbox" id="faqrememberme" name="faqrememberme" value="rememberMe">
                            {rememberMe}
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        {loginHeader}
                    </button>
                </div>
            </form>

        </section>