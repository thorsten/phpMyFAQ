<section>
            <form class="form-horizontal" id="formValues" action="#" method="post" accept-charset="utf-8">
                <input type="hidden" name="{msgS2FReferrer}" value="{send2friendLink}" />
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{msgS2FName}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="name" id="name" value="{defaultContentName}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{msgS2FEMail}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="email" id="email" value="{defaultContentMail}" required>
                    </div>
                </div>

                <div class="form-group">{msgS2FFriends}</div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mailto[0]">1{msgS2FEMails}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="mailto[0]" id="mailto[0]"  required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mailto[1]">2{msgS2FEMails}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="mailto[1]" id="mailto[1]">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mailto[2]">3{msgS2FEMails}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="mailto[2]" id="mailto[2]">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mailto[3]">4{msgS2FEMails}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="mailto[3]" id="mailto[3]">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mailto[4]">5{msgS2FEMails}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="mailto[4]" id="mailto[4]">
                    </div>
                </div>

                <div class="form-group">
                    <strong>{msgS2FText}</strong><br>
                    <em>{send2friend_text}</em>
                </div>
                <div class="form-group">
                    <strong>{msgS2FText2}</strong><br>
                    <em>{send2friendLink}</em>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="message">{msgS2FMessage}</label>
                    <div class="col-sm-9">
                    <textarea name="message" id="message" class="form-control" cols="37" rows="5"></textarea>
                    </div>
                </div>

                {captchaFieldset}

                <div id="loader"></div>
                <div id="send2friends"></div>

                <div class="form-actions text-right">
                    <button class="btn btn-primary btn-lg" type="submit" id="submitfriends">
                        {msgS2FButton}
                    </button>
                </div>
            </form>
            <script>
            $(function() {
                $('#submitfriends').click(function() {
                    saveFormValues('sendtofriends', 'send2friend');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
    
        </section>
