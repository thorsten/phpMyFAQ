function ResultAssistant() {
}

ResultAssistant.prototype.setup = function(){

    /*
     * Textfield-Attributes Declaration
     */
    this.usernameAtt = {
        hintText:         'Username',
        textFieldName:    'name', 
        modelProperty:    'original', 
        multiline:        false,
        disabledProperty: 'disabled',
        focus:            true, 
        modifierState:    Mojo.Widget.capsLock,
        //autoResize:     automatically grow or shrink the textbox horizontally,
        //autoResizeMax:  how large horizontally it can get
        //enterSubmits:   when used in conjunction with multline, if this is set, then enter will submit rather than newline
        limitResize:      false, 
        holdToEnable:     false, 
        focusMode:        Mojo.Widget.focusSelectMode,
        changeOnKeyPress: true,
        textReplacement:  false,
        maxLength:        30,
        requiresEnterKey: false
    };
    
    this.passwordAtt = {
        hintText:         'Password',
        textFieldName:    'password', 
        modelProperty:    'value', 
        multiline:        false,
        disabledProperty: 'disabled',
        focus:            true, 
        modifierState:    Mojo.Widget.capsLock,
        //autoResize:     automatically grow or shrink the textbox horizontally,
        //autoResizeMax:  how large horizontally it can get
        //enterSubmits:   when used in conjunction with multline, if this is set, then enter will submit rather than newline
        limitResize:      false, 
        holdToEnable:     false, 
        focusMode:        Mojo.Widget.focusSelectMode,
        changeOnKeyPress: true,
        textReplacement:  false,
        maxLength:        30,
        requiresEnterKey: false
        
    };
    
    /*
     * Textfield-Models Declaration
     */
    this.usernameModel = {
        value:    "",
        disabled: false
    };
    
    this.passwordModel = {
        value:    "",
        disabled: false
    };
    
    /*
     * Setup Textfield-Widget's
     */
    this.controller.setupWidget('Auth_name',     this.usernameAtt, this.usernameModel);
    this.controller.setupWidget('Auth_password', this.passwordAtt, this.passwordModel);
    
    
    this.controller.setupWidget("buttonId",
            this.buttonAtt1 = {
            //type : 'Activity'
            },
            this.buttonModel1 = {
                buttonLabel : 'Login',
                buttonClass : '',
                disable : false
            }
    );
    /* add listener button*/
    this.handleButtonPressBinder = this.handleButtonPress.bind(this);
    Mojo.Event.listen(this.controller.get("buttonId"),
                      Mojo.Event.tap, 
                      this.handleButtonPressBinder
    )
    
    /* set copywrite */
    this.controller.get( 'AppName' ).innerHTML = _APP_Name;
    
}
ResultAssistant.prototype.handleButtonPress = function(event){
    //this.controller.get('string').update(this.text2);
    //push the second scene on the scene stack
    this.name = this.textNameModel.value;
    this.password = this.textPasswordModel.value;
    this.controller.stageController.pushScene(event.item.scene);
}

ResultAssistant.prototype.activate = function(event){
    /* put in event handlers here that should only be in effect when this scene is active. For
     example, key handlers that are observing the document */
    if (event != undefined) {
        this.controller.get('string').update(this.text2 + "<br>" + event);
        this.model.original = "New Text";
        this.controller.modelChanged(this.model);
    }   
}


ResultAssistant.prototype.deactivate = function(event) {
    /* remove any event handlers you added in activate and do any other cleanup that should happen before
       this scene is popped or another scene is pushed on top */
}

ResultAssistant.prototype.cleanup = function(event) {
    /* this function should do any cleanup needed before the scene is destroyed as 
       a result of being popped off the scene stack */
   Mojo.Event.stopListening(this.controller.get('push_button'),Mojo.Event.tap, this.handleButtonPressBinder)
}