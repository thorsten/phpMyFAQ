function SearchAssistant() {
}

SearchAssistant.prototype.setup = function(){
    this.searchField = {
            hintText:         'Searchkey',
            textFieldName:    'search', 
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
    this.submitField = {
            //type : 'Activity'
    };
    
    this.searchModel = {
            value:    "",
            disabled: false
    };

    this.submitModel = {
            buttonLabel : 'Search', 
            buttonClass : '',
            disable : false
    };

    this.controller.setupWidget('search', this.searchField, this.searchModel);
    this.controller.setupWidget('submit', this.submitField, this.submitModel);

}
SearchAssistant.prototype.handleButtonPress = function(event){
}

SearchAssistant.prototype.activate = function(event){
}


SearchAssistant.prototype.deactivate = function(event) {
}

SearchAssistant.prototype.cleanup = function(event) {
}