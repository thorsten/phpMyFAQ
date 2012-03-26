        <section>

            <header>
                <h2>{headerChangePassword}</h2>
            </header>

            <form id="formValues" action="#" method="post" class="form-horizontal">

                <div class="control-group">
                    <label class="control-label">{msgUsername}</label>
                    <div class="controls">
                        <input type="text" name="username" required="required" autofocus="autofocus" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">{msgEmail}</label>
                    <div class="controls">
                        <input type="email" name="email" required="required" />
                    </div>
                </div>

                <div class="form-actions">
                    <input class="btn-primary" type="submit" id="changepassword" value="{msgSubmit}" />
                </div>
            </form>

            <div id="loader"></div>
            <div id="changepasswords"></div>

            <script type="text/javascript" >
                $(function() {
                    $('#changepassword').click(function() {
                        saveFormValues('changepassword', 'changepassword');
                    });
                    $('form#formValues').submit(function() { return false; });
                });
            </script>

        </section>