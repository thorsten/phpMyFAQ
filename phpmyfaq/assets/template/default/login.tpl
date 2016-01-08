<section>

            [useSslForLogins]
            <p>
                <a href="{secureloginurl}">{securelogintext}</a>
            </p>
            [/useSslForLogins]

            {loginMessage}

            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="login-panel panel panel-default">
                        <div class="panel-heading">
                            <header>
                                <h3 class="panel-title text-center">phpMyFAQ Login</h3>
                            </header>
                        </div>
                        <div class="panel-body">
                            <form action="{writeLoginPath}" method="post" role="form">
                                <fieldset>

                                    <div class="form-group">
                                        <input type="text" name="faqusername" id="faqusername"
                                               class="form-control input-lg" placeholder="{username}" required>
                                    </div>

                                    <div class="form-group">
                                        <input type="password" name="faqpassword" id="faqpassword"
                                               class="form-control input-lg" placeholder="{password}" required>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="faqrememberme" name="faqrememberme"
                                                   value="rememberMe">
                                            {rememberMe}
                                        </label>
                                    </div>

                                    <div class="form-group">
                                        <button class="btn btn-lg btn-primary btn-block" type="submit">
                                            {loginHeader}
                                        </button>
                                    </div>

                                    <div class="form-group">
                                        <p class="pull-right">
                                            {sendPassword}
                                            <br>
                                            {registerUser}
                                        </p>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>