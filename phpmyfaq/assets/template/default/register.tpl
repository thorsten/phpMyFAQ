<section>
            <p>{msgRegistrationCredentials} {msgRegistrationNote}</p>

            <div id="registrations"></div>

            <form class="form-horizontal" id="formValues" method="post" action="#" accept-charset="utf-8">
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="realname">{realname}</label>
                    <div class="col-sm-9">
                        <input type="text" name="realname" id="realname" required class="form-control input-lg">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{loginname}</label>
                    <div class="col-sm-9">
                        <input type="text" name="name" id="name" required class="form-control input-lg">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{email}</label>
                    <div class="col-sm-9">
                        <input type="email" name="email" id="email" required class="form-control input-lg">
                    </div>
                </div>

                {captchaFieldset}

                <div id="loader"></div>

                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-9">
                        <button class="btn btn-lg btn-primary" type="submit" id="submitregistration">
                            {submitRegister}
                        </button>
                    </div>
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
