<section>
            <header>
                <h2>{msgRegistration}</h2>
            </header>

            <p>{msgRegistrationCredentials} {msgRegistrationNote}</p>

            <div id="registrations"></div>

            <form class="form-horizontal" id="formValues" method="post" action="#">
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <div class="control-group">
                    <label class="control-label" for="realname">{realname}</label>
                    <div class="controls">
                        <input type="text" name="realname" id="realname" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="name">{loginname}</label>
                    <div class="controls">
                        <input type="text" name="name" id="name" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">{email}</label>
                    <div class="controls">
                        <input type="email" name="email" id="email" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    {captchaFieldset}
                </div>

                <div id="loader"></div>

                <div class="form-actions">
                    <input class="btn-primary" type="submit"  id="submitregistration" value="{submitRegister}" />
                </div>
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
