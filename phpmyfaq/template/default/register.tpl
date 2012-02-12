<section>
            <header>
                <h2>{msgRegistration}</h2>
            </header>

            <p>{msgRegistrationCredentials}</p>
            <p>{msgRegistrationNote}</p>

            <div id="registrations"></div>

            <form id="formValues" method="post" action="#">
                <input type="hidden" name="lang" id="lang" value="{lang}" />
                <p>
                    <label for="realname">{realname}</label>
                    <input type="text" name="realname" id="realname" required="required" autofocus="autofocus" />
                </p>
                <p>
                    <label for="name">{loginname}</label>
                    <input type="text" name="name" id="name" required="required" />
                </p>
                <p>
                    <label for="email">{email}</label>
                    <input type="email" name="email" id="email" required="required" />
                </p>

                <p>
                    {captchaFieldset}
                </p>

                <div id="loader"></div>

                <p>
                    <input class="submit" type="submit"  id="submitregistration" value="{submitRegister}" />
                </p>
            </form>
            <script type="text/javascript" >
            $(function() {
                $('#submitregistration').click(function() {
                    saveFormValues('saveregistration', 'registration');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>

        </section>