<section>
            <div class="row">

                <div class="col-md-3 pmf-personal-gravatar">
                    <div class="text-center">
                        {ucpGravatarImage}
                    </div>
                </div>
                <div class="col-md-9 pmf-personal-info">

                    <div id="ucpReturnedMessage"></div>

                    <form class="form-horizontal" id="formValues" action="#" method="post">
                        <input type="hidden" name="userid" value="{userid}">
                        <input type="hidden" name="csrf" value="{csrf}">

                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="name">{msgRealName}:</label>
                            <div class="col-sm-8 controls">
                                <input type="text" name="name" id="name" tabindex="1" value="{realname}"
                                       class="form-control input-lg" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="email">{msgEmail}:</label>
                            <div class="col-sm-8 controls">
                                <input type="email" name="email" id="email" tabindex="2" value="{email}"
                                       class="form-control input-lg" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="password">{msgPassword}:</label>
                            <div class="col-sm-8 controls">
                                <input type="password" name="password" id="password" tabindex="3"
                                       class="form-control input-lg">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="password_confirm">{msgConfirm}:</label>
                            <div class="col-sm-8 controls">
                                <input type="password" name="password_confirm" id="password_confirm" tabindex="4"
                                       class="form-control input-lg">
                            </div>
                        </div>

                        <div class="form-actions">
                            <div class="text-right">
                                <button class="btn btn-primary btn-lg" type="submit" id="submituserdata">
                                    {msgSave}
                                </button>
                            </div>
                        </div>

                    </form>

                    <div id="loader"></div>
                    <div id="userdatas"></div>

                </div>
            </div>
        </section>

        <script>
            $(function() {
                $('#submituserdata').on('click', function() {
                    saveFormValues('saveuserdata', 'userdata');
                });
                $('form#formValues').submit(function() { return false; });
            });
        </script>
