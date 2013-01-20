<header>
    <div class="row">
    {ucpGravatarImage}
    </div>
    <h2>{headerUserControlPanel}</h2>
</header>

    <div id="ucpReturnedMessage"></div>

    <form class="form-horizontal" id="formValues" action="#" method="post">
    <input type="hidden" name="userid" value="{userid}" />

        <div class="control-group">
            <label class="control-label" for="name">{msgRealName}:</label>
            <div class="controls">
                <input type="text" name="name" id="name" required="required" tabindex="1" value="{realname}">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="email">{msgEmail}:</label>
            <div class="controls">
                <input type="email" name="email" id="email" required="required" tabindex="2" value="{email}">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="password">{msgPassword}:</label>
            <div class="controls">
                <input type="password" name="password" id="password" tabindex="3" value="">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="password_confirm">{msgConfirm}:</label>
            <div class="controls">
                <input type="password" name="password_confirm" id="password_confirm" tabindex="4" value="">
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" id="submituserdata">
                {msgSave}
            </button>
        </div>

    </form>

    <div id="loader"></div>
    <div id="userdatas"></div>

    <script type="text/javascript">
        $(function() {
            $('#submituserdata').click(function() {
                saveFormValues('saveuserdata', 'userdata');
            });
            $('form#formValues').submit(function() { return false; });
        });
    </script>

