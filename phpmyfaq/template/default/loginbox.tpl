
            <div id="loginSelection">
                <a href="#" onclick="javascript:loginForm(); return false;">{msgLoginUser}</a> | {msgRegisterUser}
            </div>
            <div id="loginForm">                
                <form action="{writeLoginPath}" method="post">
                    <label for="faqusername">{username}</label><br>
                    <input type="text" name="faqusername" id="faqusername" size="16" required="required"><br>
                    <label for="faqpassword">{password}</label><br>
                    <input type="password" size="16" name="faqpassword" id="faqpassword" required="required">
                    <input type="submit" value="{login}">
                </form>
                <span class="error">{msgLoginFailed}</span>
            </div>