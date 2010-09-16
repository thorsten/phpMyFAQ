                
                <form action="{writeLoginPath}" method="post">
                    <label for="faqusername">{username}</label><br>
                    <input type="text" name="faqusername" id="faqusername" size="16" required="true"><br>
                    <label for="faqpassword">{password}</label><br>
                    <input type="password" size="16" name="faqpassword" id="faqpassword" required="true">
                    <input type="submit" value="{login}">
                </form>
                <span class="error">{msgLoginFailed}</span>