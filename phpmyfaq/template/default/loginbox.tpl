            <ul class="nav pull-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                    {msgLoginUser}
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <!-- {msgLoginFailed} -->
                            <form action="{writeLoginPath}" method="post">
                                <input type="hidden" name="faqloginaction" value="{faqloginaction}"/>
                                <label for="faqusername">{username}</label><br/>
                                <input type="text" name="faqusername" id="faqusername" size="16" required="required"
                                       autofocus="autofocus"/><br/>
                                <label for="faqpassword">{password}</label><br/>
                                <input type="password" size="16" name="faqpassword" id="faqpassword" required="required"/>
                                <input type="submit" value="{login}"/>
                            </form>
                        </li>
                        <li class="divider"></li>
                        <li>
                            {msgRegisterUser}
                        </li>
                        <li>
                            <a href="admin/password.php" title="{msgLostPassword}">{msgLostPassword}</a>
                        </li>
                    </ul>
                </li>
            </ul>