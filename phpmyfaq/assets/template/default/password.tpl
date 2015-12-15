<section>

            <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">

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
                    <button class="btn btn-primary" type="submit" id="changepassword">
                        {msgSubmit}
                    </button>
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