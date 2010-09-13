            
            <form action="{writeLoginPath}" method="post">
            <label for="faqusername">{username}</label>
            <input type="text" name="faqusername" id="faqusername" size="20" required="true"><br>
            
            <label for="faqpassword">{password}</label>
            <input type="password" size="20" name="faqpassword" id="faqpassword"  required="true"><br>
            
            <input class="submit" type="submit" value="{login}" />
            </form>
            {msgLoginFailed}
            {msgRegisterUser}