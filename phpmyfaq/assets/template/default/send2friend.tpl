<section>
            <form class="form-horizontal" id="formValues" action="#" method="post" accept-charset="utf-8">
                <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <div class="control-group">
                    <label class="control-label" for="name">{msgS2FName}</label>
                    <div class="controls">
                        <input type="text" name="name" id="name" value="{defaultContentName}" required />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">{msgS2FEMail}</label>
                    <div class="controls">
                        <input type="email" name="email" id="email" value="{defaultContentMail}" required />
                    </div>
                </div>

                <div class="control-group">{msgS2FFriends}</div>

                <div class="control-group">
                    <label class="control-label" for="mailto[0]">1{msgS2FEMails}</label>
                    <div class="controls">
                        <input type="email" name="mailto[0]" id="mailto[0]"  required />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="mailto[1]">2{msgS2FEMails}</label>
                    <div class="controls">
                        <input type="email" name="mailto[1]" id="mailto[1]"  />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="mailto[2]">3{msgS2FEMails}</label>
                    <div class="controls">
                        <input type="email" name="mailto[2]" id="mailto[2]"  />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="mailto[3]">4{msgS2FEMails}</label>
                    <div class="controls">
                        <input type="email" name="mailto[3]" id="mailto[3]" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="mailto[4]">5{msgS2FEMails}</label>
                    <div class="controls">
                        <input type="email" name="mailto[4]" id="mailto[4]" />
                    </div>
                </div>

                <div class="control-group">
                    <strong>{msgS2FText}</strong><br/>
                    <em>{send2friend_text}</em>
                </div>
                <div class="control-group">
                    <strong>{msgS2FText2}</strong><br/>
                    <em>{send2friendLink}</em>
                </div>

                <div class="control-group">
                    <label class="control-label" for="message">{msgS2FMessage}</label>
                    <div class="controls">
                    <textarea name="message" id="message" cols="37" rows="5"></textarea>
                    </div>
                </div>

                {captchaFieldset}

                <div id="loader"></div>
                <div id="send2friends"></div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" id="submitfriends">
                        {msgS2FButton}
                    </button>
                </div>
            </form>
            <script type="text/javascript" >
            $(function() {
                $('#submitfriends').click(function() {
                    saveFormValues('sendtofriends', 'send2friend');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
    
        </section>
