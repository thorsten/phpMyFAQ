
            <div id="loginSelection">
                <a href="#" onclick="javascript:loginForm(); return false;">{msgLoginUser}</a> | {msgRegisterUser}
            </div>
            <div id="loginForm" class="{loginVisibility}">
                <div class="error">{msgLoginFailed}</div>
                <form action="{writeLoginPath}" method="post">
                    <input type="hidden" name="faqloginaction" value="{faqloginaction}"/>
                    <label for="faqusername">{username}</label><br/>
                    <input type="text" name="faqusername" id="faqusername" size="16" required="required"
                           autofocus="autofocus"/><br/>
                    <label for="faqpassword">{password}</label><br/>
                    <input type="password" size="16" name="faqpassword" id="faqpassword" required="required"/>
                    <input type="submit" value="{login}"/>
                </form>
                <p><a href="admin/password.php" title="{msgLostPassword}">{msgLostPassword}</a></p>
            </div>