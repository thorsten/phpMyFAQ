<header>
    <h2>{headerUserControlPanel}</h2>
</header>

    <div id="ucpReturnedMessage"></div>

    <form id="formValues" action="#" method="post">
    <input type="hidden" name="userid" value="{userid}" />

    <p>
        <label for="name">{msgRealName}:</label>
        <input type="text" name="name" id="name" required="required" tabindex="2" value="{realname}" />
    </p>

    <p>
        <label for="email">{msgEmail}:</label>
        <input type="email" name="email" id="email" required="required" tabindex="3" value="{email}" />
    </p>

    <p>
        <label for="password">{msgPassword}:</label>
        <input type="password" name="password" id="password" tabindex="4" value=""  />
    </p>

    <p>
        <label for="password_confirm">{msgConfirm}:</label>
        <input type="password" name="password_confirm" id="password_confirm" tabindex="5" value=""  />
    </p>

    <p>
        <input class="submit" type="submit" id="submituserdata" value="{msgSave}" tabindex="6" />
    </p>

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

