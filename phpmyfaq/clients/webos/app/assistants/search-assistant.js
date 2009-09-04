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
            limitResize:      false, 
            holdToEnable:     false, 
            focusMode:        Mojo.Widget.focusSelectMode,
            changeOnKeyPress: true,
            textReplacement:  false,
            maxLength:        30,
            requiresEnterKey: false
        };

    this.submitField = {};
    
    this.searchModel = { 'value' :    '', disabled: false };
    this.submitModel = {
            buttonLabel : 'Search', 
            buttonClass : '',
            disable : false
    };

    this.controller.setupWidget('search', this.searchField, this.searchModel);
    this.controller.setupWidget('submit_button', this.submitField, this.submitModel);
	this.handleButtonPressBinder = this.handleButtonPress.bind(this);
    Mojo.Event.listen(this.controller.get('submit_button'),Mojo.Event.tap, this.handleButtonPressBinder)

}

SearchAssistant.prototype.handleButtonPress = function(event){
	/*
	 * Get FAQ-Results
	 */
    var request = new Ajax.Request(PMF_URL + this.searchModel.original, {
        method: "get",
        evalJSON: "true",
        onSuccess: function(transport) {
            responseJSON = transport.responseJSON;
           
        }
    });
    this.controller.stageController.pushScene('result', responseJSON); tes
}

SearchAssistant.prototype.activate = function(event){
}


SearchAssistant.prototype.deactivate = function(event) {
}

SearchAssistant.prototype.cleanup = function(event) {
    Mojo.Event.stopListening(this.controller.get('submit'),Mojo.Event.tap, this.handleButtonPressBinder)
}