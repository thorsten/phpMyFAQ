<section>
            <header>
                <h2>{msgSend2Friend}</h2>
            </header>

            <form action="{writeSendAdress}" method="post">
                <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />

                <p>
                    <label for="name">{msgS2FName}</label>
                    <input type="text" name="name" id="name" value="{defaultContentName}" size="50" required="required" />
                </p>

                <p>
                    <label for="mailfrom">{msgS2FEMail}</label>
                    <input type="email" name="mailfrom" id="mailfrom" value="{defaultContentMail}" size="50" required="required" />
                </p>

                <p>{msgS2FFriends}</p>

                <p>
                    <label for="mailto[0]">1{msgS2FEMails}</label>
                    <input type="email" name="mailto[0]" id="mailto[0]" size="50" required="required" />
                </p>

                <p>
                    <label for="mailto[1]">2{msgS2FEMails}</label>
                    <input type="email" name="mailto[1]" id="mailto[1]" size="50" />
                </p>

                <p>
                    <label for="mailto[2]">3{msgS2FEMails}</label>
                    <input type="email" name="mailto[2]" id="mailto[2]" size="50" />
                </p>
                <p>
                    <label for="mailto[3]">4{msgS2FEMails}</label>
                    <input type="email" name="mailto[3]" id="mailto[3]" size="50" />
                </p>

                <p>
                    <label for="mailto[4]">5{msgS2FEMails}</label>
                    <input type="email" name="mailto[4]" id="mailto[4]" size="50" />
                </p>

                <p>{msgS2FText}</p>
                <p><em>{send2friend_text}</em></p>
                <p>{msgS2FText2}</p>
                <p><em>{send2friendLink}</em></p>

                <p>
                    <label for="zusatz">{msgS2FMessage}</label>
                    <textarea name="zusatz" id="zusatz" cols="37" rows="5"></textarea>
                </p>

                <p>
                    {captchaFieldset}
                </p>

                <p>
                    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
                </p>
            </form>
        </section>
